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
use TYPO3\CMS\Core\Database\Connection;
use Kitodo\Dlf\Common\Document;

/**
 * Reindex a collection into database and Solr.
 */
class ReindexCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
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
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Reindex all documents on the given page.'
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

        if ($input->getOption('all')) {
            // Get the document...
            $documents = $this->getAllDocuments($startingPoint);
        } else {
            // coll may be a single integer, a list of integer
            if (empty(array_filter(GeneralUtility::intExplode(',', $input->getOption('coll'), true)))) {
                $io->error('ERROR: "' . $input->getOption('coll') . '" is not a valid list of collection UIDs for --coll|-c.');
                exit(1);
            }
            $documents = $this->getDocumentsToProceed($input->getOption('coll'), $startingPoint);
        }

        foreach ($documents as $id => $document) {
            $doc = Document::getInstance($document, $startingPoint, TRUE);
            if ($doc->ready) {
                if ($dryRun) {
                    $io->writeln('DRY RUN: Would index ' . $id . '/' . count($documents) . ' ' . $doc->uid . ' ("' . $doc->location . '") on UID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
                } else {
                    if ($io->isVerbose()) {
                        $io->writeln(date('Y-m-d H:i:s') . ' Indexing ' . $id . '/' . count($documents) . ' ' . $doc->uid . ' ("' . $doc->location . '") on UID ' . $startingPoint . ' and Solr core ' . $solrCoreUid . '.');
                    }
                    // ...and save it to the database...
                    if (!$doc->save($startingPoint, $solrCoreUid)) {
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
     * Fetches all records of tx_dlf_solrcores on given page.
     *
     * @param int $pageId the uid of the solr record (can also be 0)
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

    /**
     * Fetches all documents with given collection.
     *
     * @param string $collId a comma separated list of collection uids
     * @param int $pageId the uid of the solr record
     *
     * @return array array of valid solr cores
     */
    protected function getDocumentsToProceed(string $collIds, int $pageId): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $documents = [];
        $pageId = (int) $pageId;
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
                        $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
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
     * @param int $pageId the uid of the solr record
     *
     * @return array array of valid solr cores
     */
    protected function getAllDocuments(int $pageId): array
    {
        /** @var QueryBuilder $queryBuilder */
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
