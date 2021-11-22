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

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Domain\Model\Collection;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;

class SearchController extends AbstractController
{
    public $prefixId = 'tx_dlf';
    public $extKey = 'dlf';

    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @param CollectionRepository $collectionRepository
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * Search action
     */
    public function searchAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);

        // Build label for result list.
        $label = htmlspecialchars(LocalizationUtility::translate('search.search', 'dlf'));
        if (!empty($requestData['query'])) {
            $label .= ' ' . htmlspecialchars(sprintf(LocalizationUtility::translate('search.for', 'dlf'), trim($requestData['query'])));
        }
        // Prepare query parameters.
        $params = [];
        $matches = [];
        // Set search query.
        if (
            (!empty($this->settings['fulltext']) && !empty($requestData['fulltext']))
            || preg_match('/fulltext:\((.*)\)/', trim($requestData['query']), $matches)
        ) {
            // If the query already is a fulltext query e.g using the facets
            $requestData['query'] = empty($matches[1]) ? $requestData['query'] : $matches[1];
            // Search in fulltext field if applicable. Query must not be empty!
            if (!empty($requestData['query'])) {
                $query = 'fulltext:(' . Solr::escapeQuery(trim($requestData['query'])) . ')';
            }
        } else {
            // Retain given search field if valid.
            $query = Solr::escapeQueryKeepField(trim($requestData['query']), $this->settings['pages']);
        }
        // Add extended search query.
        if (
            !empty($requestData['extQuery'])
            && is_array($requestData['extQuery'])
        ) {
            $allowedOperators = ['AND', 'OR', 'NOT'];
            $allowedFields = GeneralUtility::trimExplode(',', $this->settings['extendedFields'], true);
            $numberOfExtQueries = count($requestData['extQuery']);
            for ($i = 0; $i < $numberOfExtQueries; $i++) {
                if (!empty($requestData['extQuery'][$i])) {
                    if (
                        in_array($requestData['extOperator'][$i], $allowedOperators)
                        && in_array($requestData['extField'][$i], $allowedFields)
                    ) {
                        if (!empty($query)) {
                            $query .= ' ' . $requestData['extOperator'][$i] . ' ';
                        }
                        $query .= Indexer::getIndexFieldName($requestData['extField'][$i], $this->settings['pages']) . ':(' . Solr::escapeQuery($requestData['extQuery'][$i]) . ')';
                    }
                }
            }
        }
        // Add filter query for faceting.
        if (!empty($requestData['fq'])) {
            foreach ($requestData['fq'] as $filterQuery) {
                $params['filterquery'][]['query'] = $filterQuery;
            }
        }

        // Add filter query for in-document searching.
        if (
            $this->settings['searchIn'] == 'document'
            || $this->settings['searchIn'] == 'all'
        ) {
            if (
                !empty($requestData['id'])
                && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($requestData['id'])
            ) {
                // Search in document and all subordinates (valid for up to three levels of hierarchy).
                $params['filterquery'][]['query'] = '_query_:"{!join from=uid to=partof}uid:{!join from=uid to=partof}uid:' . $requestData['id'] . '"' .
                    ' OR {!join from=uid to=partof}uid:' . $requestData['id'] .
                    ' OR uid:' . $requestData['id'];
                $label .= ' ' . htmlspecialchars(sprintf(LocalizationUtility::translate('search.in', 'dlf'), Document::getTitle($requestData['id'])));
            }
        }
        // Add filter query for in-collection searching.
        if (
            $this->settings['searchIn'] == 'collection'
            || $this->settings['searchIn'] == 'all'
        ) {
            if (
                !empty($requestData['collection'])
                && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($requestData['collection'])
            ) {
                $index_name = Helper::getIndexNameFromUid($requestData['collection'], 'tx_dlf_collections', $this->settings['pages']);
                $params['filterquery'][]['query'] = 'collection_faceting:("' . Solr::escapeQuery($index_name) . '")';
                $label .= ' ' . sprintf(LocalizationUtility::translate('search.in', 'dlf'), Helper::translate($index_name, 'tx_dlf_collections', $this->settings['pages']));
            }
        }
        // Add filter query for collection restrictions.
        if ($this->settings['collections']) {
            $collIds = explode(',', $this->settings['collections']);
            $collIndexNames = [];
            foreach ($collIds as $collId) {
                $collIndexNames[] = Solr::escapeQuery(Helper::getIndexNameFromUid(intval($collId), 'tx_dlf_collections', $this->settings['pages']));
            }
            // Last value is fake and used for distinction in $this->addCurrentCollection()
            $params['filterquery'][]['query'] = 'collection_faceting:("' . implode('" OR "', $collIndexNames) . '" OR "FakeValueForDistinction")';
        }
        // Set some query parameters.
        $params['query'] = !empty($query) ? $query : '*';
        $params['start'] = 0;
        $params['rows'] = 0;
        $params['sort'] = ['score' => 'desc'];
        // Instantiate search object.
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            $this->redirect('main', 'Search', null);
            //return $this->responseFactory->createHtmlResponse($this->view->render());
        }
        // Set search parameters.
        $solr->cPid = $this->settings['pages'];
        $solr->params = $params;
        // Perform search.
        $list = $solr->search();
        $list->metadata = [
            'label' => $label,
            'thumbnail' => '',
            'searchString' => $requestData['query'],
            'fulltextSearch' => (!empty($requestData['fulltext']) ? '1' : '0'),
            'options' => $list->metadata['options']
        ];
        $list->save();
        // Clean output buffer.
        ob_end_clean();
        $additionalParams = [];
        if (!empty($requestData['logicalPage'])) {
            $additionalParams['logicalPage'] = $requestData['logicalPage'];
        }
        // Jump directly to the page view, if there is only one result and it is configured
        if ($list->metadata['options']['numberOfHits'] == 1 && !empty($this->settings['showSingleResult'])) {
            $linkConf['parameter'] = $this->settings['targetPidPageView'];
            $additionalParams['id'] = $list->current()['uid'];
            $additionalParams['highlight_word'] = preg_replace('/\s\s+/', ';', $list->metadata['searchString']);
            $additionalParams['page'] = count($list[0]['subparts']) == 1 ? $list[0]['subparts'][0]['page'] : 1;
        } else {
            // Keep some plugin variables.
            $linkConf['parameter'] = $this->settings['targetPid'];
            if (!empty($requestData['order'])) {
                $additionalParams['order'] = $requestData['order'];
                $additionalParams['asc'] = !empty($requestData['asc']) ? '1' : '0';
            }
        }
        $linkConf['forceAbsoluteUrl'] = !empty($this->settings['forceAbsoluteUrl']) ? 1 : 0;
        $linkConf['forceAbsoluteUrl.']['scheme'] = !empty($this->settings['forceAbsoluteUrl']) && !empty($this->settings['forceAbsoluteUrlHttps']) ? 'https' : 'http';
        $linkConf['additionalParams'] = GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', true, false);

        $this->redirect('main', 'Search', null, null);
    }

    /**
     *
     * @return mixed
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        // Quit without doing anything if required variables are not set.
        if (empty($this->settings['solrcore'])) {
            $this->logger->warning('Incomplete plugin configuration');
            return '';
        }
        if (!isset($requestData['query'])
            && empty($requestData['extQuery'])
        ) {
            // Extract query and filter from last search.
            $list = GeneralUtility::makeInstance(DocumentList::class);
            if (!empty($list->metadata['searchString'])) {
                if ($list->metadata['options']['source'] == 'search') {
                    $search['query'] = $list->metadata['searchString'];
                }
                $search['params'] = $list->metadata['options']['params'];
            }

            $this->view->assign('QUERY', (!empty($search['query']) ? htmlspecialchars($search['query']) : ''));
            $this->view->assign('FULLTEXT_SEARCH', $list->metadata['fulltextSearch']);
        } else {
            $this->view->assign('QUERY', (!empty($requestData['query']) ? htmlspecialchars($requestData['query']) : ''));
            $this->view->assign('FULLTEXT_SEARCH', $requestData['fulltext']);
        }

        $this->view->assign('FACETS_MENU', $this->addFacetsMenu());

        $this->addEncryptedCoreName();

        if ($this->settings['searchIn'] == 'collection' || $this->settings['searchIn'] == 'all') {
            $this->addCurrentCollection();
        }
        if ($this->settings['searchIn'] == 'document' || $this->settings['searchIn'] == 'all') {
            $this->addCurrentDocument($requestData);
        }

        // Get additional fields for extended search.
        $this->addExtendedSearch();
    }

    /**
     * Adds the current document's UID or parent ID to the search form
     *
     * @access protected
     *
     * @return string HTML input fields with current document's UID
     */
    protected function addCurrentDocument($requestData)
    {
        // Load current list object.
        $list = GeneralUtility::makeInstance(DocumentList::class);
        // Load current document.
        if (
            !empty($requestData['id'])
            && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($requestData['id'])
        ) {
            $this->loadDocument($requestData);
            // Get document's UID
            if ($this->doc->ready) {
                $this->view->assign('DOCUMENT_ID', $this->doc->uid);
            }
        } elseif (!empty($list->metadata['options']['params']['filterquery'])) {
            // Get document's UID from search metadata.
            // The string may be e.g. "{!join from=uid to=partof}uid:{!join from=uid to=partof}uid:2" OR {!join from=uid to=partof}uid:2 OR uid:2"
            // or "collection_faceting:("Some Collection Title")"
            foreach ($list->metadata['options']['params']['filterquery'] as $facet) {
                if (($lastUidPos = strrpos($facet['query'], 'uid:')) !== false) {
                    $facetKeyVal = explode(':', substr($facet['query'], $lastUidPos));
                    if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($facetKeyVal[1])) {
                        $documentId = (int) $facetKeyVal[1];
                    }
                }
            }
            if (!empty($documentId)) {
                $this->view->assign('DOCUMENT_ID', $documentId);
            }
        }
    }


    /**
     * Adds the current collection's UID to the search form
     *
     * @access protected
     *
     * @return string HTML input fields with current document's UID and parent ID
     */
    protected function addCurrentCollection()
    {
        // Load current collection.
        $list = GeneralUtility::makeInstance(DocumentList::class);
        if (
            !empty($list->metadata['options']['source'])
            && $list->metadata['options']['source'] == 'collection'
        ) {
            $this->view->assign('COLLECTION_ID', $list->metadata['options']['select']);
            // Get collection's UID.
        } elseif (!empty($list->metadata['options']['params']['filterquery'])) {
            // Get collection's UID from search metadata.
            foreach ($list->metadata['options']['params']['filterquery'] as $facet) {
                $facetKeyVal = explode(':', $facet['query'], 2);
                if (
                    $facetKeyVal[0] == 'collection_faceting'
                    && !strpos($facetKeyVal[1], '" OR "')
                ) {
                    $collectionId = Helper::getUidFromIndexName(trim($facetKeyVal[1], '(")'), 'tx_dlf_collections');
                }
            }
            $this->view->assign('COLLECTION_ID', $collectionId);
        }
    }

    /**
     * Adds the encrypted Solr core name to the search form
     *
     * @access protected
     *
     * @return string HTML input fields with encrypted core name and hash
     */
    protected function addEncryptedCoreName()
    {
        // Get core name.
        $name = Helper::getIndexNameFromUid($this->settings['solrcore'], 'tx_dlf_solrcores');
        // Encrypt core name.
        if (!empty($name)) {
            $name = Helper::encrypt($name);
        }
        // Add encrypted fields to search form.
        if ($name !== false) {
            $this->view->assign('ENCRYPTED_CORE_NAME', $name);
        }
    }

    /**
     * Adds the facets menu to the search form
     *
     * @access protected
     *
     * @return string HTML output of facets menu
     */
    protected function addFacetsMenu()
    {
        // Check for typoscript configuration to prevent fatal error.
        if (empty($this->settings['facetsConf'])) {
            $this->logger->warning('Incomplete plugin configuration');
            return '';
        }
        // Quit without doing anything if no facets are selected.
        if (empty($this->settings['facets']) && empty($this->settings['facetCollections'])) {
            return '';
        }

        // Get facets from plugin configuration.
        $facets = [];
        foreach (GeneralUtility::trimExplode(',', $this->settings['facets'], true) as $facet) {
            $facets[$facet . '_faceting'] = Helper::translate($facet, 'tx_dlf_metadata', $this->settings['pages']);
        }

        $this->view->assign('facetsMenu', $this->makeFacetsMenuArray($facets));
    }

    /**
     * This builds a menu array for HMENU
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return array HMENU array
     */
    public function makeFacetsMenuArray($facets)
    {
        $menuArray = [];
        // Set default value for facet search.
        $search = [
            'query' => '*',
            'params' => [
                'component' => [
                    'facetset' => [
                        'facet' => []
                    ]
                ]
            ]
        ];
        // Extract query and filter from last search.
        $list = GeneralUtility::makeInstance(DocumentList::class);
        if (!empty($list->metadata['options']['source'])) {
            if ($list->metadata['options']['source'] == 'search') {
                $search['query'] = $list->metadata['options']['select'];
            }
            $search['params'] = $list->metadata['options']['params'];
        }
        // Get applicable facets.
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return [];
        }
        // Set needed parameters for facet search.
        if (empty($search['params']['filterquery'])) {
            $search['params']['filterquery'] = [];
        }

        foreach (array_keys($facets) as $field) {
            $search['params']['component']['facetset']['facet'][] = [
                'type' => 'field',
                'key' => $field,
                'field' => $field,
                'limit' => $this->settings['limitFacets'],
                'sort' => isset($this->settings['sortingFacets']) ? $this->settings['sortingFacets'] : 'count'
            ];
        }

        // Set additional query parameters.
        $search['params']['start'] = 0;
        $search['params']['rows'] = 0;
        // Set query.
        $search['params']['query'] = $search['query'];
        // Perform search.
        $selectQuery = $solr->service->createSelect($search['params']);
        $results = $solr->service->select($selectQuery);
        $facet = $results->getFacetSet();

        $facetCollectionArray = [];

        // replace everything expect numbers and comma
        $facetCollections = preg_replace('/[^0-9,]/', '', $this->settings['facetCollections']);

        if (!empty($facetCollections)) {
            $collections = $this->collectionRepository->findCollectionsBySettings(['collections' => $facetCollections]);

            /** @var Collection $collection */
            foreach ($collections as $collection) {
                $facetCollectionArray[] = $collection->getIndexName();
            }
        }

        // Process results.
        if ($facet) {
            foreach ($facet as $field => $values) {
                $entryArray = [];
                $entryArray['title'] = htmlspecialchars($facets[$field]);
                $entryArray['count'] = 0;
                $entryArray['_OVERRIDE_HREF'] = '';
                $entryArray['doNotLinkIt'] = 1;
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
                        if (++$i == $this->settings['limit']) {
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
     * Creates an array for a HMENU entry of a facet value.
     *
     * @access protected
     *
     * @param string $field: The facet's index_name
     * @param string $value: The facet's value
     * @param int $count: Number of hits for this facet
     * @param array $search: The parameters of the current search query
     * @param string &$state: The state of the parent item
     *
     * @return array The array for the facet's menu entry
     */
    protected function getFacetsMenuEntry($field, $value, $count, $search, &$state)
    {
        $entryArray = [];
        // Translate value.
        if ($field == 'owner_faceting') {
            // Translate name of holding library.
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->settings['pages']));
        } elseif ($field == 'type_faceting') {
            // Translate document type.
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->settings['pages']));
        } elseif ($field == 'collection_faceting') {
            // Translate name of collection.
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_collections', $this->settings['pages']));
        } elseif ($field == 'language_faceting') {
            // Translate ISO 639 language code.
            $entryArray['title'] = htmlspecialchars(Helper::getLanguageName($value));
        } else {
            $entryArray['title'] = htmlspecialchars($value);
        }
        $entryArray['count'] = $count;
        $entryArray['doNotLinkIt'] = 0;
        // Check if facet is already selected.
        $queryColumn = array_column($search['params']['filterquery'], 'query');
        $index = array_search($field . ':("' . Solr::escapeQuery($value) . '")', $queryColumn);
        if ($index !== false) {
            // Facet is selected, thus remove it from filter.
            unset($queryColumn[$index]);
            $queryColumn = array_values($queryColumn);
            $entryArray['ITEM_STATE'] = 'CUR';
            $state = 'ACTIFSUB';
            //Reset facets
            if ($this->settings['resetFacets']) {
                //remove ($count) for selected facet in template
                $entryArray['count'] = false;
                //build link to delete selected facet
                $uri = $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setArguments(['tx_dlf' => ['query' => $search['query'], 'fq' => $queryColumn], 'tx_dlf_search' => ['action' => 'search']])
                    ->setAddQueryString(true)
                    ->build();
                $entryArray['_OVERRIDE_HREF'] = $uri;
                $entryArray['title'] = sprintf(LocalizationUtility::translate('search.resetFacet', 'dlf'), $entryArray['title']);
            }
        } else {
            // Facet is not selected, thus add it to filter.
            $queryColumn[] = $field . ':("' . Solr::escapeQuery($value) . '")';
            $entryArray['ITEM_STATE'] = 'NO';
        }
        $uri = $this->uriBuilder->reset()
            ->setTargetPageUid($GLOBALS['TSFE']->id)
            ->setArguments(['tx_dlf' => ['query' => $search['query'], 'fq' => $queryColumn], 'tx_dlf_search' => ['action' => 'search']])
            ->setArgumentPrefix('tx_dlf')
            ->build();
        $entryArray['_OVERRIDE_HREF'] = $uri;

        return $entryArray;
    }

    /**
     * Returns the extended search form and adds the JS files necessary for extended search.
     *
     * @access protected
     *
     * @return string The extended search form or an empty string
     */
    protected function addExtendedSearch()
    {
        // Quit without doing anything if no fields for extended search are selected.
        if (
            empty($this->settings['extendedSlotCount'])
            || empty($this->settings['extendedFields'])
        ) {
            return '';
        }
        // Get operator options.
        $operatorOptions = [];
        foreach (['AND', 'OR', 'NOT'] as $operator) {
            $operatorOptions[$operator] = htmlspecialchars(LocalizationUtility::translate('search.' . $operator, 'dlf'));
        }
        // Get field selector options.
        $fieldSelectorOptions = [];
        $searchFields = GeneralUtility::trimExplode(',', $this->settings['extendedFields'], true);
        foreach ($searchFields as $searchField) {
            $fieldSelectorOptions[$searchField] = Helper::translate($searchField, 'tx_dlf_metadata', $this->settings['pages']);
        }
        $slotCountArray = [];
        for ($i = 0; $i < $this->settings['extendedSlotCount']; $i++) {
            $slotCountArray[] = $i;
        }

        $this->view->assign('extendedSlotCount', $slotCountArray);
        $this->view->assign('extendedFields', $this->settings['extendedFields']);
        $this->view->assign('operators', $operatorOptions);
        $this->view->assign('searchFields', $fieldSelectorOptions);
    }
}
