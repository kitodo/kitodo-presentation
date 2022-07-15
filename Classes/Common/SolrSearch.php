<?php

namespace Kitodo\Dlf\Common;

use Kitodo\Dlf\Common\SolrSearchResult\ResultDocument;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Notes on implementation:
 * - `Countable`: `count()` returns the number of toplevel documents.
 * - `ArrayAccess`/`Iterator`: Access toplevel documents indexed in order of their ranking.
 */
class SolrSearch implements \Countable, \Iterator, \ArrayAccess, QueryResultInterface
{
    protected $result;
    protected $position = 0;

    /**
     *
     * @param DocumentRepository $documentRepository
     * @param QueryResult|Collection $collection
     * @param array $settings
     * @param array $searchParams
     * @param QueryResult $listedMetadata
     */
    public function __construct($documentRepository, $collection, $settings, $searchParams, $listedMetadata = null)
    {
        $this->documentRepository = $documentRepository;
        $this->collection = $collection;
        $this->settings = $settings;
        $this->searchParams = $searchParams;
        $this->listedMetadata = $listedMetadata;
    }

    public function count()
    {
        if ($this->result === null) {
            return 0;
        }

        return count($this->result['document_keys']);
    }

    public function current()
    {
        return $this[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return isset($this[$this->position]);
    }

    public function offsetExists($offset)
    {
        $idx = $this->result['document_keys'][$offset];
        return isset($this->result['documents'][$idx]);
    }

    public function offsetGet($offset)
    {
        $idx = $this->result['document_keys'][$offset];
        $document = $this->result['documents'][$idx] ?? null;

        if ($document !== null) {
            foreach ($document['children'] ?? [] as $childUid => $childDoc) {
                if (empty($document['children'][$childUid]['metadata'])) {
                    $document['children'][$childUid]['metadata'] = $this->fetchMetadataFromSolr($childUid);
                }
            }

            if (empty($document['metadata'])) {
                $document['metadata'] = $this->fetchMetadataFromSolr($document['uid']);
            }

            // get title of parent/grandparent/... if empty
            if (empty($document['title']) && $document['partOf'] > 0) {
                $superiorTitle = Doc::getTitle($document['partOf'], true);
                if (!empty($superiorTitle)) {
                    $document['title'] = '[' . $superiorTitle . ']';
                }
            }
        }

        return $document;
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception("SolrSearch: Modifying result list is not supported");
    }

    public function offsetUnset($offset)
    {
        throw new \Exception("SolrSearch: Modifying result list is not supported");
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getQuery()
    {
        return new SolrSearchQuery($this);
    }

    public function getFirst()
    {
        return $this[0];
    }

    public function toArray()
    {
        return array_values($this->result['documents']);
    }

    public function submit()
    {
        // Prepare query parameters.
        $params = [];
        $matches = [];
        $fields = Solr::getFields();

        // Set search query.
        if (
            (!empty($this->searchParams['fulltext']))
            || preg_match('/' . $fields['fulltext'] . ':\((.*)\)/', trim($this->searchParams['query']), $matches)
        ) {
            // If the query already is a fulltext query e.g using the facets
            $this->searchParams['query'] = empty($matches[1]) ? $this->searchParams['query'] : $matches[1];
            // Search in fulltext field if applicable. Query must not be empty!
            if (!empty($this->searchParams['query'])) {
                $query = $fields['fulltext'] . ':(' . Solr::escapeQuery(trim($this->searchParams['query'])) . ')';
            }
            $params['fulltext'] = true;
        } else {
            // Retain given search field if valid.
            if (!empty($this->searchParams['query'])) {
                $query = Solr::escapeQueryKeepField(trim($this->searchParams['query']), $this->settings['storagePid']);
            }
        }

        // Add extended search query.
        if (
            !empty($this->searchParams['extQuery'])
            && is_array($this->searchParams['extQuery'])
        ) {
            $allowedOperators = ['AND', 'OR', 'NOT'];
            $numberOfExtQueries = count($this->searchParams['extQuery']);
            for ($i = 0; $i < $numberOfExtQueries; $i++) {
                if (!empty($this->searchParams['extQuery'][$i])) {
                    if (
                        in_array($this->searchParams['extOperator'][$i], $allowedOperators)
                    ) {
                        if (!empty($query)) {
                            $query .= ' ' . $this->searchParams['extOperator'][$i] . ' ';
                        }
                        $query .= Indexer::getIndexFieldName($this->searchParams['extField'][$i], $this->settings['storagePid']) . ':(' . Solr::escapeQuery($this->searchParams['extQuery'][$i]) . ')';
                    }
                }
            }
        }

        // Add filter query for faceting.
        if (isset($this->searchParams['fq']) && is_array($this->searchParams['fq'])) {
            foreach ($this->searchParams['fq'] as $filterQuery) {
                $params['filterquery'][]['query'] = $filterQuery;
            }
        }

        // Add filter query for in-document searching.
        if (
            !empty($this->searchParams['documentId'])
            && MathUtility::canBeInterpretedAsInteger($this->searchParams['documentId'])
        ) {
            // Search in document and all subordinates (valid for up to three levels of hierarchy).
            $params['filterquery'][]['query'] = '_query_:"{!join from='
                . $fields['uid'] . ' to=' . $fields['partof'] . '}'
                . $fields['uid'] . ':{!join from=' . $fields['uid'] . ' to=' . $fields['partof'] . '}'
                . $fields['uid'] . ':' . $this->searchParams['documentId'] . '"' . ' OR {!join from='
                . $fields['uid'] . ' to=' . $fields['partof'] . '}'
                . $fields['uid'] . ':' . $this->searchParams['documentId'] . ' OR '
                . $fields['uid'] . ':' . $this->searchParams['documentId'];
        }

        // if a collection is given, we prepare the collection query string
        if ($this->collection) {
            if ($this->collection instanceof Collection) {
                $collectionsQueryString = '"' . $this->collection->getIndexName() . '"';
            } else {
                $collectionsQueryString = '';
                foreach ($this->collection as $index => $collectionEntry) {
                    $collectionsQueryString .= ($index > 0 ? ' OR ' : '') . '"' . $collectionEntry->getIndexName() . '"';
                }
            }

            if (empty($query)) {
                $params['filterquery'][]['query'] = 'toplevel:true';
                $params['filterquery'][]['query'] = 'partof:0';
            }
            $params['filterquery'][]['query'] = 'collection_faceting:(' . $collectionsQueryString . ')';
        }

        // Set some query parameters.
        $params['query'] = !empty($query) ? $query : '*';
        $params['start'] = 0;
        $params['rows'] = 10000;

        // order the results as given or by title as default
        if (!empty($this->searchParams['orderBy'])) {
            $querySort = [
                $this->searchParams['orderBy'] => $this->searchParams['order'],
            ];
        } else {
            $querySort = [
                'year_sorting' => 'asc',
                'title_sorting' => 'asc',
            ];
        }

        $params['sort'] = $querySort;
        $params['listMetadataRecords'] = [];

        // Restrict the fields to the required ones.
        $params['fields'] = 'uid,id,page,title,thumbnail,partof,toplevel,type';

        if ($this->listedMetadata) {
            foreach ($this->listedMetadata as $metadata) {
                if ($metadata->getIndexStored() || $metadata->getIndexIndexed()) {
                    $listMetadataRecord = $metadata->getIndexName() . '_' . ($metadata->getIndexTokenized() ? 't' : 'u') . ($metadata->getIndexStored() ? 's' : 'u') . ($metadata->getIndexIndexed() ? 'i' : 'u');
                    $params['fields'] .= ',' . $listMetadataRecord;
                    $params['listMetadataRecords'][$metadata->getIndexName()] = $listMetadataRecord;
                }
            }
        }

        // Perform search.
        $result = $this->searchSolr($params, true);

        // Initialize values
        $numberOfToplevels = 0;
        $documents = [];

        if ($result['numFound'] > 0) {
            // flat array with uids from Solr search
            $documentSet = array_unique(array_column($result['documents'], 'uid'));

            if (empty($documentSet)) {
                // return nothing found
                $this->result = ['solrResults' => [], 'documents' => [], 'document_keys' => []];
                return;
            }

            // get the Extbase document objects for all uids
            $allDocuments = $this->documentRepository->findAllByUids($documentSet);
            $childrenOf = $this->documentRepository->findChildrenOfEach($documentSet);

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
                                    $searchResult['metadata'][$indexName] = $doc['metadata'][$indexName];
                                }
                            }
                            if ($this->searchParams['fulltext'] == '1') {
                                $searchResult['snippet'] = $doc['snippet'];
                                $searchResult['highlight'] = $doc['highlight'];
                                $searchResult['highlight_word'] = $this->searchParams['query'];
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
                        if ($this->searchParams['fulltext'] != '1') {
                            $documents[$doc['uid']]['page'] = 1;
                            $children = $childrenOf[$doc['uid']] ?? [];
                            foreach ($children as $docChild) {
                                // We need only a few fields from the children, but we need them as array.
                                $childDocument = [
                                    'thumbnail' => $docChild['thumbnail'],
                                    'title' => $docChild['title'],
                                    'structure' => $docChild['structure'],
                                    'metsOrderlabel' => $docChild['metsOrderlabel'],
                                    'uid' => $docChild['uid'],
                                    'metadata' => [],
                                ];
                                $documents[$doc['uid']]['children'][$docChild['uid']] = $childDocument;
                            }
                        }
                    }
                }
            }
        }

        $this->result = ['solrResults' => $result, 'numberOfToplevels' => $numberOfToplevels, 'documents' => $documents, 'document_keys' => array_keys($documents)];
    }

    /**
     * Find all listed metadata for given document
     *
     * @param int $uid the uid of the document
     * @return array
     */
    protected function fetchMetadataFromSolr($uid)
    {
        // Prepare query parameters.
        $params = [];
        $metadataArray = [];

        // Set some query parameters.
        $params['query'] = 'uid:' . $uid;
        $params['start'] = 0;
        $params['rows'] = 1;
        $params['sort'] = ['score' => 'desc'];
        $params['listMetadataRecords'] = [];

        // Restrict the fields to the required ones.
        $params['fields'] = 'uid,toplevel';

        if ($this->listedMetadata) {
            foreach ($this->listedMetadata as $metadata) {
                if ($metadata->getIndexStored() || $metadata->getIndexIndexed()) {
                    $listMetadataRecord = $metadata->getIndexName() . '_' . ($metadata->getIndexTokenized() ? 't' : 'u') . ($metadata->getIndexStored() ? 's' : 'u') . ($metadata->getIndexIndexed() ? 'i' : 'u');
                    $params['fields'] .= ',' . $listMetadataRecord;
                    $params['listMetadataRecords'][$metadata->getIndexName()] = $listMetadataRecord;
                }
            }
        }
        // Set filter query to just get toplevel documents.
        $params['filterquery'][] = ['query' => 'toplevel:true'];

        // Perform search.
        $result = $this->searchSolr($params, true);

        if ($result['numFound'] > 0) {
            // There is only one result found because of toplevel:true.
            if (isset($result['documents'][0]['metadata'])) {
                $metadataArray = $result['documents'][0]['metadata'];
            }
        }
        return $metadataArray;
    }

    /**
     * Processes a search request
     *
     * @access public
     *
     * @param array $parameters: Additional search parameters
     * @param boolean $enableCache: Enable caching of Solr requests
     *
     * @return array The Apache Solr Documents that were fetched
     */
    protected function searchSolr($parameters = [], $enableCache = true)
    {
        // Set additional query parameters.
        $parameters['start'] = 0;
        // Set query.
        $parameters['query'] = isset($parameters['query']) ? $parameters['query'] : '*';
        $parameters['filterquery'] = isset($parameters['filterquery']) ? $parameters['filterquery'] : [];

        // Perform Solr query.
        // Instantiate search object.
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            Helper::log('Apache Solr not available', LOG_SEVERITY_ERROR);
            return [];
        }

        $cacheIdentifier = '';
        $cache = null;
        // Calculate cache identifier.
        if ($enableCache === true) {
            $cacheIdentifier = Helper::digest($solr->core . print_r($parameters, true));
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_dlf_solr');
        }
        $resultSet = [
            'documents' => [],
            'numFound' => 0,
        ];
        if ($enableCache === false || ($entry = $cache->get($cacheIdentifier)) === false) {
            $selectQuery = $solr->service->createSelect($parameters);

            if ($parameters['fulltext'] === true) {
                // get highlighting component and apply settings
                $selectQuery->getHighlighting();
            }

            $solrRequest = $solr->service->createRequest($selectQuery);

            if ($parameters['fulltext'] === true) {
                // If it is a fulltext search, enable highlighting.
                // field for which highlighting is going to be performed,
                // is required if you want to have OCR highlighting
                $solrRequest->addParam('hl.ocr.fl', 'fulltext');
                // return the coordinates of highlighted search as absolute coordinates
                $solrRequest->addParam('hl.ocr.absoluteHighlights', 'on');
                // max amount of snippets for a single page
                $solrRequest->addParam('hl.snippets', 20);
                // we store the fulltext on page level and can disable this option
                $solrRequest->addParam('hl.ocr.trackPages', 'off');
            }

            // Perform search for all documents with the same uid that either fit to the search or marked as toplevel.
            $response = $solr->service->executeRequest($solrRequest);
            $result = $solr->service->createResult($selectQuery, $response);

            /** @scrutinizer ignore-call */
            $resultSet['numFound'] = $result->getNumFound();
            $highlighting = [];
            if ($parameters['fulltext'] === true) {
                $data = $result->getData();
                $highlighting = $data['ocrHighlighting'];
            }
            $fields = Solr::getFields();

            foreach ($result as $record) {
                $resultDocument = new ResultDocument($record, $highlighting, $fields);

                $document = [
                    'id' => $resultDocument->getId(),
                    'page' => $resultDocument->getPage(),
                    'snippet' => $resultDocument->getSnippets(),
                    'thumbnail' => $resultDocument->getThumbnail(),
                    'title' => $resultDocument->getTitle(),
                    'toplevel' => $resultDocument->getToplevel(),
                    'type' => $resultDocument->getType(),
                    'uid' => !empty($resultDocument->getUid()) ? $resultDocument->getUid() : $parameters['uid'],
                    'highlight' => $resultDocument->getHighlightsIds(),
                ];
                foreach ($parameters['listMetadataRecords'] as $indexName => $solrField) {
                    if (!empty($record->$solrField)) {
                        $document['metadata'][$indexName] = $record->$solrField;
                    }
                }
                $resultSet['documents'][] = $document;
            }

            // Save value in cache.
            if (!empty($resultSet) && $enableCache === true) {
                $cache->set($cacheIdentifier, $resultSet);
            }
        } else {
            // Return cache hit.
            $resultSet = $entry;
        }
        return $resultSet;
    }
}
