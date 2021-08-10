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

use \TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use \Kitodo\Dlf\Domain\Model\SearchForm;

class SearchController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    public $prefixId = 'tx_dlf';

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * SearchController constructor.
     * @param $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param SearchForm $searchForm
     */
    public function searchAction(SearchForm $searchForm = NULL)
    {
        // Build label for result list.
        $label = htmlspecialchars($this->pi_getLL('search', ''));
        if (!empty($searchForm->getQuery())) {
            $label .= htmlspecialchars(sprintf($this->pi_getLL('for', ''), trim($searchForm->getQuery())));
        }
        // Prepare query parameters.
        $params = [];
        $matches = [];
        // Set search query.
        if (
            (!empty($this->settings['fulltext']) && !empty($searchForm->getFulltext()))
            || preg_match('/fulltext:\((.*)\)/', trim($searchForm->getQuery()), $matches)
        ) {
            // If the query already is a fulltext query e.g using the facets
            $searchForm->setQuery(empty($matches[1]) ? $searchForm->getQuery() : $matches[1]);
            // Search in fulltext field if applicable. Query must not be empty!
            if (!empty($searchForm->getQuery())) {
                $query = 'fulltext:(' . Solr::escapeQuery(trim($searchForm->getQuery())) . ')';
            }
        } else {
            // Retain given search field if valid.
            $query = Solr::escapeQueryKeepField(trim($searchForm->getQuery()), $this->settings['pages']);
        }
        // Add extended search query.
        if (
            !empty($searchForm->getExtQuery())
            && is_array($searchForm->getExtQuery())
        ) {
            $allowedOperators = ['AND', 'OR', 'NOT'];
            $allowedFields = GeneralUtility::trimExplode(',', $this->settings['extendedFields'], true);
            $numberOfExtQueries = count($searchForm->getExtQuery());
            for ($i = 0; $i < $numberOfExtQueries; $i++) {
                if (!empty($searchForm->getExtQuery()[$i])) {
                    if (
                        in_array($searchForm->getExtOperator()[$i], $allowedOperators)
                        && in_array($searchForm->getExtField()[$i], $allowedFields)
                    ) {
                        if (!empty($query)) {
                            $query .= ' ' . $searchForm->getExtOperator()[$i] . ' ';
                        }
                        $query .= Indexer::getIndexFieldName($searchForm->getExtField()[$i], $this->settings['pages']) . ':(' . Solr::escapeQuery($searchForm->getExtQuery()[$i]) . ')';
                    }
                }
            }
        }
        // Add filter query for faceting.
        if (!empty($searchForm->getFilterQuery())) {
            foreach ($searchForm->getFilterQuery() as $filterQuery) {
                $params['filterquery'][]['query'] = $filterQuery;
            }
        }
        // Add filter query for in-document searching.
        if (
            $this->settings['searchIn'] == 'document'
            || $this->settings['searchIn'] == 'all'
        ) {
            if (
                !empty($searchForm->getDocumentId())
                && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($searchForm->getDocumentId())
            ) {
                // Search in document and all subordinates (valid for up to three levels of hierarchy).
                $params['filterquery'][]['query'] = '_query_:"{!join from=uid to=partof}uid:{!join from=uid to=partof}uid:' . $searchForm->getDocumentId() . '"' .
                    ' OR {!join from=uid to=partof}uid:' . $searchForm->getDocumentId() .
                    ' OR uid:' . $searchForm->getDocumentId();
                $label .= htmlspecialchars(sprintf($this->pi_getLL('in', ''), Document::getTitle($searchForm->getDocumentId())));
            }
        }
        // Add filter query for in-collection searching.
        if (
            $this->settings['searchIn'] == 'collection'
            || $this->settings['searchIn'] == 'all'
        ) {
            if (
                !empty($searchForm->getCollection())
                && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($searchForm->getCollection())
            ) {
                $index_name = Helper::getIndexNameFromUid($searchForm->getCollection(), 'tx_dlf_collections', $this->settings['pages']);
                $params['filterquery'][]['query'] = 'collection_faceting:("' . Solr::escapeQuery($index_name) . '")';
                $label .= sprintf($this->pi_getLL('in', '', true), Helper::translate($index_name, 'tx_dlf_collections', $this->settings['pages']));
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
            Helper::devLog('Apache Solr not available', DEVLOG_SEVERITY_ERROR);
            $this->redirect('main', 'Search', NULL);
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
            'searchString' => $searchForm->getQuery(),
            'fulltextSearch' => (!empty($searchForm->getFulltext()) ? '1' : '0'),
            'options' => $list->metadata['options']
        ];
        $list->save();
        // Clean output buffer.
        ob_end_clean();
        $additionalParams = [];
        if (!empty($searchForm->getLogicalPage())) {
            $additionalParams['logicalPage'] = $searchForm->getLogicalPage();
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
            if (!empty($searchForm->getOrder())) {
                $additionalParams['order'] = $searchForm->getOrder();
                $additionalParams['asc'] = !empty($searchForm->getAsc()) ? '1' : '0';
            }
        }
        $linkConf['forceAbsoluteUrl'] = !empty($this->settings['forceAbsoluteUrl']) ? 1 : 0;
        $linkConf['forceAbsoluteUrl.']['scheme'] = !empty($this->settings['forceAbsoluteUrl']) && !empty($this->settings['forceAbsoluteUrlHttps']) ? 'https' : 'http';
        $linkConf['additionalParams'] = GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', true, false);
        // Send headers.
//            header('Location: ' . GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL($linkConf)));
//            exit;
        return $this->forward('main', 'Search', NULL, ['searchForm' => NULL]);
    }

    /**
     * @param SearchForm|null $searchForm
     * @return mixed
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function mainAction(SearchForm $searchForm = NULL)
    {
        $settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);

        // Disable caching for this plugin.
//        $this->setCache(false);
        // Quit without doing anything if required variables are not set.
        if (empty($this->settings['solrcore'])) {
            Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
        }
        if (!isset($searchForm) ||
            (empty($searchForm->getQuery())
            && empty($searchForm->getExtQuery()))
        ) {
            // Extract query and filter from last search.
            $list = GeneralUtility::makeInstance(DocumentList::class);
            if (!empty($list->metadata['searchString'])) {
                if ($list->metadata['options']['source'] == 'search') {
                    $search['query'] = $list->metadata['searchString'];
                }
                $search['params'] = $list->metadata['options']['params'];
            }
            // Add javascript for search suggestions if enabled and jQuery autocompletion is available.
            if (!empty($this->settings['suggest'])) {
                $this->addAutocompleteJS();
            }
            // Load template file.
//            TODO: Extbase/Fluid Check template
//            $this->getTemplate();
            // Configure @action URL for form.
            $linkConf = [
                'parameter' => $GLOBALS['TSFE']->id,
                'forceAbsoluteUrl' => !empty($this->settings['forceAbsoluteUrl']) ? 1 : 0,
                'forceAbsoluteUrl.' => ['scheme' => !empty($this->settings['forceAbsoluteUrl']) && !empty($this->settings['forceAbsoluteUrlHttps']) ? 'https' : 'http']
            ];

            // Assign variables to view
            $uri = $this->uriBuilder->reset()
                ->setTargetPageUid($GLOBALS['TSFE']->id)
                ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']))
                ->setAbsoluteUriScheme(!empty($this->settings['forceAbsoluteUrl']) && !empty($this->settings['forceAbsoluteUrlHttps']) ? 'https' : 'http')
                ->build();

            $this->view->assign('ACTION_URL', $uri);
            $this->view->assign('FIELD_QUERY', 'query');
            $this->view->assign('QUERY', (!empty($search['query']) ? htmlspecialchars($search['query']) : ''));
            $this->view->assign('FULLTEXT_SEARCH', $list->metadata['fulltextSearch']);

            $this->view->assign('FACETS_MENU', $this->addFacetsMenu());

            $this->addEncryptedCoreName();

            if ($this->settings['searchIn'] == 'collection' || $this->settings['searchIn'] == 'all') {
                $this->addCurrentCollection();
            }
            if ($this->settings['searchIn'] == 'document' || $this->settings['searchIn'] == 'all') {
                $this->addCurrentDocument($searchForm);
            }

            // Get additional fields for extended search.
            $this->addExtendedSearch();

        }
        return $this->view->render();
    }

    protected function pi_getLL($label)
    {
        return $GLOBALS['TSFE']->sL('LLL:EXT:dlf/Resources/Private/Language/Search.xml:' . $label);
    }

    /**
     * Adds the JS files necessary for search suggestions
     *
     * @access protected
     *
     * @return void
     */
    protected function addAutocompleteJS()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        // Check if there are any metadata to suggest.
        $result = $queryBuilder
            ->select('tx_dlf_metadata.*')
            ->from('tx_dlf_metadata')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_metadata.index_autocomplete', 1),
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($this->conf['pages'])),
                Helper::whereExpression('tx_dlf_metadata')
            )
            ->setMaxResults(1)
            ->execute();

        if ($result->rowCount() == 1) {
            $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
            $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->extKey)) . 'Resources/Public/Javascript/Search/Suggester.js');
        } else {
            Helper::devLog('No metadata fields configured for search suggestions', DEVLOG_SEVERITY_WARNING);
        }
    }

    /**
     * Adds the current document's UID or parent ID to the search form
     *
     * @access protected
     *
     * @return string HTML input fields with current document's UID
     */
    protected function addCurrentDocument(SearchForm $searchForm)
    {
        // Load current list object.
        $list = GeneralUtility::makeInstance(DocumentList::class);
        // Load current document.
        if (
            !empty($searchForm->getDocumentId())
            && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($searchForm->getDocumentId())
        ) {
            $this->loadDocument();
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
        $name = Helper::getIndexNameFromUid($this->conf['solrcore'], 'tx_dlf_solrcores');
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
            Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
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
        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
        $cObj = $this->configurationManager->getContentObject();
        // Render facets menu.
        $TSconfig = [];
        $TSconfig['special'] = 'userfunction';
        $TSconfig['special.']['userFunc'] = '\Kitodo\Dlf\Plugin\Search::class->makeFacetsMenuArray';
        $TSconfig['special.']['facets'] = $facets;
        $TSconfig['special.']['limit'] = max(intval($this->settings['limitFacets']), 1);
        $TSconfig = Helper::mergeRecursiveWithOverrule($this->settings['facetsConf'], $TSconfig);

        // TODO: FACETS HMENU NOT WORKING

        return $cObj->cObjGetSingle('HMENU', $TSconfig);
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
        $extendedSearch = '';
        // Quit without doing anything if no fields for extended search are selected.
        if (
            empty($this->settings['extendedSlotCount'])
            || empty($this->settings['extendedFields'])
        ) {
            return $extendedSearch;
        }
        // Get operator options.
        $operatorOptions = [];
        foreach (['AND', 'OR', 'NOT'] as $operator) {
            $operatorOptions[$operator] = htmlspecialchars($this->pi_getLL($operator, ''));
        }
        // Get field selector options.
        $fieldSelectorOptions = [];
        $searchFields = GeneralUtility::trimExplode(',', $this->settings['extendedFields'], true);
        foreach ($searchFields as $searchField) {
            $fieldSelectorOptions[$searchField] = Helper::translate($searchField, 'tx_dlf_metadata', $this->settings['pages']);
        }
        for ($i = 0; $i < $this->settings['extendedSlotCount']; $i++) {
            $slotCountArray[] = $i;
        }

        $this->view->assign('extendedSlotCount', $slotCountArray);
        $this->view->assign('extendedFields', $this->settings['extendedFields']);
        $this->view->assign('operators', $operatorOptions);
        $this->view->assign('searchFields', $fieldSelectorOptions);
    }
}