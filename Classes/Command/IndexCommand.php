<?php

namespace Kitodo\Dlf\Command;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Kitodo\Dlf\Common\Document;

/**
 * Index single document into database and Solr.
 */
class IndexCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
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
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::getInstance()->initializeBackendAuthentication();

        $dryRun = $input->getOption('dry-run') != false ? true : false;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $startingPoint = 0;
        if (MathUtility::canBeInterpretedAsInteger($input->getOption('pid'))) {
            $startingPoint = MathUtility::forceIntegerInRange((int) $input->getOption('pid'), 0);
        }
        if ($startingPoint == 0) {
            $io->error('ERROR: No valid PID (' . $startingPoint . ') given.');
            exit(1);
        }

        if ($input->getOption('solr')) {
            $allSolrCores = $this->getSolrCores($startingPoint);
            if (MathUtility::canBeInterpretedAsInteger($input->getOption('solr'))) {
                $solrCoreUid = MathUtility::forceIntegerInRange((int) $input->getOption('solr'), 0);
            } else {
                $solrCoreName = $input->getOption('solr');
                $solrCoreUid = $allSolrCores[$solrCoreName];
            }
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
            $io->error('ERROR: Required parameter --solr|-s is missing.');
            exit(1);
        }

        if (
            !MathUtility::canBeInterpretedAsInteger($input->getOption('doc'))
            && !GeneralUtility::isValidUrl($input->getOption('doc'))
        ) {
            $io->error('ERROR: "' . $input->getOption('doc') . '" is not a valid document UID or URL for --doc|-d.');
            exit(1);
        }

        // Get the document...
        $doc = Document::getInstance($input->getOption('doc'), $startingPoint, TRUE);
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


    /**
     * Fetches all records of tx_dlf_solrcores on given page.
     *
     * @param int $pageId the uid of the solr record
     *
     * @return array array of valid solr cores
     */
    protected function getSolrCores(int $pageId): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_solrcores');

        $solrCores = [];
        $pageId = (int) $pageId;
        $result = $queryBuilder
            ->select('uid', 'index_name')
            ->from('tx_dlf_solrcores')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->execute();

        while ($record = $result->fetch()) {
            $solrCores[$record['index_name']] = $record['uid'];
        }

        return $solrCores;
    }
}
