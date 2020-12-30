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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use Kitodo\Dlf\Common\Document;
use Phpoaipmh\Endpoint;
use Phpoaipmh\Exception\BaseoaipmhException;

/**
 * CLI Command for harvesting OAI-PMH interfaces into database and Solr.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class HarvestCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     *
     * @return void
     */
    public function configure()
    {
        $this
            ->setDescription('Harvest OAI-PMH contents into database and Solr.')
            ->setHelp('')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not actually be processed but the location URIs are shown.'
            )
            ->addOption(
                'lib',
                'l',
                InputOption::VALUE_REQUIRED,
                'UID of the library to harvest.'
            )
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'UID of the page the documents should be added to.'
            )
            ->addOption(
                'solr',
                's',
                InputOption::VALUE_REQUIRED,
                '[UID|index_name] of the Solr core the document should be added to.'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Datestamp (YYYY-MM-DD) to begin harvesting from.'
            )
            ->addOption(
                'until',
                null,
                InputOption::VALUE_OPTIONAL,
                'Datestamp (YYYY-MM-DD) to end harvesting on.'
            )
            ->addOption(
                'set',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of the set to limit harvesting to.'
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

        $startingPoint = 0;
        if (MathUtility::canBeInterpretedAsInteger($input->getOption('pid'))) {
            $startingPoint = MathUtility::forceIntegerInRange((int) $input->getOption('pid'), 0);
        }
        if ($startingPoint == 0) {
            $io->error('ERROR: No valid PID (' . $startingPoint . ') given.');
            exit(1);
        }

        if (
            !empty($input->getOption('solr'))
            && !is_array($input->getOption('solr'))
        ) {
            $allSolrCores = $this->getSolrCores($startingPoint);
            if (MathUtility::canBeInterpretedAsInteger($input->getOption('solr'))) {
                $solrCoreUid = MathUtility::forceIntegerInRange((int) $input->getOption('solr'), 0);
            } else {
                $solrCoreUid = $allSolrCores[$input->getOption('solr')];
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
            $io->error('ERROR: Required parameter --solr|-s is missing or array.');
            exit(1);
        }

        if (MathUtility::canBeInterpretedAsInteger($input->getOption('lib'))) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_libraries');

            $result = $queryBuilder
                ->select('oai_base')
                ->from('tx_dlf_libraries')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter((int) $input->getOption('lib'), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter((int) $startingPoint, Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute();

            $record = $result->fetch();
            $baseUrl = $record['oai_base'];
        } else {
            $io->error('ERROR: Required parameter --lib|-l is not a valid UID.');
            exit(1);
        }
        if (!GeneralUtility::isValidUrl($baseUrl)) {
            $io->error('ERROR: No valid OAI Base URL set for library with given UID ("' . $input->getOption('lib') . '").');
            exit(1);
        } else {
            try {
                $oai = Endpoint::build($baseUrl);
            } catch (BaseoaipmhException $e) {
                $this->handleOaiError($e, $io);
            }
        }

        if (
            !is_array($input->getOption('from'))
            && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $input->getOption('from'))
        ) {
            $from = new \DateTime($input->getOption('from'));
        } else {
            $from = null;
        }

        if (
            !is_array($input->getOption('until'))
            && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $input->getOption('until'))
        ) {
            $until = new \DateTime($input->getOption('until'));
        } else {
            $until = null;
        }

        $set = null;
        if (
            !is_array($input->getOption('set'))
            && !empty($input->getOption('set'))
        ) {
            $setsAvailable = $oai->listSets();
            foreach ($setsAvailable as $setAvailable) {
                if ((string) $setAvailable->setSpec === $input->getOption('set')) {
                    $set = $input->getOption('set');
                    break;
                }
            }
            if (empty($set)) {
                $io->error('ERROR: OAI interface does not provide a set with given setSpec ("' . $input->getOption('set') . '").');
                exit(1);
            }
        }

        // Get OAI record identifiers to process.
        try {
            $identifiers = $oai->listIdentifiers('mets', $from, $until, $set);
        } catch (BaseoaipmhException $exception) {
            $this->handleOaiError($exception, $io);
        }

        // Process all identifiers.
        $baseLocation = $baseUrl . (parse_url($baseUrl, PHP_URL_QUERY) ? '&' : '?');
        foreach ($identifiers as $identifier) {
            // Build OAI GetRecord URL...
            $params = [
                'verb' => 'GetRecord',
                'metadataPrefix' => 'mets',
                'identifier' => (string) $identifier->identifier
            ];
            $docLocation = $baseLocation . http_build_query($params);
            // ...index the document...
            $doc = Document::getInstance($docLocation, $startingPoint, true);
            if ($doc->ready) {
                if ($dryRun) {
                    $io->writeln('DRY RUN: Would index ' . $doc->uid . ' ("' . $doc->location . '") on PID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
                } else {
                    if ($io->isVerbose()) {
                        $io->writeln(date('Y-m-d H:i:s') . ' Indexing ' . $doc->uid . ' ("' . $doc->location . '") on PID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
                    }
                    // ...and save it to the database...
                    if (!$doc->save($startingPoint, $solrCoreUid, (int) $input->getOption('lib'))) {
                        $io->error('ERROR: Document "' . $doc->location . '" not saved and indexed.');
                    }
                }
            } else {
                $io->error('ERROR: Document "' . $docLocation . '" could not be loaded.');
            }
            // Clear document registry to prevent memory exhaustion.
            Document::clearRegistry();
        }

        $io->success('All done!');
    }


    /**
     * Fetches all Solr cores on given page.
     *
     * @param int $pageId The UID of the Solr core or 0 to disable indexing
     *
     * @return array Array of valid Solr cores
     */
    protected function getSolrCores(int $pageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_solrcores');

        $solrCores = [];
        $result = $queryBuilder
            ->select('uid', 'index_name')
            ->from('tx_dlf_solrcores')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter((int) $pageId, Connection::PARAM_INT)
                )
            )
            ->execute();

        while ($record = $result->fetch()) {
            $solrCores[$record['index_name']] = $record['uid'];
        }

        return $solrCores;
    }

    /**
     * Handles OAI errors
     *
     * @param BaseoaipmhException $exception Instance of exception thrown
     * @param SymfonyStyle $io
     *
     * @return void
     */
    protected function handleOaiError(BaseoaipmhException $exception, SymfonyStyle $io)
    {
        $io->error('ERROR: Trying to retrieve data from OAI interface resulted in error:' . "\n    " . $exception->getMessage());
    }
}
