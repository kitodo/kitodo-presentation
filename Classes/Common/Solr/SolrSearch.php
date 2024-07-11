<?php

namespace Kitodo\Dlf\Common\Solr;

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr\SearchResult\ResultDocument;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Solarium\QueryType\Select\Result\Document;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Targeted towards being used in ``PaginateController`` (``<f:widget.paginate>``).
 *
 * Notes on implementation:
 * - `Countable`: `count()` returns the number of toplevel documents.
 * - `getNumLoadedDocuments()`: Number of toplevel documents that have been fetched from Solr.
 * - `ArrayAccess`/`Iterator`: Access *fetched* toplevel documents indexed in order of their ranking.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SolrSearch implements \Countable, \Iterator, \ArrayAccess, QueryResultInterface
{
    /**
     * @access private
     * @var DocumentRepository
     */
    private DocumentRepository $documentRepository;

    /**
     * @access private
     * @var array|QueryResultInterface
     */
    private $collections;

    /**
     * @access private
     * @var array
     */
    private array $settings;

    /**
     * @access private
     * @var array
     */
    private array $searchParams;

    /**
     * @access private
     * @var QueryResult|null
     */
    private ?QueryResult $listedMetadata;

    /**
     * @access private
     * @var array
     */
    private array $params;

    /**
     * @access private
     * @var array
     */
    private $result;

    /**
     * @access private
     * @var int
     */
    protected int $position = 0;

    /**
     * Constructs SolrSearch instance.
     *
     * @access public
     *
     * @param DocumentRepository $documentRepository
     * @param array|QueryResultInterface $collections can contain 0, 1 or many Collection objects
     * @param array $settings
     * @param array $searchParams
     * @param QueryResult $listedMetadata
     *
     * @return void
     */
    public function __construct(DocumentRepository $documentRepository, $collections, array $settings, array $searchParams, QueryResult $listedMetadata = null)
    {
        $this->documentRepository = $documentRepository;
        $this->collections = $collections;
        $this->settings = $settings;
        $this->searchParams = $searchParams;
        $this->listedMetadata = $listedMetadata;
    }

    /**
     * Gets amount of loaded documents.
     *
     * @access public
     *
     * @return int
     */
    public function getNumLoadedDocuments(): int
    {
        return count($this->result['documents']);
    }

    /**
     * Count results.
     *
     * @access public
     *
     * @return int
     */
    public function count(): int
    {
        if ($this->result === null) {
            return 0;
        }

        return $this->result['numberOfToplevels'];
    }

    /**
     * Current result.
     *
     * @access public
     *
     * @return array
     */
    public function current(): array
    {
        return $this[$this->position];
    }

    /**
     * Current key.
     *
     * @access public
     *
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Next key.
     *
     * @access public
     *
     * @return void
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * First key.
     *
     * @access public
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @access public
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this[$this->position]);
    }

    /**
     * Checks if the document with given offset exists.
     *
     * @access public
     *
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        $idx = $this->result['document_keys'][$offset];
        return isset($this->result['documents'][$idx]);
    }

    /**
     * Gets the document with given offset.
     *
     * @access public
     *
     * @param int $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $idx = $this->result['document_keys'][$offset];
        $document = $this->result['documents'][$idx] ?? null;

        if ($document !== null) {
            // It may happen that a Solr group only includes non-toplevel results,
            // in which case metadata of toplevel entry isn't yet filled.
            if (empty($document['metadata'])) {
                $document['metadata'] = $this->fetchToplevelMetadataFromSolr([
                    'query' => 'uid:' . $document['uid'],
                    'start' => 0,
                    'rows' => 1,
                    'sort' => ['score' => 'desc'],
                ])[$document['uid']] ?? [];
            }

            // get title of parent/grandparent/... if empty
            if (empty($document['title']) && $document['partOf'] > 0) {
                $superiorTitle = AbstractDocument::getTitle($document['partOf'], true);
                if (!empty($superiorTitle)) {
                    $document['title'] = '[' . $superiorTitle . ']';
                }
            }
        }

        return $document;
    }

    /**
     * Not supported.
     *
     * @access public
     *
     * @param int $offset
     * @param int $value
     *
     * @return void
     *
     * @throws \Exception
     */
    public function offsetSet($offset, $value): void
    {
        throw new \Exception("SolrSearch: Modifying result list is not supported");
    }

    /**
     * Not supported.
     *
     * @access public
     *
     * @param int $offset
     *
     * @return void
     *
     * @throws \Exception
     */
    public function offsetUnset($offset): void
    {
        throw new \Exception("SolrSearch: Modifying result list is not supported");
    }

    /**
     * Gets SOLR results.
     *
     * @access public
     *
     * @return mixed
     */
    public function getSolrResults()
    {
        return $this->result['solrResults'];
    }

    /**
     * Gets by UID.
     *
     * @access public
     *
     * @param int $uid
     *
     * @return mixed
     */
    public function getByUid($uid)
    {
        return $this->result['documents'][$uid];
    }

    /**
     * Gets query.
     *
     * @access public
     *
     * @return SolrSearchQuery
     */
    public function getQuery()
    {
        return new SolrSearchQuery($this);
    }

    /**
     * Gets first.
     *
     * @access public
     *
     * @return SolrSearch
     */
    public function getFirst()
    {
        return $this[0];
    }

    /**
     * Parses results to array.
     *
     * @access public
     *
     * @return array
     */
    public function toArray()
    {
        return array_values($this->result['documents']);
    }

    /**
     * Get total number of hits.
     *
     * This can be accessed in Fluid template using `.numFound`.
     *
     * @access public
     *
     * @return int
     */
    public function getNumFound()
    {
        return $this->result['numFound'];
    }

    /**
     * Prepares SOLR search.
     *
     * @access public
     *
     * @return void
     */
    public function prepare()
    {
        // Prepare query parameters.
        $params = [];
        $matches = [];
        $fields = Solr::getFields();
        $query = '';

        // Set search query.
        if (
            !empty($this->searchParams['fulltext'])
            || preg_match('/' . $fields['fulltext'] . ':\((.*)\)/', trim($this->searchParams['query'] ?? ''), $matches)
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

        // Add filter query for date search
        if (!empty($this->searchParams['dateFrom']) && !empty($this->searchParams['dateTo'])) {
            // combine dateFrom and dateTo into range search
            $params['filterquery'][]['query'] = '{!join from=' . $fields['uid'] . ' to=' . $fields['uid'] . '}'. $fields['date'] . ':[' . $this->searchParams['dateFrom'] . ' TO ' . $this->searchParams['dateTo'] . ']';
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

        // if collections are given, we prepare the collection query string
        if (!empty($this->collections)) {
            $params['filterquery'][]['query'] = $this->getCollectionFilterQuery($query);
        }

        // Set some query parameters.
        $params['query'] = !empty($query) ? $query : '*';

        $params['sort'] = $this->getSort();
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

        $this->params = $params;

        // Send off query to get total number of search results in advance
        $this->submit(0, 1, false);
    }

    /**
     * Submits SOLR search.
     *
     * @access public
     *
     * @param int $start
     * @param int $rows
     * @param bool $processResults default value is true
     *
     * @return void
     */
    public function submit($start, $rows, $processResults = true)
    {
        $params = $this->params;
        $params['start'] = $start;
        $params['rows'] = $rows;

        // Perform search.
        $result = $this->searchSolr($params, true);

        // Initialize values
        $documents = [];

        if ($processResults && $result['numFound'] > 0) {
            // flat array with uids from Solr search
            $documentSet = array_unique(array_column($result['documents'], 'uid'));

            if (empty($documentSet)) {
                // return nothing found
                $this->result = ['solrResults' => [], 'documents' => [], 'document_keys' => [], 'numFound' => 0];
                return;
            }

            // get the Extbase document objects for all uids
            $allDocuments = $this->documentRepository->findAllByUids($documentSet);
            $childrenOf = $this->documentRepository->findChildrenOfEach($documentSet);

            foreach ($result['documents'] as $doc) {
                if (empty($documents[$doc['uid']]) && isset($allDocuments[$doc['uid']])) {
                    $documents[$doc['uid']] = $allDocuments[$doc['uid']];
                }
                if (isset($documents[$doc['uid']])) {
                    $this->translateLanguageCode($doc);
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
                                    $searchResult['metadata'][$indexName] = $doc['metadata'][$indexName];
                                }
                            }
                            if ($this->searchParams['fulltext'] == '1') {
                                $searchResult['snippet'] = $doc['snippet'];
                                $searchResult['highlight'] = $doc['highlight'];
                                $searchResult['highlight_word'] = preg_replace('/^;|;$/', '',       // remove ; at beginning or end
                                                                  preg_replace('/;+/', ';',         // replace any multiple of ; with a single ;
                                                                  preg_replace('/[{~\d*}{\s+}{^=*\d+.*\d*}`~!@#$%\^&*()_|+-=?;:\'",.<>\{\}\[\]\\\]/', ';', $this->searchParams['query']))); // replace search operators and special characters with ;
                            }
                            $documents[$doc['uid']]['searchResults'][] = $searchResult;
                        }
                    } else if ($doc['toplevel'] === true) {
                        foreach ($params['listMetadataRecords'] as $indexName => $solrField) {
                            if (isset($doc['metadata'][$indexName])) {
                                $documents[$doc['uid']]['metadata'][$indexName] = $doc['metadata'][$indexName];
                            }
                        }
                        if (!array_key_exists('fulltext', $this->searchParams) || $this->searchParams['fulltext'] != '1') {
                            $documents[$doc['uid']]['page'] = 1;
                            $children = $childrenOf[$doc['uid']] ?? [];
                        
                            if (!empty($children)) {
                                $batchSize = 100;
                                $totalChildren = count($children);
                        
                                for ($start = 0; $start < $totalChildren; $start += $batchSize) {
                                    $batch = array_slice($children, $start, $batchSize, true);
                        
                                    // Fetch metadata for the current batch
                                    $metadataOf = $this->fetchToplevelMetadataFromSolr([
                                        'query' => 'partof:' . $doc['uid'],
                                        'start' => $start,
                                        'rows' => min($batchSize, $totalChildren - $start),
                                    ]);
                        
                                    foreach ($batch as $docChild) {
                                        // We need only a few fields from the children, but we need them as an array.
                                        $childDocument = [
                                            'thumbnail' => $docChild['thumbnail'],
                                            'title' => $docChild['title'],
                                            'structure' => $docChild['structure'],
                                            'metsOrderlabel' => $docChild['metsOrderlabel'],
                                            'uid' => $docChild['uid'],
                                            'metadata' => $metadataOf[$docChild['uid']],
                                        ];
                                        $documents[$doc['uid']]['children'][$docChild['uid']] = $childDocument;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->result = ['solrResults' => $result, 'numberOfToplevels' => $result['numberOfToplevels'], 'documents' => $documents, 'document_keys' => array_keys($documents), 'numFound' => $result['numFound']];
    }

    /**
     * Find all listed metadata using specified query params.
     *
     * @access protected
     *
     * @param array $queryParams
     *
     * @return array
     */
    protected function fetchToplevelMetadataFromSolr(array $queryParams): array
    {
        // Prepare query parameters.
        $params = $queryParams;
        $metadataArray = [];

        // Set some query parameters.
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

        foreach ($result['documents'] as $doc) {
            $this->translateLanguageCode($doc);
            $metadataArray[$doc['uid']] = $doc['metadata'];
        }

        return $metadataArray;
    }

    /**
     * Processes a search request
     *
     * @access protected
     *
     * @param array $parameters Additional search parameters
     * @param boolean $enableCache Enable caching of Solr requests
     *
     * @return array The Apache Solr Documents that were fetched
     */
    protected function searchSolr($parameters = [], $enableCache = true)
    {
        // Set query.
        $parameters['query'] = isset($parameters['query']) ? $parameters['query'] : '*';
        $parameters['filterquery'] = isset($parameters['filterquery']) ? $parameters['filterquery'] : [];

        // Perform Solr query.
        // Instantiate search object.
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            Helper::log('Apache Solr not available', LOG_SEVERITY_ERROR);
            return [
                'documents' => [],
                'numberOfToplevels' => 0,
                'numFound' => 0,
            ];
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
            'numberOfToplevels' => 0,
            'numFound' => 0,
        ];
        if ($enableCache === false || ($entry = $cache->get($cacheIdentifier)) === false) {
            $selectQuery = $solr->service->createSelect($parameters);

            $grouping = $selectQuery->getGrouping();
            $grouping->addField('uid');
            $grouping->setLimit(100); // Results in group (TODO: check)
            $grouping->setNumberOfGroups(true);

            $fulltextExists = $parameters['fulltext'] ?? false;
            if ($fulltextExists === true) {
                // get highlighting component and apply settings
                $selectQuery->getHighlighting();
            }

            $solrRequest = $solr->service->createRequest($selectQuery);

            if ($fulltextExists === true) {
                // If it is a fulltext search, enable highlighting.
                // field for which highlighting is going to be performed,
                // is required if you want to have OCR highlighting
                $solrRequest->addParam('hl.ocr.fl', 'fulltext');
                // return the coordinates of highlighted search as absolute coordinates
                $solrRequest->addParam('hl.ocr.absoluteHighlights', 'on');
                // max amount of snippets for a single page
                $solrRequest->addParam('hl.snippets', '20');
                // we store the fulltext on page level and can disable this option
                $solrRequest->addParam('hl.ocr.trackPages', 'off');
            }

            // Perform search for all documents with the same uid that either fit to the search or marked as toplevel.
            $response = $solr->service->executeRequest($solrRequest);
            // return empty resultSet on error-response
            if ($response->getStatusCode() == 400) {
                return $resultSet;
            }
            $result = $solr->service->createResult($selectQuery, $response);

            // TODO: Call to an undefined method Solarium\Core\Query\Result\ResultInterface::getGrouping().
            // @phpstan-ignore-next-line
            $uidGroup = $result->getGrouping()->getGroup('uid');
            $resultSet['numberOfToplevels'] = $uidGroup->getNumberOfGroups();
            $resultSet['numFound'] = $uidGroup->getMatches();
            $highlighting = [];
            if ($fulltextExists === true) {
                $data = $result->getData();
                $highlighting = $data['ocrHighlighting'];
            }
            $fields = Solr::getFields();

            foreach ($uidGroup as $group) {
                foreach ($group as $record) {
                    $resultSet['documents'][] = $this->getDocument($record, $highlighting, $fields, $parameters);
                }
            }

            // Save value in cache.
            if (!empty($resultSet['documents']) && $enableCache === true) {
                $cache->set($cacheIdentifier, $resultSet);
            }
        } else {
            // Return cache hit.
            $resultSet = $entry;
        }
        return $resultSet;
    }

    /**
     * Get collection filter query for search.
     *
     * @access private
     *
     * @param string $query
     *
     * @return string
     */
    private function getCollectionFilterQuery(string $query) : string
    {
        $collectionsQueryString = '';
        $virtualCollectionsQueryString = '';
        foreach ($this->collections as $collection) {
            // check for virtual collections query string
            if ($collection->getIndexSearch()) {
                $virtualCollectionsQueryString .= empty($virtualCollectionsQueryString) ? '(' . $collection->getIndexSearch() . ')' : ' OR (' . $collection->getIndexSearch() . ')';
            } else {
                $collectionsQueryString .= empty($collectionsQueryString) ? '"' . $collection->getIndexName() . '"' : ' OR "' . $collection->getIndexName() . '"';
            }
        }

        // distinguish between simple collection browsing and actual searching within the collection(s)
        if (!empty($collectionsQueryString)) {
            if (empty($query)) {
                $collectionsQueryString = '(collection_faceting:(' . $collectionsQueryString . ') AND toplevel:true AND partof:0)';
            } else {
                $collectionsQueryString = '(collection_faceting:(' . $collectionsQueryString . '))';
            }
        }

        // virtual collections might query documents that are neither toplevel:true nor partof:0 and need to be searched separately
        if (!empty($virtualCollectionsQueryString)) {
            $virtualCollectionsQueryString = '(' . $virtualCollectionsQueryString . ')';
        }

        // combine both query strings into a single filterquery via OR if both are given, otherwise pass either of those
        return implode(' OR ', array_filter([$collectionsQueryString, $virtualCollectionsQueryString]));
    }

    /**
     * Get sort order of the results as given or by title as default.
     *
     * @access private
     *
     * @return array
     */
    private function getSort() : array
    {
        if (!empty($this->searchParams['orderBy'])) {
            return [
                $this->searchParams['orderBy'] => $this->searchParams['order'],
            ];
        }

        return [
            'score' => 'desc',
            'year_sorting' => 'asc',
            'title_sorting' => 'asc',
            'volume' => 'asc'
        ];
    }

    /**
     * Gets a document
     *
     * @access private
     *
     * @param Document $record
     * @param array $highlighting
     * @param array $fields
     * @param array $parameters
     *
     * @return array The Apache Solr Documents that were fetched
     */
    private function getDocument(Document $record, array $highlighting, array $fields, $parameters) {
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

        return $document;
    }

    /**
     * Translate language code if applicable.
     *
     * @access private
     *
     * @param &$doc document array
     *
     * @return void
     */
    private function translateLanguageCode(&$doc): void
    {
        if (array_key_exists('language', $doc['metadata'])) {
            foreach($doc['metadata']['language'] as $indexName => $language) {
                $doc['metadata']['language'][$indexName] = Helper::getLanguageName($language);
            }
        }
    }
}
