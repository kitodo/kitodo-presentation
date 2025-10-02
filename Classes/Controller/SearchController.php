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

namespace Kitodo\Dlf\Controller;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\SolrPaginator;
use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Psr\Http\Message\ResponseInterface;
use Solarium\Component\Result\FacetSet;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller class for the plugin 'Search'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SearchController extends AbstractController
{
    /**
     * @access protected
     * @var CollectionRepository
     */
    protected CollectionRepository $collectionRepository;

    /**
     * @access public
     *
     * @param CollectionRepository $collectionRepository
     *
     * @return void
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository): void
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @access protected
     * @var MetadataRepository
     */
    protected MetadataRepository $metadataRepository;

    /**
     * @access public
     *
     * @param MetadataRepository $metadataRepository
     *
     * @return void
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository): void
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @access protected
     * @var array of the current search parameters
     */
    protected ?array $search;

    /**
     * Search Action
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function searchAction(): ResponseInterface
    {
        // if search was triggered, get search parameters from POST variables
        $this->search = $this->getParametersSafely('search', ['tx_dlf_collection', 'tx_dlf_listview']);

        // output is done by main action
        return $this->redirect('main', null, null, ['search' => $this->search]);
    }

    /**
     * Main action
     *
     * This shows the search form and optional the facets and extended search form.
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        $listViewSearch = false;
        // Quit without doing anything if required variables are not set.
        if (empty($this->settings['solrcore'])) {
            $this->logger->warning('Incomplete plugin configuration');
            return $this->htmlResponse();
        }

        $this->addFieldsForExtendedSearch();

        $this->enableSuggester();

        // if search was triggered, get search parameters from POST variables
        $this->search = $this->getParametersSafely('search');

        // if search was triggered by the ListView plugin, get the parameters from GET variables
        $listRequestData = $this->request->getQueryParams()['tx_dlf_listview'] ?? null;
        // Quit without doing anything if no search parameters.
        if (empty($this->search) && empty($listRequestData)) {
            $this->logger->warning('Missing search parameters');
            return $this->htmlResponse();
        }

        if (isset($listRequestData['search']) && is_array($listRequestData['search'])) {
            $this->search = array_merge($this->search ?: [], $listRequestData['search']);
            $listViewSearch = true;
            $this->request->getAttribute('frontend.user')->setKey('ses', 'search', $this->search);
        }

        $this->search = is_array($this->search) ? $this->search : [];

        // sanitize date search input
        if (array_key_exists('dateFrom', $this->search) || array_key_exists('dateTo', $this->search)) {
            if (!array_key_exists('dateFrom', $this->search) && array_key_exists('dateTo', $this->search)) {
                $this->search['dateFrom'] = '*';
            }

            if (!array_key_exists('dateTo', $this->search) && array_key_exists('dateFrom', $this->search)) {
                $this->search['dateTo'] = 'NOW';
            }

            if ($this->search['dateFrom'] > $this->search['dateTo']) {
                $tmpDate = $this->search['dateFrom'];
                $this->search['dateFrom'] = $this->search['dateTo'];
                $this->search['dateTo'] = $tmpDate;
            }
        }

        // Pagination of Results: Pass the currentPage to the fluid template to calculate current index of search result.
        $currentPage = $this->getParametersSafely('page');
        if (empty($currentPage)) {
            $currentPage = 1;
        }

        // If a targetPid is given, the results will be shown by ListView on the target page.
        if (!empty($this->settings['targetPid']) && !empty($this->search) && !$listViewSearch) {
            return $this->redirect(
                'main',
                'ListView',
                null,
                [
                    'search' => $this->search,
                    'page' => $currentPage
                ], $this->settings['targetPid']
            );
        }

        // If no search has been executed, no variables have to be prepared.
        // An empty form will be shown.
        if (!empty($this->search)) {
            // get all sortable metadata records
            $sortableMetadata = $this->metadataRepository->findBy(['isSortable' => true]);

            // get all metadata records to be shown in results
            $listedMetadata = $this->metadataRepository->findBy(['isListed' => true]);

            // get all indexed metadata fields
            $indexedMetadata = $this->metadataRepository->findBy(['indexIndexed' => true]);

            $solrResults = null;
            $numResults = 0;
            // Do not execute the Solr search if used together with ListView plugin.
            if (!$listViewSearch) {
                $solrResults = $this->documentRepository->findSolrWithoutCollection($this->settings, $this->search, $listedMetadata, $indexedMetadata);
                $numResults = $solrResults->getNumFound();

                $itemsPerPage = $this->settings['list']['paginate']['itemsPerPage'] ?? 25;
                $solrPaginator = new SolrPaginator($solrResults, $currentPage, $itemsPerPage);
                $simplePagination = new SimplePagination($solrPaginator);

                $pagination = $this->buildSimplePagination($simplePagination, $solrPaginator);
                $this->view->assignMultiple([ 'pagination' => $pagination, 'paginator' => $solrPaginator ]);
            }

            $this->view->assign('documents', !empty($solrResults) ? $solrResults : []);
            $this->view->assign('numResults', $numResults);
            $this->view->assign('page', $currentPage);
            $this->view->assign('lastSearch', $this->search);
            $this->view->assign('listedMetadata', $listedMetadata);
            $this->view->assign('sortableMetadata', $sortableMetadata);

            // Add the facets menu
            $this->addFacetsMenu();
        }

        // Add the current document if present to fluid. This way, we can limit further searches to this document.
        if (isset($this->requestData['id'])) {
            $currentDocument = $this->documentRepository->findByUid($this->requestData['id']);
            $this->view->assign('currentDocument', $currentDocument);
        }

        $this->view->assign('viewData', $this->viewData);

        return $this->htmlResponse();
    }

    /**
     * Adds the facets menu to the search form
     *
     * @access protected
     */
    protected function addFacetsMenu(): void
    {
        // Quit without doing anything if no facets are configured.
        if (empty($this->settings['facets']) && empty($this->settings['facetCollections'])) {
            return;
        }

        // Get facets from plugin configuration.
        $facets = [];
        foreach (GeneralUtility::trimExplode(',', $this->settings['facets'], true) as $facet) {
            $facets[$facet . '_faceting'] = Helper::translate($facet, 'tx_dlf_metadata', $this->settings['storagePid']);
        }

        $this->view->assign('facetsMenu', $this->makeFacetsMenuArray($facets));
    }

    /**
     * This builds a menu array for HMENU
     *
     * @access private
     *
     * @param array $facets
     *
     * @return array HMENU array
     */
    private function makeFacetsMenuArray(array $facets): array
    {
        // Set default value for facet search.
        $search = [
            'query' => '*:*',
            'params' => [
                'component' => [
                    'facetset' => [
                        'facet' => []
                    ]
                ],
                'filterquery' => []
            ]
        ];

        $fields = Solr::getFields();

        // Set search query.
        $searchParams = $this->search;
        if (
            (array_key_exists('fulltext', $searchParams))
            || preg_match('/' . $fields['fulltext'] . ':\((.*)\)/', trim($searchParams['query'] ?? ''), $matches)
        ) {
            // If the query already is a fulltext query e.g using the facets
            $searchParams['query'] = empty($matches[1]) ? $searchParams['query'] : $matches[1];
            // Search in fulltext field if applicable. Query must not be empty!
            if (!empty($searchParams['query'])) {
                $search['query'] = $fields['fulltext'] . ':(' . Solr::escapeQuery(trim($searchParams['query'])) . ')';
            }
        } else {
            $search['params']['filterquery'][]['query'] = '-type:page';
            // Retain given search field if valid.
            if (!empty($searchParams['query'])) {
                $search['query'] = Solr::escapeQueryKeepField(trim($searchParams['query']), $this->settings['storagePid']);
            }
        }

        $collectionsQuery = $this->addCollectionsQuery($search['query']);
        if (!empty($collectionsQuery)) {
            $search['params']['filterquery'][]['query'] = $collectionsQuery;
        }

        // add filter query for date search
        if (array_key_exists('dateFrom', $this->search) && array_key_exists('dateTo', $this->search)) {
            // combine dateFrom and dateTo into filterquery as range search
            $search['params']['filterquery'][]['query'] = '{!join from=' . $fields['uid'] . ' to=' . $fields['uid'] . '}' . $fields['date'] . ':[' . $this->search['dateFrom'] . ' TO ' . $this->search['dateTo'] . ']';
        }

        // Add extended search query.
        if (
            !empty($searchParams['extQuery'])
            && is_array($searchParams['extQuery'])
        ) {
            // If the search query is already set by the simple search field, we have to reset it.
            $search['query'] = '';
            $allowedOperators = ['AND', 'OR', 'NOT'];
            $numberOfExtQueries = count($searchParams['extQuery']);
            for ($i = 0; $i < $numberOfExtQueries; $i++) {
                if (!empty($searchParams['extQuery'][$i])) {
                    if (
                        in_array($searchParams['extOperator'][$i], $allowedOperators)
                    ) {
                        if (!empty($search['query'])) {
                            $search['query'] .= ' ' . $searchParams['extOperator'][$i] . ' ';
                        }
                        $search['query'] .= Indexer::getIndexFieldName($searchParams['extField'][$i], $this->settings['storagePid']) . ':(' . Solr::escapeQuery($searchParams['extQuery'][$i]) . ')';
                    }
                }
            }
        }

        if (array_key_exists('fq', $this->search) && is_array($this->search['fq'])) {
            foreach ($this->search['fq'] as $fq) {
                $search['params']['filterquery'][]['query'] = $fq;
            }
        }

        // Get applicable facets.
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return [];
        }

        foreach (array_keys($facets) as $field) {
            $search['params']['component']['facetset']['facet'][$field] = [
                'type' => 'field',
                'mincount' => '1',
                'key' => $field,
                'field' => $field,
                'limit' => $this->settings['limitFacets'] ?? 15,
                'sort' => $this->settings['sortingFacets'] ?? 'count'
            ];
        }

        // Set additional query parameters.
        $search['params']['start'] = 0;
        $search['params']['rows'] = 0;
        // Set query.
        $search['params']['query'] = $search['query'];
        // Perform search.
        $selectQuery = $solr->service->createSelect($search['params']);
        // check for solr response
        $solrRequest = $solr->service->createRequest($selectQuery);
        $response = $solr->service->executeRequest($solrRequest);
        // return empty facet on solr error
        if ($response->getStatusCode() == 400) {
            return [];
        }
        $results = $solr->service->select($selectQuery);
        $facet = $results->getFacetSet();

        $facetCollectionArray = [];

        // replace everything expect numbers and comma
        $facetCollections = preg_replace('/[^\d,]/', '', $this->settings['facetCollections']);

        if (!empty($facetCollections)) {
            $collections = $this->collectionRepository->findCollectionsBySettings(['collections' => $facetCollections]);

            /** @var Collection $collection */
            foreach ($collections as $collection) {
                $facetCollectionArray[] = $collection->getIndexName();
            }
        }

        return $this->processResults($facet, $facetCollectionArray, $search);
    }

    /**
     * Add the collection query string, if the collections are given.
     *
     * @access private
     *
     * @param string $query The current query
     *
     * @return string The collection query string
     */
    private function addCollectionsQuery(string $query): string
    {
        // if collections are given, we prepare the collections query string
        // extract collections from collection parameter
        $collections = null;
        if (array_key_exists('collection', $this->search)) {
            foreach (explode(',', $this->search['collection']) as $collectionEntry) {
                if (!empty($collectionEntry)) {
                    $collections[] = $this->collectionRepository->findByUid((int) $collectionEntry);
                }
            }
        }
        if ($collections) {
            $collectionsQueryString = '';
            $virtualCollectionsQueryString = '';
            foreach ($collections as $collectionEntry) {
                // check for virtual collections query string
                /** @var Collection $collectionEntry */
                if ($collectionEntry->getIndexSearch()) {
                    $virtualCollectionsQueryString .= empty($virtualCollectionsQueryString) ? '(' . $collectionEntry->getIndexSearch() . ')' : ' OR (' . $collectionEntry->getIndexSearch() . ')';
                } else {
                    $collectionsQueryString .= empty($collectionsQueryString) ? '"' . $collectionEntry->getIndexName() . '"' : ' OR "' . $collectionEntry->getIndexName() . '"';
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

            // combine both querystrings into a single filterquery via OR if both are given, otherwise pass either of those
            return implode(" OR ", array_filter([$collectionsQueryString, $virtualCollectionsQueryString]));
        }
        return "";
    }

    /**
     * Creates an array for a HMENU entry of a facet value.
     *
     * @access private
     *
     * @param string $field The facet's index_name
     * @param string $value The facet's value
     * @param int $count Number of hits for this facet
     * @param array $search The parameters of the current search query
     * @param string &$state The state of the parent item
     *
     * @return array The array for the facet's menu entry
     */
    private function getFacetsMenuEntry(string $field, string $value, int $count, array $search, string &$state): array
    {
        $entryArray = [
            'title' => $this->translateValue($field, $value),
            'count' => $count,
            'doNotLinkIt' => 0
        ];

        // Check if facet is already selected.
        $queryColumn = array_column($search['params']['filterquery'], 'query');
        $index = array_search($field . ':("' . Solr::escapeQuery($value) . '")', $queryColumn);
        if ($index !== false) {
            // Facet is selected, thus remove it from filter.
            unset($queryColumn[$index]);
            $queryColumn = array_values($queryColumn);
            $entryArray['ITEM_STATE'] = 'CUR';
            $state = 'ACTIFSUB';
            // Reset facets
            if ($this->settings['resetFacets']) {
                $entryArray['resetFacet'] = true;
            }
        } else {
            // Facet is not selected, thus add it to filter.
            $queryColumn[] = $field . ':("' . Solr::escapeQuery($value) . '")';
            $entryArray['ITEM_STATE'] = 'NO';
        }
        $entryArray['queryColumn'] = $queryColumn;

        return $entryArray;
    }

    /**
     * Process results.
     *
     * @access private
     *
     * @param FacetSet|null $facet
     * @param array $facetCollectionArray
     * @param array $search
     *
     * @return array menu array
     */
    private function processResults($facet, array $facetCollectionArray, array $search): array
    {
        $menuArray = [];

        if ($facet) {
            foreach ($facet as $field => $values) {
                $entryArray = [];
                $entryArray['field'] = substr($field, 0, strpos($field, '_faceting'));
                $entryArray['count'] = 0;
                $entryArray['_OVERRIDE_HREF'] = '';
                $entryArray['ITEM_STATE'] = 'NO';
                // Count number of facet values.
                $i = 0;
                foreach ($values as $value => $count) {
                    if ($count > 0) {
                        // check if facet collection configuration exists
                        if (!empty($this->settings['facetCollections'])) {
                            if ($field == "collection_faceting" && !in_array($value, $facetCollectionArray)) {
                                continue;
                            }
                        }
                        $entryArray['count']++;
                        if ($entryArray['ITEM_STATE'] == 'NO') {
                            $entryArray['ITEM_STATE'] = 'IFSUB';
                        }
                        $entryArray['_SUB_MENU'][] = $this->getFacetsMenuEntry($field, $value, $count, $search, $entryArray['ITEM_STATE']);
                        if (++$i == ($this->settings['limitFacets'] ?? 15)) {
                            break;
                        }
                    } else {
                        break;
                    }
                }
                $menuArray[] = $entryArray;
            }
        }
        return $menuArray;
    }

    /**
     * Translates value depending on the index name.
     *
     * @access private
     *
     * @param string $field The facet's index_name
     * @param string $value The facet's value
     *
     * @return string
     */
    private function translateValue(string $field, string $value): string
    {
        return match ($field) {
            'owner_faceting' => htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->settings['storagePid'])),
            'type_faceting' => htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->settings['storagePid'])),
            'collection_faceting' => htmlspecialchars(Helper::translate($value, 'tx_dlf_collections', $this->settings['storagePid'])),
            'language_faceting' => htmlspecialchars(Helper::getLanguageName($value)),
            default => htmlspecialchars($value)
        };
    }

    /**
     * Adds the fields necessary for extended search.
     *
     * @access private
     *
     * @return void
     */
    private function addFieldsForExtendedSearch(): void
    {
        // Quit without doing anything if no fields for extended search are selected.
        if (
            empty($this->settings['extendedSlotCount'])
            || empty($this->settings['extendedFields'])
        ) {
            return;
        }

        // Get field selector options.
        $searchFields = GeneralUtility::trimExplode(',', $this->settings['extendedFields'], true);
        $extendedSlotCount = range(0, (int) $this->settings['extendedSlotCount'] - 1);

        $this->view->assign('extendedSlotCount', $extendedSlotCount);
        $this->view->assign('operators', ['AND', 'OR', 'NOT']);
        $this->view->assign('searchFields', $searchFields);
    }

    /**
     * Enables suggester if setting is set.
     *
     * @access private
     *
     * @return void
     */
    private function enableSuggester(): void
    {
        // Add uHash parameter to suggest parameter to make a basic protection of this form.
        if ($this->settings['suggest'] ?? true) {
            $this->view->assign('uHash', GeneralUtility::hmac((string) (new Typo3Version()) . Environment::getExtensionsPath(), 'SearchSuggest'));
        }
    }
}
