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

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;

class SearchController extends AbstractController
{
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
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * Search Action
     *
     * @return void
     */
    public function searchAction()
    {
        // if search was triggered, get search parameters from POST variables
        $searchParams = $this->getParametersSafely('searchParameter');

        // output is done by main action
        $this->forward('main', null, null, ['searchParameter' => $searchParams]);
    }

    /**
     * Main action
     *
     * @return void
     */
    public function mainAction()
    {
        // Quit without doing anything if required variables are not set.
        if (empty($this->settings['solrcore'])) {
            $this->logger->warning('Incomplete plugin configuration');
            return;
        }

        // if search was triggered, get search parameters from POST variables
        $searchParams = $this->getParametersSafely('searchParameter');

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        // get results from search
        // find all documents from Solr
        if (!empty($searchParams)) {
            $solrResults = $this->documentRepository->findSolrByCollection('', $this->settings, $searchParams, $listedMetadata);
        }

        // Pagination of Results: Pass the currentPage to the fluid template to calculate current index of search result.
        $widgetPage = $this->getParametersSafely('@widget_0');
        if (empty($widgetPage)) {
            $widgetPage = ['currentPage' => 1];
        }

        $documents = $solrResults['documents'] ? : [];
        //$this->view->assign('metadata', $sortableMetadata);
        $this->view->assign('documents', $documents);
        $this->view->assign('widgetPage', $widgetPage);
        $this->view->assign('lastSearch', $searchParams);

        $this->view->assign('listedMetadata', $listedMetadata);
        $this->view->assign('sortableMetadata', $sortableMetadata);

        // ABTODO: facets and extended search might fail
        // Add the facets menu
        $this->addFacetsMenu();

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
    protected function addCurrentDocument()
    {
        // Load current list object.
        $list = GeneralUtility::makeInstance(DocumentList::class);
        // Load current document.
        if (
            !empty($this->requestData['id'])
            && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->requestData['id'])
        ) {
            $this->loadDocument($this->requestData);
            // Get document's UID
            if ($this->document) {
                $this->view->assign('DOCUMENT_ID', $this->document->getUid());
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
            $facets[$facet . '_faceting'] = Helper::translate($facet, 'tx_dlf_metadata', $this->settings['storagePid']);
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
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->settings['storagePid']));
        } elseif ($field == 'type_faceting') {
            // Translate document type.
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->settings['storagePid']));
        } elseif ($field == 'collection_faceting') {
            // Translate name of collection.
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_collections', $this->settings['storagePid']));
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
            $fieldSelectorOptions[$searchField] = Helper::translate($searchField, 'tx_dlf_metadata', $this->settings['storagePid']);
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
