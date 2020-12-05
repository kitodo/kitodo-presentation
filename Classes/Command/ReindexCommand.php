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

/**
 * CLI Command for re-indexing collections into database and Solr.
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ReindexCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     *
     * @return void
     */
    public function configure()
    {
        $this
            ->setDescription('Reindex a collection into database and Solr.')
            ->setHelp('')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not actually be processed but the location URI is shown.'
            )
            ->addOption(
                'coll',
                'c',
                InputOption::VALUE_REQUIRED,
                'UID of the collection.'
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
                'owner',
                'o',
                InputOption::VALUE_OPTIONAL,
                '[UID|index_name] of the Library which should be set as owner of the documents.'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Reindex all documents on the given page.'
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

        if (!empty($input->getOption('owner'))) {
            if (MathUtility::canBeInterpretedAsInteger($input->getOption('owner'))) {
                $owner = MathUtility::forceIntegerInRange((int) $input->getOption('owner'), 1);
            } else {
                $owner = (string) $input->getOption('owner');
            }
        } else {
            $owner = null;
        }

        if (!empty($input->getOption('all'))) {
            // Get all documents.
            $documents = $this->getAllDocuments($startingPoint);
        } elseif (
            !empty($input->getOption('coll'))
            && !is_array($input->getOption('coll'))
        ) {
            // "coll" may be a single integer or a comma-separated list of integers.
            if (empty(array_filter(GeneralUtility::intExplode(',', $input->getOption('coll'), true)))) {
                $io->error('ERROR: Parameter --coll|-c is not a valid comma-separated list of collection UIDs.');
                exit(1);
            }
            $documents = $this->getDocumentsToProceed($input->getOption('coll'), $startingPoint);
        } else {
            $io->error('ERROR: One of parameters --all|-a or --coll|-c must be given.');
            exit(1);
        }

        foreach ($documents as $id => $document) {
            $doc = Document::getInstance($document, $startingPoint, true);
            if ($doc->ready) {
                if ($dryRun) {
                    $io->writeln('DRY RUN: Would index ' . $id . '/' . count($documents) . ' ' . $doc->uid . ' ("' . $doc->location . '") on PID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
                } else {
                    if ($io->isVerbose()) {
                        $io->writeln(date('Y-m-d H:i:s') . ' Indexing ' . $id . '/' . count($documents) . ' ' . $doc->uid . ' ("' . $doc->location . '") on PID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
                    }
                    // ...and save it to the database...
                    if (!$doc->save($startingPoint, $solrCoreUid, $owner)) {
                        $io->error('ERROR: Document "' . $id . '" not saved and indexed.');
                    }
                }
            } else {
                $io->error('ERROR: Document "' . $id . '" could not be loaded.');
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
     * Fetches all documents with given collection.
     *
     * @param string $collId A comma separated list of collection UIDs
     * @param int $pageId The PID of the collections' documents
     *
     * @return array Array of documents to index
     */
    protected function getDocumentsToProceed(string $collIds, int $pageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $documents = [];
        $result = $queryBuilder
            ->select('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->join(
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_relations_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_local',
                    'tx_dlf_documents.uid'
                )
            )
            ->join(
                'tx_dlf_relations_joins',
                'tx_dlf_collections',
                'tx_dlf_collections_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_foreign',
                    'tx_dlf_collections_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in(
                        'tx_dlf_collections_join.uid',
                        $queryBuilder->createNamedParameter(
                            GeneralUtility::intExplode(',', $collIds, true),
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        'tx_dlf_collections_join.pid',
                        $queryBuilder->createNamedParameter((int) $pageId, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tx_dlf_relations_joins.ident',
                        $queryBuilder->createNamedParameter('docs_colls')
                    )
                )
            )
            ->groupBy('tx_dlf_documents.uid')
            ->orderBy('tx_dlf_documents.uid', 'ASC')
            ->execute();

        while ($record = $result->fetch()) {
            $documents[] = $record['uid'];
        }

        return $documents;
    }

    /**
     * Fetches all documents of given page.
     *
     * @param int $pageId The documents' PID
     *
     * @return array Array of documents to index
     */
    protected function getAllDocuments(int $pageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $documents = [];
        $pageId = (int) $pageId;
        $result = $queryBuilder
            ->select('uid')
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_dlf_documents.pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->orderBy('tx_dlf_documents.uid', 'ASC')
            ->execute();

        while ($record = $result->fetch()) {
            $documents[] = $record['uid'];
        }
        return $documents;
    }
}
