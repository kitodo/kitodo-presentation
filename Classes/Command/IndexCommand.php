<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Kitodo\Dlf\Command\BaseCommand;
use Kitodo\Dlf\Common\Document;

/**
 * CLI Command for indexing single documents into database and Solr.
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class IndexCommand extends BaseCommand
{
    /**
     * Configure the command by defining the name, options and arguments
     *
     * @return void
     */
    public function configure()
    {
        $this
            ->setDescription('Index single document into database and Solr.')
            ->setHelp('')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not actually be processed but the location URI is shown.'
            )
            ->addOption(
                'doc',
                'd',
                InputOption::VALUE_REQUIRED,
                'UID or URL of the document.'
            )
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'UID of the page the document should be added to.'
            )
            ->addOption(
                'solr',
                's',
                InputOption::VALUE_REQUIRED,
                '[UID|index_name] of the Solr core the document should be added to.'
            );
    }

    /**
     * Executes the command to index the given document to db and solr.
     *
     * @param InputInterface $input The input parameters
     * @param OutputInterface $output The Symfony interface for outputs on console
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::getInstance()->initializeBackendAuthentication();

        $dryRun = $input->getOption('dry-run') != false ? true : false;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $startingPoint = getStartingPoint($input->getOption('pid'));

        if ($startingPoint == 0) {
            $io->error('ERROR: No valid PID (' . $startingPoint . ') given.');
            exit(1);
        }

        if (
            !empty($input->getOption('solr'))
            && !is_array($input->getOption('solr'))
        ) {
            $allSolrCores = $this->getSolrCores($startingPoint);
            $solrCoreUid = $this->getSolrCoreUid($allSolrCores, $input->getOption('solr'));

            // Abort if solrCoreUid is empty or not in the array of allowed solr cores.
            if (empty($solrCoreUid) || !in_array($solrCoreUid, $allSolrCores)) {
                $output_solrCores = [];
                foreach ($allSolrCores as $index_name => $uid) {
                    $output_solrCores[] = $uid . ' : ' . $index_name;
                }
                if (empty($output_solrCores)) {
                    $io->error('ERROR: No valid Solr core ("' . $input->getOption('solr') . '") given. No valid cores found on PID ' . $startingPoint . ".\n");
                    exit(1);
                } else {
                    $io->error('ERROR: No valid Solr core ("' . $input->getOption('solr') . '") given. ' . "Valid cores are (<uid>:<index_name>):\n" . implode("\n", $output_solrCores) . "\n");
                    exit(1);
                }
            }
        } else {
            $io->error('ERROR: Required parameter --solr|-s is missing or array.');
            exit(1);
        }

        if (
            empty($input->getOption('doc'))
            || is_array($input->getOption('doc'))
            || (
                !MathUtility::canBeInterpretedAsInteger($input->getOption('doc'))
                && !GeneralUtility::isValidUrl($input->getOption('doc'))
            )
        ) {
            $io->error('ERROR: Required parameter --doc|-d is not a valid document UID or URL.');
            exit(1);
        }

        // Get the document...
        $doc = Document::getInstance($input->getOption('doc'), $startingPoint, true);
        if ($doc->ready) {
            if ($dryRun) {
                $io->section('DRY RUN: Would index ' . $doc->uid . ' ("' . $doc->location . '") on UID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
            } else {
                if ($io->isVerbose()) {
                    $io->section('Indexing ' . $doc->uid . ' ("' . $doc->location . '") on UID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
                }
                // ...and save it to the database...
                if (!$doc->save($startingPoint, $solrCoreUid)) {
                    $io->error('ERROR: Document "' . $input->getOption('doc') . '" not saved and indexed.');
                    exit(1);
                }
            }
        } else {
            $io->error('ERROR: Document "' . $input->getOption('doc') . '" could not be loaded.');
            exit(1);
        }

        $io->success('All done!');
    }
}
