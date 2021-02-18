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

namespace Kitodo\Dlf\Plugin;

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Search' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @author Frank Ulrich Weber <fuw@zeutschel.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Search extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Search.php';

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
            // Get collection's UID.
            return '<input type="hidden" name="' . $this->prefixId . '[collection]" value="' . $list->metadata['options']['select'] . '" />';
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
            return '<input type="hidden" name="' . $this->prefixId . '[collection]" value="' . $collectionId . '" />';
        }
        return '';
    }

    /**
     * Adds the current document's UID or parent ID to the search form
     *
     * @access protected
     *
     * @return string HTML input fields with current document's UID and parent ID
     */
    protected function addCurrentDocument()
    {
        // Load current list object.
        $list = GeneralUtility::makeInstance(DocumentList::class);
        // Load current document.
        if (
            !empty($this->piVars['id'])
            && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->piVars['id'])
        ) {
            $this->loadDocument();
            // Get document's UID or parent ID.
            if ($this->doc->ready) {
                return '<input type="hidden" name="' . $this->prefixId . '[id]" value="' . ($this->doc->parentId > 0 ? $this->doc->parentId : $this->doc->uid) . '" />';
            }
        } elseif (!empty($list->metadata['options']['params']['filterquery'])) {
            // Get document's UID from search metadata.
            foreach ($list->metadata['options']['params']['filterquery'] as $facet) {
                $facetKeyVal = explode(':', $facet['query']);
                if ($facetKeyVal[0] == 'uid') {
                    $documentId = (int) substr($facetKeyVal[1], 1, strpos($facetKeyVal[1], ')'));
                }
            }
            return '<input type="hidden" name="' . $this->prefixId . '[id]" value="' . $documentId . '" />';
        }
        return '';
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
            return '<input type="hidden" name="' . $this->prefixId . '[encrypted]" value="' . $name . '" />';
        } else {
            return '';
        }
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
            empty($this->conf['extendedSlotCount'])
            || empty($this->conf['extendedFields'])
        ) {
            return $extendedSearch;
        }
        // Get operator options.
        $operatorOptions = '';
        foreach (['AND', 'OR', 'NOT'] as $operator) {
            $operatorOptions .= '<option class="tx-dlf-search-operator-option tx-dlf-search-operator-' . $operator . '" value="' . $operator . '">' . htmlspecialchars($this->pi_getLL($operator, '')) . '</option>';
        }
        // Get field selector options.
        $fieldSelectorOptions = '';
        $searchFields = GeneralUtility::trimExplode(',', $this->conf['extendedFields'], true);
        foreach ($searchFields as $searchField) {
            $fieldSelectorOptions .= '<option class="tx-dlf-search-field-option tx-dlf-search-field-' . $searchField . '" value="' . $searchField . '">' . Helper::translate($searchField, 'tx_dlf_metadata', $this->conf['pages']) . '</option>';
        }
        for ($i = 0; $i < $this->conf['extendedSlotCount']; $i++) {
            $markerArray = [
                '###EXT_SEARCH_OPERATOR###' => '<select class="tx-dlf-search-operator tx-dlf-search-operator-' . $i . '" name="' . $this->prefixId . '[extOperator][' . $i . ']">' . $operatorOptions . '</select>',
                '###EXT_SEARCH_FIELDSELECTOR###' => '<select class="tx-dlf-search-field tx-dlf-search-field-' . $i . '" name="' . $this->prefixId . '[extField][' . $i . ']">' . $fieldSelectorOptions . '</select>',
                '###EXT_SEARCH_FIELDQUERY###' => '<input class="tx-dlf-search-query tx-dlf-search-query-' . $i . '" type="text" name="' . $this->prefixId . '[extQuery][' . $i . ']" />'
            ];
            $extendedSearch .= $this->templateService->substituteMarkerArray($this->templateService->getSubpart($this->template, '###EXT_SEARCH_ENTRY###'), $markerArray);
        }
        return $extendedSearch;
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
        if (empty($this->conf['facetsConf.'])) {
            Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
            return '';
        }
        // Quit without doing anything if no facets are selected.
        if (empty($this->conf['facets']) && empty($this->conf['facetCollections'])) {
            return '';
        }
        // Get facets from plugin configuration.
        $facets = [];
        foreach (GeneralUtility::trimExplode(',', $this->conf['facets'], true) as $facet) {
            $facets[$facet . '_faceting'] = Helper::translate($facet, 'tx_dlf_metadata', $this->conf['pages']);
        }
        // Render facets menu.
        $TSconfig = [];
        $TSconfig['special'] = 'userfunction';
        $TSconfig['special.']['userFunc'] = \Kitodo\Dlf\Plugin\Search::class . '->makeFacetsMenuArray';
        $TSconfig['special.']['facets'] = $facets;
        $TSconfig['special.']['limit'] = max(intval($this->conf['limitFacets']), 1);
        $TSconfig = Helper::mergeRecursiveWithOverrule($this->conf['facetsConf.'], $TSconfig);
        return $this->cObj->cObjGetSingle('HMENU', $TSconfig);
    }

    /**
     * Adds the fulltext switch to the search form
     *
     * @access protected
     *
     * @param int $isFulltextSearch: Is fulltext search activated?
     *
     * @return string HTML output of fulltext switch
     */
    protected function addFulltextSwitch($isFulltextSearch = 0)
    {
        $output = '';
        // Check for plugin configuration.
        if (!empty($this->conf['fulltext'])) {
            $output .= ' <input class="tx-dlf-search-fulltext" id="tx-dlf-search-fulltext-no" type="radio" name="' . $this->prefixId . '[fulltext]" value="0" ' . ($isFulltextSearch == 0 ? 'checked="checked"' : '') . ' />';
            $output .= ' <label for="tx-dlf-search-fulltext-no">' . htmlspecialchars($this->pi_getLL('label.inMetadata', '')) . '</label>';
            $output .= ' <input class="tx-dlf-search-fulltext" id="tx-dlf-search-fulltext-yes" type="radio" name="' . $this->prefixId . '[fulltext]" value="1" ' . ($isFulltextSearch == 1 ? 'checked="checked"' : '') . '/>';
            $output .= ' <label for="tx-dlf-search-fulltext-yes">' . htmlspecialchars($this->pi_getLL('label.inFulltext', '')) . '</label>';
        }
        return $output;
    }

    /**
     * Adds the logical page field to the search form
     *
     * @access protected
     *
     * @return string HTML output of logical page field
     */
    protected function addLogicalPage()
    {
        $output = '';
        // Check for plugin configuration.
        if (!empty($this->conf['showLogicalPageField'])) {
            $output .= ' <label for="tx-dlf-search-logical-page">' . htmlspecialchars($this->pi_getLL('label.logicalPage', '')) . ': </label>';
            $output .= ' <input class="tx-dlf-search-logical-page" id="tx-dlf-search-logical-page" type="text" name="' . $this->prefixId . '[logicalPage]" />';
        }
        return $output;
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
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));
        } elseif ($field == 'type_faceting') {
            // Translate document type.
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));
        } elseif ($field == 'collection_faceting') {
            // Translate name of collection.
            $entryArray['title'] = htmlspecialchars(Helper::translate($value, 'tx_dlf_collections', $this->conf['pages']));
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
            if ($this->conf['resetFacets']) {
                //remove ($count) for selected facet in template
                $entryArray['count'] = false;
                //build link to delete selected facet
                $entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(['query' => $search['query'], 'fq' => $queryColumn]);
                $entryArray['title'] = sprintf($this->pi_getLL('resetFacet', ''), $entryArray['title']);
            }
        } else {
            // Facet is not selected, thus add it to filter.
            $queryColumn[] = $field . ':("' . Solr::escapeQuery($value) . '")';
            $entryArray['ITEM_STATE'] = 'NO';
        }
        $entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(['query' => $search['query'], 'fq' => $queryColumn]);
        return $entryArray;
    }

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Disable caching for this plugin.
        $this->setCache(false);
        // Quit without doing anything if required variables are not set.
        if (empty($this->conf['solrcore'])) {
            Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
            return $content;
        }
        if (
            !isset($this->piVars['query'])
            && empty($this->piVars['extQuery'])
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
            if (!empty($this->conf['suggest'])) {
                $this->addAutocompleteJS();
            }
            // Load template file.
            $this->getTemplate();
            // Configure @action URL for form.
            $linkConf = [
                'parameter' => $GLOBALS['TSFE']->id,
                'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http']
            ];
            // Fill markers.
            $markerArray = [
                '###ACTION_URL###' => $this->cObj->typoLink_URL($linkConf),
                '###LABEL_QUERY###' => (!empty($search['query']) ? htmlspecialchars($search['query']) : htmlspecialchars($this->pi_getLL('label.query'))),
                '###LABEL_SUBMIT###' => htmlspecialchars($this->pi_getLL('label.submit')),
                '###FIELD_QUERY###' => $this->prefixId . '[query]',
                '###QUERY###' => (!empty($search['query']) ? htmlspecialchars($search['query']) : ''),
                '###FULLTEXTSWITCH###' => $this->addFulltextSwitch($list->metadata['fulltextSearch']),
                '###FIELD_DOC###' => ($this->conf['searchIn'] == 'document' || $this->conf['searchIn'] == 'all' ? $this->addCurrentDocument() : ''),
                '###FIELD_COLL###' => ($this->conf['searchIn'] == 'collection' || $this->conf['searchIn'] == 'all' ? $this->addCurrentCollection() : ''),
                '###ADDITIONAL_INPUTS###' => $this->addEncryptedCoreName(),
                '###FACETS_MENU###' => $this->addFacetsMenu(),
                '###LOGICAL_PAGE###' => $this->addLogicalPage()
            ];
            // Get additional fields for extended search.
            $extendedSearch = $this->addExtendedSearch();
            // Display search form.
            $content .= $this->templateService->substituteSubpart($this->templateService->substituteMarkerArray($this->template, $markerArray), '###EXT_SEARCH_ENTRY###', $extendedSearch);
            return $this->pi_wrapInBaseClass($content);
        } else {
            // Build label for result list.
            $label = htmlspecialchars($this->pi_getLL('search', ''));
            if (!empty($this->piVars['query'])) {
                $label .= htmlspecialchars(sprintf($this->pi_getLL('for', ''), trim($this->piVars['query'])));
            }
            // Prepare query parameters.
            $params = [];
            $matches = [];
            // Set search query.
            if (
                (!empty($this->conf['fulltext']) && !empty($this->piVars['fulltext']))
                || preg_match('/fulltext:\((.*)\)/', trim($this->piVars['query']), $matches)
            ) {
                // If the query already is a fulltext query e.g using the facets
                $this->piVars['query'] = empty($matches[1]) ? $this->piVars['query'] : $matches[1];
                // Search in fulltext field if applicable. Query must not be empty!
                if (!empty($this->piVars['query'])) {
                    $query = 'fulltext:(' . Solr::escapeQuery(trim($this->piVars['query'])) . ')';
                }
            } else {
                // Retain given search field if valid.
                $query = Solr::escapeQueryKeepField(trim($this->piVars['query']), $this->conf['pages']);
            }
            // Add extended search query.
            if (
                !empty($this->piVars['extQuery'])
                && is_array($this->piVars['extQuery'])
            ) {
                $allowedOperators = ['AND', 'OR', 'NOT'];
                $allowedFields = GeneralUtility::trimExplode(',', $this->conf['extendedFields'], true);
                $numberOfExtQueries = count($this->piVars['extQuery']);
                for ($i = 0; $i < $numberOfExtQueries; $i++) {
                    if (!empty($this->piVars['extQuery'][$i])) {
                        if (
                            in_array($this->piVars['extOperator'][$i], $allowedOperators)
                            && in_array($this->piVars['extField'][$i], $allowedFields)
                        ) {
                            if (!empty($query)) {
                                $query .= ' ' . $this->piVars['extOperator'][$i] . ' ';
                            }
                            $query .= Indexer::getIndexFieldName($this->piVars['extField'][$i], $this->conf['pages']) . ':(' . Solr::escapeQuery($this->piVars['extQuery'][$i]) . ')';
                        }
                    }
                }
            }
            // Add filter query for faceting.
            if (!empty($this->piVars['fq'])) {
                foreach ($this->piVars['fq'] as $filterQuery) {
                    $params['filterquery'][]['query'] = $filterQuery;
                }
            }
            // Add filter query for in-document searching.
            if (
                $this->conf['searchIn'] == 'document'
                || $this->conf['searchIn'] == 'all'
            ) {
                if (
                    !empty($this->piVars['id'])
                    && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->piVars['id'])
                ) {
                    $params['filterquery'][]['query'] = 'uid:(' . $this->piVars['id'] . ') OR partof:(' . $this->piVars['id'] . ')';
                    $label .= htmlspecialchars(sprintf($this->pi_getLL('in', ''), Document::getTitle($this->piVars['id'])));
                }
            }
            // Add filter query for in-collection searching.
            if (
                $this->conf['searchIn'] == 'collection'
                || $this->conf['searchIn'] == 'all'
            ) {
                if (
                    !empty($this->piVars['collection'])
                    && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->piVars['collection'])
                ) {
                    $index_name = Helper::getIndexNameFromUid($this->piVars['collection'], 'tx_dlf_collections', $this->conf['pages']);
                    $params['filterquery'][]['query'] = 'collection_faceting:("' . Solr::escapeQuery($index_name) . '")';
                    $label .= sprintf($this->pi_getLL('in', '', true), Helper::translate($index_name, 'tx_dlf_collections', $this->conf['pages']));
                }
            }
            // Add filter query for collection restrictions.
            if ($this->conf['collections']) {
                $collIds = explode(',', $this->conf['collections']);
                $collIndexNames = [];
                foreach ($collIds as $collId) {
                    $collIndexNames[] = Solr::escapeQuery(Helper::getIndexNameFromUid(intval($collId), 'tx_dlf_collections', $this->conf['pages']));
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
            $solr = Solr::getInstance($this->conf['solrcore']);
            if (!$solr->ready) {
                Helper::devLog('Apache Solr not available', DEVLOG_SEVERITY_ERROR);
                return $content;
            }
            // Set search parameters.
            $solr->cPid = $this->conf['pages'];
            $solr->params = $params;
            // Perform search.
            $list = $solr->search();
            $list->metadata = [
                'label' => $label,
                'thumbnail' => '',
                'searchString' => $this->piVars['query'],
                'fulltextSearch' => (!empty($this->piVars['fulltext']) ? '1' : '0'),
                'options' => $list->metadata['options']
            ];
            $list->save();
            // Clean output buffer.
            ob_end_clean();
            $additionalParams = [];
            if (!empty($this->piVars['logicalPage'])) {
                $additionalParams['logicalPage'] = $this->piVars['logicalPage'];
            }
            // Jump directly to the page view, if there is only one result and it is configured
            if ($list->metadata['options']['numberOfHits'] == 1 && !empty($this->conf['showSingleResult'])) {
                $linkConf['parameter'] = $this->conf['targetPidPageView'];
                $additionalParams['id'] = $list->current()['uid'];
                $additionalParams['highlight_word'] = preg_replace('/\s\s+/', ';', $list->metadata['searchString']);
                $additionalParams['page'] = count($list[0]['subparts']) == 1 ? $list[0]['subparts'][0]['page'] : 1;
            } else {
                // Keep some plugin variables.
                $linkConf['parameter'] = $this->conf['targetPid'];
                if (!empty($this->piVars['order'])) {
                    $additionalParams['order'] = $this->piVars['order'];
                    $additionalParams['asc'] = !empty($this->piVars['asc']) ? '1' : '0';
                }
            }
            $linkConf['forceAbsoluteUrl'] = !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0;
            $linkConf['forceAbsoluteUrl.']['scheme'] = !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http';
            $linkConf['additionalParams'] = GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', true, false);
            // Send headers.
            header('Location: ' . GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL($linkConf)));
            exit;
        }
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
    public function makeFacetsMenuArray($content, $conf)
    {
        $this->init($conf);
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
        $solr = Solr::getInstance($this->conf['solrcore']);
        if (!$solr->ready) {
            Helper::devLog('Apache Solr not available', DEVLOG_SEVERITY_ERROR);
            return [];
        }
        // Set needed parameters for facet search.
        if (empty($search['params']['filterquery'])) {
            $search['params']['filterquery'] = [];
        }
        foreach (array_keys($this->conf['facets']) as $field) {
            $search['params']['component']['facetset']['facet'][] = [
                'type' => 'field',
                'key' => $field,
                'field' => $field,
                'limit' => $this->conf['limitFacets'],
                'sort' => isset($this->conf['sortingFacets']) ? $this->conf['sortingFacets'] : 'count'
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
        $facetCollections = preg_replace('/[^0-9,]/', '', $this->conf['facetCollections']);

        if (!empty($facetCollections)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_collections');

            $result = $queryBuilder
                ->select('tx_dlf_collections.index_name AS index_name')
                ->from('tx_dlf_collections')
                ->where(
                    $queryBuilder->expr()->in(
                        'tx_dlf_collections.uid',
                        $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $facetCollections), Connection::PARAM_INT_ARRAY)
                    )
                )
                ->execute();

            while ($collection = $result->fetch()) {
                $facetCollectionArray[] = $collection['index_name'];
            }
        }

        // Process results.
        foreach ($facet as $field => $values) {
            $entryArray = [];
            $entryArray['title'] = htmlspecialchars($this->conf['facets'][$field]);
            $entryArray['count'] = 0;
            $entryArray['_OVERRIDE_HREF'] = '';
            $entryArray['doNotLinkIt'] = 1;
            $entryArray['ITEM_STATE'] = 'NO';
            // Count number of facet values.
            $i = 0;
            foreach ($values as $value => $count) {
                if ($count > 0) {
                    // check if facet collection configuration exists
                    if (!empty($this->conf['facetCollections'])) {
                        if ($field == "collection_faceting" && !in_array($value, $facetCollectionArray)) {
                            continue;
                        }
                    }
                    $entryArray['count']++;
                    if ($entryArray['ITEM_STATE'] == 'NO') {
                        $entryArray['ITEM_STATE'] = 'IFSUB';
                    }
                    $entryArray['_SUB_MENU'][] = $this->getFacetsMenuEntry($field, $value, $count, $search, $entryArray['ITEM_STATE']);
                    if (++$i == $this->conf['limit']) {
                        break;
                    }
                } else {
                    break;
                }
            }
            $menuArray[] = $entryArray;
        }
        return $menuArray;
    }
}
