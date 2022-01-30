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

namespace Kitodo\Dlf\Domain\Repository;

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Domain\Model\Document;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class DocumentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Find one document by given parameters
     *
     * GET parameters may be:
     *
     * - 'id': the uid of the document
     * - 'location': the URL of the location of the XML file
     * - 'recordId': the record_id of the document
     *
     * @param array $parameters
     *
     * @return \Kitodo\Dlf\Domain\Model\Document|null
     */
    public function findOneByParameters($parameters)
    {
        $doc = null;
        $document = null;

        if (isset($parameters['id']) && MathUtility::canBeInterpretedAsInteger($parameters['id'])) {

            $document = $this->findOneByIdAndSettings($parameters['id']);

        } else if (isset($parameters['recordId'])) {

            $document = $this->findOneByRecordId($parameters['recordId']);

        } else if (isset($parameters['location']) && GeneralUtility::isValidUrl($parameters['location'])) {

            $doc = Doc::getInstance($parameters['location'], [], true);

            if ($doc->recordId) {
                $document = $this->findOneByRecordId($doc->recordId);
            }

            if ($document === null) {
                // create new (dummy) Document object
                $document = GeneralUtility::makeInstance(Document::class);
                $document->setLocation($parameters['location']);
            }

        }

        if ($document !== null && $doc === null) {
            $doc = Doc::getInstance($document->getLocation(), [], true);
        }

        if ($doc !== null) {
            $document->setDoc($doc);
        }

        return $document;

    }


    public function findByUidAndPartOf($uid, $partOf)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('uid', $uid));
        $query->matching($query->equals('partof', $partOf));

        return $query->execute();
    }

    /**
     * Find the oldest document
     *
     * @return \Kitodo\Dlf\Domain\Model\Document|null
     */
    public function findOldestDocument()
    {
        $query = $this->createQuery();

        $query->setOrderings(['tstamp' => QueryInterface::ORDER_ASCENDING]);
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @param int $partOf
     * @param string $structure
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function getChildrenOfYearAnchor($partOf, $structure)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('structure', Helper::getUidFromIndexName($structure, 'tx_dlf_structures')));
        $query->matching($query->equals('partof', $partOf));

        $query->setOrderings([
            'mets_orderlabel' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
        ]);

        return $query->execute();
    }

    /**
     * Finds all documents for the given settings
     *
     * @param int $uid
     * @param array $settings
     *
     * @return \Kitodo\Dlf\Domain\Model\Document|null
     */
    public function findOneByIdAndSettings($uid, $settings = [])
    {
        $settings = ['documentSets' => $uid];

        return $this->findDocumentsBySettings($settings)->getFirst();
    }

    /**
     * Finds all documents for the given settings
     *
     * @param array $settings
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findDocumentsBySettings($settings = [])
    {
        $query = $this->createQuery();

        $constraints = [];

        if ($settings['documentSets']) {
            $constraints[] = $query->in('uid', GeneralUtility::intExplode(',', $settings['documentSets']));
        }

        if (isset($settings['excludeOther']) && (int) $settings['excludeOther'] === 0) {
            $query->getQuerySettings()->setRespectStoragePage(false);
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd($constraints)
            );
        }

        return $query->execute();
    }

    /**
     * Finds all documents for the given collections
     *
     * @param array $collections
     * @param int $limit
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllByCollectionsLimited($collections, $limit = 50)
    {
        $query = $this->createQuery();

        // order by start_date -> start_time...
        $query->setOrderings(
            ['tstamp' => QueryInterface::ORDER_DESCENDING]
        );

        $constraints = [];
        if ($collections) {
            $constraints[] = $query->in('collections.uid', $collections);
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd($constraints)
            );
        }

        if ($limit > 0) {
            $query->setLimit((int) $limit);
        }

        return $query->execute();
    }

    /**
     * Find all the titles
     *
     * documents with partof == 0
     *
     * @param array $settings
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllTitles($settings = [])
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->equals('partof', 0);

        if ($settings['collections']) {
            $constraints[] = $query->in('collections.uid', GeneralUtility::intExplode(',', $settings['collections']));
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd($constraints)
            );
        }

        return $query->execute();
    }

    /**
     * Count the titles
     *
     * documents with partof == 0
     *
     * @param array $settings
     *
     * @return int
     */
    public function countAllTitles($settings = [])
    {
        return $this->findAllTitles($settings)->count();
    }

    /**
     * Count the volumes
     *
     * documents with partof != 0
     *
     * @param array $settings
     *
     * @return int
     */
    public function countAllVolumes($settings = [])
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $subQuery = $subQueryBuilder
            ->select('tx_dlf_documents.partof')
            ->from('tx_dlf_documents')
            ->where(
                $subQueryBuilder->expr()->neq('tx_dlf_documents.partof', 0)
            )
            ->groupBy('tx_dlf_documents.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($settings['pages'])),
                $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery)
            )
            ->execute()
            ->fetchColumn(0);

        return $countVolumes;
    }

    public function getStatisticsForSelectedCollection($settings)
    {
        // Include only selected collections.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $countTitles = $queryBuilder
            ->count('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->innerJoin(
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_relations_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_local',
                    'tx_dlf_documents.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_relations_joins',
                'tx_dlf_collections',
                'tx_dlf_collections_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_foreign',
                    'tx_dlf_collections_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', 0),
                $queryBuilder->expr()->in('tx_dlf_collections_join.uid',
                    $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',',
                        $settings['collections']), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident',
                    $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $subQuery = $subQueryBuilder
            ->select('tx_dlf_documents.partof')
            ->from('tx_dlf_documents')
            ->where(
                $subQueryBuilder->expr()->neq('tx_dlf_documents.partof', 0)
            )
            ->groupBy('tx_dlf_documents.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->innerJoin(
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_relations_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_local',
                    'tx_dlf_documents.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_relations_joins',
                'tx_dlf_collections',
                'tx_dlf_collections_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_foreign',
                    'tx_dlf_collections_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($settings['pages'])),
                $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery),
                $queryBuilder->expr()->in('tx_dlf_collections_join.uid',
                    $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',',
                        $settings['collections']), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident',
                    $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);

        return ['titles' => $countTitles, 'volumes' => $countVolumes];
    }

    public function getTableOfContentsFromDb($uid, $pid, $settings)
    {
        // Build table of contents from database.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $excludeOtherWhere = '';
        if ($settings['excludeOther']) {
            $excludeOtherWhere = 'tx_dlf_documents.pid=' . intval($settings['pages']);
        }
        // Check if there are any metadata to suggest.
        $result = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.title AS title',
                'tx_dlf_documents.volume AS volume',
                'tx_dlf_documents.mets_label AS mets_label',
                'tx_dlf_documents.mets_orderlabel AS mets_orderlabel',
                'tx_dlf_structures_join.index_name AS type'
            )
            ->innerJoin(
                'tx_dlf_documents',
                'tx_dlf_structures',
                'tx_dlf_structures_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_structures_join.uid',
                    'tx_dlf_documents.structure'
                )
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($uid)),
                $queryBuilder->expr()->eq('tx_dlf_structures_join.pid', intval($pid)),
                $excludeOtherWhere
            )
            ->addOrderBy('tx_dlf_documents.volume_sorting')
            ->addOrderBy('tx_dlf_documents.mets_orderlabel')
            ->execute();
        return $result;
    }

    /**
     * Find one document by given settings and identifier
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return array The found document object
     */
    public function getOaiRecord($settings, $parameters)
    {
        $where = '';

        if (!$settings['show_userdefined']) {
            $where .= 'AND tx_dlf_collections.fe_cruser_id=0 ';
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_documents');

        $sql = 'SELECT `tx_dlf_documents`.*, GROUP_CONCAT(DISTINCT `tx_dlf_collections`.`oai_name` ORDER BY `tx_dlf_collections`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `tx_dlf_documents` ' .
            'INNER JOIN `tx_dlf_relations` ON `tx_dlf_relations`.`uid_local` = `tx_dlf_documents`.`uid` ' .
            'INNER JOIN `tx_dlf_collections` ON `tx_dlf_collections`.`uid` = `tx_dlf_relations`.`uid_foreign` ' .
            'WHERE `tx_dlf_documents`.`record_id` = ? ' .
            'AND `tx_dlf_relations`.`ident`="docs_colls" ' .
            $where;

        $values = [
            $parameters['identifier']
        ];

        $types = [
            Connection::PARAM_STR
        ];

        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        $statement = $connection->executeQuery($sql, $values, $types);

        return $statement->fetch();
    }

    /**
     * Finds all documents for the given settings
     *
     * @param array $settings
     * @param array $documentsToProcess
     *
     * @return array The found document objects
     */
    public function getOaiDocumentList($settings, $documentsToProcess)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_documents');

        $sql = 'SELECT `tx_dlf_documents`.*, GROUP_CONCAT(DISTINCT `tx_dlf_collections`.`oai_name` ORDER BY `tx_dlf_collections`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `tx_dlf_documents` ' .
            'INNER JOIN `tx_dlf_relations` ON `tx_dlf_relations`.`uid_local` = `tx_dlf_documents`.`uid` ' .
            'INNER JOIN `tx_dlf_collections` ON `tx_dlf_collections`.`uid` = `tx_dlf_relations`.`uid_foreign` ' .
            'WHERE `tx_dlf_documents`.`uid` IN ( ? ) ' .
            'AND `tx_dlf_relations`.`ident`="docs_colls" ' .
            'AND ' . Helper::whereExpression('tx_dlf_collections') . ' ' .
            'GROUP BY `tx_dlf_documents`.`uid` ';

        $values = [
            $documentsToProcess,
        ];

        $types = [
            Connection::PARAM_INT_ARRAY,
        ];

        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        $documents = $connection->executeQuery($sql, $values, $types);

        return $documents;
    }

    /**
     * Finds all documents with given uids
     *
     * @param string $uids separated by comma
     *
     * @return objects
     */
    private function findAllByUids($uids)
    {
        // get all documents from db we are talking about
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_dlf_documents');
        // Fetch document info for UIDs in $documentSet from DB
        $kitodoDocuments = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.title AS title',
                'tx_dlf_documents.structure AS structure',
                'tx_dlf_documents.thumbnail AS thumbnail',
                'tx_dlf_documents.volume_sorting AS volumeSorting',
                'tx_dlf_documents.mets_orderlabel AS metsOrderlabel',
                'tx_dlf_documents.partof AS partOf'
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->in('tx_dlf_documents.pid', $this->settings['storagePid']),
                $queryBuilder->expr()->in('tx_dlf_documents.uid', $uids)
            )
            ->addOrderBy('tx_dlf_documents.volume_sorting', 'asc')
            ->addOrderBy('tx_dlf_documents.mets_orderlabel', 'asc')
            ->execute();

        $allDocuments = [];
        $documentStructures = Helper::getDocumentStructures($this->settings['storagePid']);
        // Process documents in a usable array structure
        while ($resArray = $kitodoDocuments->fetch()) {
            $resArray['structure'] = $documentStructures[$resArray['structure']];
            $allDocuments[$resArray['uid']] = $resArray;
        }

        return $allDocuments;
    }

    /**
     * Find all documents with given collection from Solr
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $collections
     * @param array $settings
     * @param array $searchParams
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $listedMetadata
     * @return array
     */
    public function findSolrByCollection($collections, $settings, $searchParams, $listedMetadata = null) {

        $this->settings = $settings;

        // Instantiate search object.
        $solr = Solr::getInstance($settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return [];
        }

        // Prepare query parameters.
        $params = [];
        $matches = [];
        // Set search query.
        if (
            (!empty($settings['fulltext']) && !empty($searchParams['fulltext']))
            || preg_match('/fulltext:\((.*)\)/', trim($searchParams['query']), $matches)
        ) {
            // If the query already is a fulltext query e.g using the facets
            $searchParams['query'] = empty($matches[1]) ? $searchParams['query'] : $matches[1];
            // Search in fulltext field if applicable. Query must not be empty!
            if (!empty($searchParams['query'])) {
                $query = 'fulltext:(' . Solr::escapeQuery(trim($searchParams['query'])) . ')';
            }
            $params['fulltext'] = true;

        } else {
            // Retain given search field if valid.
            // ABTODO: the storagePid won't be available here
            $query = Solr::escapeQueryKeepField(trim($searchParams['query']), $settings['storagePid']);
        }

        // Set some query parameters.
        $params['query'] = !empty($query) ? $query : '*';
        $params['start'] = 0;
        $params['rows'] = 10000;

        // order the results as given or by title as default
        if (!empty($searchParams['orderBy'])) {
            $querySort = [
                $searchParams['orderBy'] => $searchParams['order']
            ];
        } else {
            $querySort = [
                'year' => 'asc',
                'title' => 'asc'
            ];
        }

        $params['sort'] = $querySort;
        $params['listMetadataRecords'] = [];

        // Restrict the fields to the required ones.
        $params['fields'] = 'uid,id,page,title,thumbnail,partof,toplevel,type';

        if ($listedMetadata) {
            foreach ($listedMetadata as $metadata) {
                if ($metadata->getIndexStored() || $metadata->getIndexIndexed()) {
                    $listMetadataRecord = $metadata->getIndexName() . '_' . ($metadata->getIndexTokenized() ? 't' : 'u') . ($metadata->getIndexStored() ? 's' : 'u') . ($metadata->getIndexIndexed() ? 'i' : 'u');
                    $params['fields'] .= ',' . $listMetadataRecord;
                    $params['listMetadataRecords'][$metadata->getIndexName()] = $listMetadataRecord;
                }
            }
        }

        // Perform search.
        $result = $solr->search_raw($params);

        $numberOfToplevels = 0;
        $documents = [];

        if ($result['numFound'] > 0) {
            // Initialize array
            $documentSet = [];
            // flat array with uids from Solr search
            $documentSet = array_unique(array_column($result['documents'], 'uid'));

            if (empty($documentSet)) {
                // return nothing found
                return ['solrResults' => [], 'documents' => []];
            }

            // get the Extbase document objects for all uids
            $allDocuments = $this->findAllByUids($documentSet);
            $children = $this->findByPartof($documentSet);

            foreach ($result['documents'] as $doc) {
                if (empty($documents[$doc['uid']]) && $allDocuments[$doc['uid']]) {
                    $documents[$doc['uid']] = $allDocuments[$doc['uid']];
                }
                if ($documents[$doc['uid']]) {
                    if ($doc['toplevel'] === false) {
                        // this maybe a chapter, article, ..., year
                        if ($doc['type'] === 'year') {
                            continue;
                        }
                        if (!empty($doc['page'])) {
                            // it's probably a fulltext or metadata search
                            $searchResult = [];
                            $searchResult['page'] = $doc['page'];
                            $searchResult['thumbnail'] = $doc['thumbnail'];
                            $searchResult['structure'] = $doc['type'];
                            $searchResult['title'] = $doc['title'];
                            foreach ($params['listMetadataRecords'] as $indexName => $solrField) {
                                if (isset($doc['metadata'][$indexName])) {
                                    $documents[$doc['uid']]['metadata'][$indexName] = $doc['metadata'][$indexName];
                                }
                            }
                            if ($searchParams['fulltext'] == '1') {
                                $searchResult['snippet'] = $doc['snippet'];
                                $searchResult['highlight'] = $doc['highlight'];
                                $searchResult['highlight_word'] = $searchParams['query'];
                            }
                            $documents[$doc['uid']]['searchResults'][] = $searchResult;
                        }
                    } else if ($doc['toplevel'] === true) {
                        $numberOfToplevels++;
                        foreach ($params['listMetadataRecords'] as $indexName => $solrField) {
                            if (isset($doc['metadata'][$indexName])) {
                                $documents[$doc['uid']]['metadata'][$indexName] = $doc['metadata'][$indexName];
                            }
                        }
                        if ($searchParams['fulltext'] != '1') {
                            $documents[$doc['uid']]['page'] = 1;
                            if (empty($searchParams['query'])) {
                                // find all child documents but not on active search
                                if (is_array($children[$documents[$doc['uid']]['uid']])) {
                                    $documents[$doc['uid']]['children'] = $this->findAllByUids($children[$documents[$doc['uid']]['uid']]);
                                }
                            }
                        }
                    }
                    if (empty($documents[$doc['uid']]['metadata'])) {
                        $documents[$doc['uid']]['metadata'] = $this->fetchMetadataFromSolr($doc['uid'], $settings, $listedMetadata);
                    }
                    // get title of parent if empty
                    if (empty($documents[$doc['uid']]['title']) && ($documents[$doc['uid']]['partOf'] > 0)) {
                        $parentDocument = $this->findByUid($documents[$doc['uid']]['partOf']);
                        if ($parentDocument) {
                            $documents[$doc['uid']]['title'] = $parentDocument->getTitle();
                        }
                    }
                }
            }
        }

        return ['solrResults' => $result, 'numberOfToplevels' => $numberOfToplevels, 'documents' => $documents];
    }

    /**
     * Find all documents with given collection from Solr
     *
     * @param int $uid the uid of the document
     * @param array $settings
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $listedMetadata
     * @return array
     */
    protected function fetchMetadataFromSolr($uid, $settings, $listedMetadata = []) {

        $this->settings = $settings;

        // Instantiate search object.
        $solr = Solr::getInstance($settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return [];
        }

        // Prepare query parameters.
        $params = [];
        $matches = [];

        // Set some query parameters.
        $params['query'] = 'uid:' . $uid;
        $params['start'] = 0;
        $params['rows'] = 1;
        $params['sort'] = ['score' => 'desc'];
        $params['listMetadataRecords'] = [];

        // Restrict the fields to the required ones.
        $params['fields'] = 'uid,toplevel';

        if ($listedMetadata) {
            foreach ($listedMetadata as $metadata) {
                if ($metadata->getIndexIndexed()) {
                    $listMetadataRecord = $metadata->getIndexName() . '_' . ($metadata->getIndexTokenized() ? 't' : 'u') . ($metadata->getIndexStored() ? 's' : 'u') . 'i';
                    $params['fields'] .= ',' . $listMetadataRecord;
                    $params['listMetadataRecords'][$metadata->getIndexName()] = $listMetadataRecord;
                }
            }
        }
        // Set filter query to just get toplevel documents.
        $params['filterquery'][] = ['query' => 'toplevel:true'];

        // Perform search.
        $result = $solr->search_raw($params);

        if ($result['numFound'] > 0) {
            $searchResult = [];
            foreach ($result['documents'] as $doc) {
                foreach ($params['listMetadataRecords'] as $indexName => $solrField) {
                    if (isset($doc['metadata'][$solrField])) {
                        $searchResult[$indexName] = $doc['metadata'][$solrField];
                    }
                }
            }
        }
        return $searchResult;
    }
}
