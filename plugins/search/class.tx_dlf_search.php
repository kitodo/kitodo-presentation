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

/**
 * Plugin 'DLF: Search' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_search extends tx_dlf_plugin {

    public $scriptRelPath = 'plugins/search/class.tx_dlf_search.php';

    /**
     * Adds the JS files necessary for search suggestions
     *
     * @access	protected
     *
     * @return	void
     */
    protected function addAutocompleteJS() {

        // Check if there are any metadata to suggest.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_metadata.*',
            'tx_dlf_metadata',
            'tx_dlf_metadata.index_autocomplete=1 AND tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata'),
            '',
            '',
            '1'
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

            $GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_search_suggest'] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/search/tx_dlf_search_suggest.js"></script>';

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_search->addAutocompleteJS()] No metadata fields configured for search suggestions', $this->extKey, SYSLOG_SEVERITY_WARNING);

            }

        }

    }

    /**
     * Adds the current collection's UID to the search form
     *
     * @access	protected
     *
     * @return	string		HTML input fields with current document's UID and parent ID
     */
    protected function addCurrentCollection() {

        // Load current collection.
        $list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

        if (!empty($list->metadata['options']['source']) && $list->metadata['options']['source'] == 'collection') {

            // Get collection's UID.
            return '<input type="hidden" name="'.$this->prefixId.'[collection]" value="'.$list->metadata['options']['select'].'" />';

        } elseif (!empty($list->metadata['options']['params']['filterquery'])) {

            // Get collection's UID from search metadata.
            foreach ($list->metadata['options']['params']['filterquery'] as $facet) {

                $facetKeyVal = explode(':', $facet['query'], 2);

                if ($facetKeyVal[0] == 'collection_faceting' && !strpos($facetKeyVal[1], '" OR "')) {

                    $collectionId = tx_dlf_helper::getIdFromIndexName(trim($facetKeyVal[1], '(")'), 'tx_dlf_collections');

                }

            }

            return '<input type="hidden" name="'.$this->prefixId.'[collection]" value="'.$collectionId.'" />';

        }

        return '';

    }

    /**
     * Adds the current document's UID or parent ID to the search form
     *
     * @access	protected
     *
     * @return	string		HTML input fields with current document's UID and parent ID
     */
    protected function addCurrentDocument() {

        // Load current list object.
        $list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

        // Load current document.
        if (!empty($this->piVars['id']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->piVars['id'])) {

            $this->loadDocument();

            // Get document's UID or parent ID.
            if ($this->doc->ready) {

                return '<input type="hidden" name="'.$this->prefixId.'[id]" value="'.($this->doc->parentId > 0 ? $this->doc->parentId : $this->doc->uid).'" />';

            }

        } elseif (!empty($list->metadata['options']['params']['filterquery'])) {

            // Get document's UID from search metadata.
            foreach ($list->metadata['options']['params']['filterquery'] as $facet) {

                $facetKeyVal = explode(':', $facet['query']);

                if ($facetKeyVal[0] == 'uid') {

                    $documentId = (int) substr($facetKeyVal[1], 1, strpos($facetKeyVal[1], ')'));

                }

            }

            return '<input type="hidden" name="'.$this->prefixId.'[id]" value="'.$documentId.'" />';

        }

        return '';

    }

    /**
     * Adds the encrypted Solr core name to the search form
     *
     * @access	protected
     *
     * @return	string		HTML input fields with encrypted core name and hash
     */
    protected function addEncryptedCoreName() {

        // Get core name.
        $name = tx_dlf_helper::getIndexName($this->conf['solrcore'], 'tx_dlf_solrcores');

        // Encrypt core name.
        if (!empty($name)) {

            $name = tx_dlf_helper::encrypt($name);

        }

        // Add encrypted fields to search form.
        if (is_array($name)) {

            return '<input type="hidden" name="'.$this->prefixId.'[encrypted]" value="'.$name['encrypted'].'" /><input type="hidden" name="'.$this->prefixId.'[hashed]" value="'.$name['hash'].'" />';

        } else {

            return '';

        }

    }

    /**
     * Returns the extended search form and adds the JS files necessary for extended search.
     *
     * @access	protected
     *
     * @return	string		The extended search form or an empty string
     */
    protected function addExtendedSearch() {

        $extendedSearch = '';

        // Quit without doing anything if no fields for extended search are selected.
        if (empty($this->conf['extendedSlotCount']) || empty($this->conf['extendedFields'])) {

            return $extendedSearch;

        }

        // Get operator options.
        $operatorOptions = '';

        foreach (array ('AND', 'OR', 'NOT') as $operator) {

            $operatorOptions .= '<option class="tx-dlf-search-operator-option tx-dlf-search-operator-'.$operator.'" value="'.$operator.'">'.$this->pi_getLL($operator, '', TRUE).'</option>';

        }

        // Get field selector options.
        $fieldSelectorOptions = '';

        $searchFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['extendedFields'], TRUE);

        foreach ($searchFields as $searchField) {

            $fieldSelectorOptions .= '<option class="tx-dlf-search-field-option tx-dlf-search-field-'.$searchField.'" value="'.$searchField.'">'.tx_dlf_helper::translate($searchField, 'tx_dlf_metadata', $this->conf['pages']).'</option>';

        }

        for ($i = 0; $i < $this->conf['extendedSlotCount']; $i++) {

            $markerArray = array (
                '###EXT_SEARCH_OPERATOR###' => '<select class="tx-dlf-search-operator tx-dlf-search-operator-'.$i.'" name="'.$this->prefixId.'[extOperator]['.$i.']">'.$operatorOptions.'</select>',
                '###EXT_SEARCH_FIELDSELECTOR###' => '<select class="tx-dlf-search-field tx-dlf-search-field-'.$i.'" name="'.$this->prefixId.'[extField]['.$i.']">'.$fieldSelectorOptions.'</select>',
                '###EXT_SEARCH_FIELDQUERY###' => '<input class="tx-dlf-search-query tx-dlf-search-query-'.$i.'" type="text" name="'.$this->prefixId.'[extQuery]['.$i.']" />'
            );

            $extendedSearch .= $this->cObj->substituteMarkerArray($this->cObj->getSubpart($this->template, '###EXT_SEARCH_ENTRY###'), $markerArray);

        }

        return $extendedSearch;

    }

    /**
     * Adds the facets menu to the search form
     *
     * @access	protected
     *
     * @return	string		HTML output of facets menu
     */
    protected function addFacetsMenu() {

        // Check for typoscript configuration to prevent fatal error.
        if (empty($this->conf['facetsConf.'])) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_search->addFacetsMenu()] Incomplete plugin configuration', $this->extKey, SYSLOG_SEVERITY_WARNING);

            }

            return '';

        }

        // Quit without doing anything if no facets are selected.
        if (empty($this->conf['facets'])) {

            return '';

        }

        // Get facets from plugin configuration.
        $facets = array ();

        foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['facets'], TRUE) as $facet) {

            $facets[$facet.'_faceting'] = tx_dlf_helper::translate($facet, 'tx_dlf_metadata', $this->conf['pages']);

        }

        // Render facets menu.
        $TSconfig = array ();

        $TSconfig['special'] = 'userfunction';

        $TSconfig['special.']['userFunc'] = 'tx_dlf_search->makeFacetsMenuArray';

        $TSconfig['special.']['facets'] = $facets;

        $TSconfig['special.']['limit'] = max(intval($this->conf['limitFacets']), 1);

        $TSconfig = tx_dlf_helper::array_merge_recursive_overrule($this->conf['facetsConf.'], $TSconfig);

        return $this->cObj->HMENU($TSconfig);

    }

    /**
     * Adds the fulltext switch to the search form
     *
     * @access	protected
     *
     * @param int $isFulltextSearch
     *
     * @return	string		HTML output of fulltext switch
     */
    protected function addFulltextSwitch($isFulltextSearch = 0) {

        $output = '';

        // Check for plugin configuration.
        if (!empty($this->conf['fulltext'])) {

            $output .= ' <input class="tx-dlf-search-fulltext" id="tx-dlf-search-fulltext-no" type="radio" name="'.$this->prefixId.'[fulltext]" value="0" '.($isFulltextSearch == 0 ? 'checked="checked"' : '').' />';

            $output .= ' <label for="tx-dlf-search-fulltext-no">'.$this->pi_getLL('label.inMetadata', '').'</label>';

            $output .= ' <input class="tx-dlf-search-fulltext" id="tx-dlf-search-fulltext-yes" type="radio" name="'.$this->prefixId.'[fulltext]" value="1" '.($isFulltextSearch == 1 ? 'checked="checked"' : '').'/>';

            $output .= ' <label for="tx-dlf-search-fulltext-yes">'.$this->pi_getLL('label.inFulltext', '').'</label>';

        }

        return $output;

    }

    /**
     * Adds the logical page field to the search form
     *
     * @access	protected
     *
     * @return	string		HTML output of logical page field
     */
    protected function addLogicalPage() {

        $output = '';

        // Check for plugin configuration.
        if (!empty($this->conf['showLogicalPageField'])) {

            $output .= ' <label for="tx-dlf-search-logical-page">'.$this->pi_getLL('label.logicalPage', '').': </label>';

            $output .= ' <input class="tx-dlf-search-logical-page" id="tx-dlf-search-logical-page" type="text" name="'.$this->prefixId.'[logicalPage]" />';

        }

        return $output;

    }

    /**
     * Creates an array for a HMENU entry of a facet value.
     *
     * @param	string		$field: The facet's index_name
     * @param	string		$value: The facet's value
     * @param	integer		$count: Number of hits for this facet
     * @param	array		$search: The parameters of the current search query
     * @param	string		&$state: The state of the parent item
     *
     * @return	array		The array for the facet's menu entry
     */
    protected function getFacetsMenuEntry($field, $value, $count, $search, &$state) {

        $entryArray = array ();

        // Translate value.
        if ($field == 'owner_faceting') {

            // Translate name of holding library.
            $entryArray['title'] = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));

        } elseif ($field == 'type_faceting') {

            // Translate document type.
            $entryArray['title'] = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));

        } elseif ($field == 'collection_faceting') {

            // Translate name of collection.
            $entryArray['title'] = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_collections', $this->conf['pages']));

        } elseif ($field == 'language_faceting') {

            // Translate ISO 639 language code.
            $entryArray['title'] = htmlspecialchars(tx_dlf_helper::getLanguageName($value));

        } else {

            $entryArray['title'] = htmlspecialchars($value);

        }

        $entryArray['count'] = $count;

        $entryArray['doNotLinkIt'] = 0;

        // Check if facet is already selected.
        $queryColumn = array_column($search['params']['filterquery'], 'query');
        $index = array_search($field.':("'.tx_dlf_solr::escapeQuery($value).'")', $queryColumn);

        if ($index !== FALSE) {

            // Facet is selected, thus remove it from filter.
            unset($queryColumn[$index]);

            $queryColumn = array_values($queryColumn);

            $entryArray['ITEM_STATE'] = 'CUR';

            $state = 'ACTIFSUB';

            //Reset facets
            if ($this->conf['resetFacets']) {
                //remove ($count) for selected facet in template
                $entryArray['count'] = FALSE;
                //build link to delete selected facet
                $entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('query' => $search['query'], 'fq' => $queryColumn));
                $entryArray['title'] = sprintf($this->pi_getLL('resetFacet', ''), $entryArray['title']);
            }

        } else {

            // Facet is not selected, thus add it to filter.
            $queryColumn[] = $field.':("'.tx_dlf_solr::escapeQuery($value).'")';

            $entryArray['ITEM_STATE'] = 'NO';

        }

        $entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('query' => $search['query'], 'fq' => $queryColumn));

        return $entryArray;

    }

    /**
     * The main method of the PlugIn
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	string		The content that is displayed on the website
     */
    public function main($content, $conf) {

        $this->init($conf);

        // Disable caching for this plugin.
        $this->setCache(FALSE);

        // Quit without doing anything if required variables are not set.
        if (empty($this->conf['solrcore'])) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_search->main('.$content.', [data])] Incomplete plugin configuration', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);

            }

            return $content;

        }

        if (!isset($this->piVars['query']) && empty($this->piVars['extQuery'])) {

            // Extract query and filter from last search.
            $list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

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
            if (!empty($this->conf['templateFile'])) {

                $this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

            } else {

                $this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/search/template.tmpl'), '###TEMPLATE###');

            }

            // Configure @action URL for form.
            $linkConf = array (
                'parameter' => $GLOBALS['TSFE']->id
            );

            // Fill markers.
            $markerArray = array (
                '###ACTION_URL###' => $this->cObj->typoLink_URL($linkConf),
                '###LABEL_QUERY###' => (!empty($search['query']) ? $search['query'] : $this->pi_getLL('label.query')),
                '###LABEL_SUBMIT###' => $this->pi_getLL('label.submit'),
                '###FIELD_QUERY###' => $this->prefixId.'[query]',
                '###QUERY###' => (!empty($search['query']) ? $search['query'] : ''),
                '###FULLTEXTSWITCH###' => $this->addFulltextSwitch($list->metadata['fulltextSearch']),
                '###FIELD_DOC###' => ($this->conf['searchIn'] == 'document' || $this->conf['searchIn'] == 'all' ? $this->addCurrentDocument() : ''),
                '###FIELD_COLL###' => ($this->conf['searchIn'] == 'collection' || $this->conf['searchIn'] == 'all' ? $this->addCurrentCollection() : ''),
                '###ADDITIONAL_INPUTS###' => $this->addEncryptedCoreName(),
                '###FACETS_MENU###' => $this->addFacetsMenu(),
                '###LOGICAL_PAGE###' => $this->addLogicalPage()
            );

            // Get additional fields for extended search.
            $extendedSearch = $this->addExtendedSearch();

            // Display search form.
            $content .= $this->cObj->substituteSubpart($this->cObj->substituteMarkerArray($this->template, $markerArray), '###EXT_SEARCH_ENTRY###', $extendedSearch);

            return $this->pi_wrapInBaseClass($content);

        } else {

            // Instantiate search object.
            $solr = tx_dlf_solr::getInstance($this->conf['solrcore']);

            if (!$solr->ready) {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_search->main('.$content.', [data])] Apache Solr not available', $this->extKey, SYSLOG_SEVERITY_ERROR, $conf);

                }

                return $content;

            }

            // Build label for result list.
            $label = $this->pi_getLL('search', '', TRUE);

            if (!empty($this->piVars['query'])) {

                $label .= htmlspecialchars(sprintf($this->pi_getLL('for', ''), $this->piVars['query']));

            }

            // Prepare query parameters.
            $params = array ();

            $matches = array ();

            // Set search query.
            if ((!empty($this->conf['fulltext']) && !empty($this->piVars['fulltext'])) || preg_match('/fulltext:\((.*)\)/', $this->piVars['query'], $matches)) {

                // If the query already is a fulltext query e.g using the facets
                $this->piVars['query'] = empty($matches[1]) ? $this->piVars['query'] : $matches[1];

                // Search in fulltext field if applicable. query must not be empty!
                if (!empty($this->piVars['query'])) {

                    $query = 'fulltext:('.tx_dlf_solr::escapeQuery($this->piVars['query']).')';

                }

            } else {
                // Retain given search field if valid.
                $query = tx_dlf_solr::escapeQueryKeepField($this->piVars['query'], $this->conf['pages']);

            }

            // Add extended search query.
            if (!empty($this->piVars['extQuery']) && is_array($this->piVars['extQuery'])) {

                $allowedOperators = array ('AND', 'OR', 'NOT');

                $allowedFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['extendedFields'], TRUE);

                for ($i = 0; $i < count($this->piVars['extQuery']); $i++) {

                    if (!empty($this->piVars['extQuery'][$i])) {

                        if (in_array($this->piVars['extOperator'][$i], $allowedOperators) && in_array($this->piVars['extField'][$i], $allowedFields)) {

                            if (!empty($query)) {

                                $query .= ' '.$this->piVars['extOperator'][$i].' ';

                            }

                            $query .= tx_dlf_indexing::getIndexFieldName($this->piVars['extField'][$i], $this->conf['pages']).':('.tx_dlf_solr::escapeQuery($this->piVars['extQuery'][$i]).')';

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
            if ($this->conf['searchIn'] == 'document' || $this->conf['searchIn'] == 'all') {

                if (!empty($this->piVars['id']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->piVars['id'])) {

                    $params['filterquery'][]['query'] = 'uid:('.$this->piVars['id'].') OR partof:('.$this->piVars['id'].')';

                    $label .= htmlspecialchars(sprintf($this->pi_getLL('in', ''), tx_dlf_document::getTitle($this->piVars['id'])));

                }

            }

            // Add filter query for in-collection searching.
            if ($this->conf['searchIn'] == 'collection' || $this->conf['searchIn'] == 'all') {

                if (!empty($this->piVars['collection']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->piVars['collection'])) {

                    $index_name = tx_dlf_helper::getIndexName($this->piVars['collection'], 'tx_dlf_collections', $this->conf['pages']);

                    $params['filterquery'][]['query'] = 'collection_faceting:("'.tx_dlf_solr::escapeQuery($index_name).'")';

                    $label .= sprintf($this->pi_getLL('in', '', TRUE), tx_dlf_helper::translate($index_name, 'tx_dlf_collections', $this->conf['pages']));

                }

            }

            // Add filter query for collection restrictions.
            if ($this->conf['collections']) {

                $collIds = explode(',', $this->conf['collections']);

                $collIndexNames = array ();

                foreach ($collIds as $collId) {

                    $collIndexNames[] = tx_dlf_solr::escapeQuery(tx_dlf_helper::getIndexName(intval($collId), 'tx_dlf_collections', $this->conf['pages']));

                }

                // Last value is fake and used for distinction in $this->addCurrentCollection()
                $params['filterquery'][]['query'] = 'collection_faceting:("'.implode('" OR "', $collIndexNames).'" OR "FakeValueForDistinction")';

            }

            // Set search parameters.
            $solr->limit = max(intval($this->conf['limit']), 1);

            $solr->cPid = $this->conf['pages'];

            $solr->params = $params;

            // Perform search.
            $results = $solr->search($query);

            $results->metadata = array (
                'label' => $label,
                'description' => '<p class="tx-dlf-search-numHits">'.htmlspecialchars(sprintf($this->pi_getLL('hits', ''), $solr->numberOfHits, count($results))).'</p>',
                'thumbnail' => '',
                'searchString' => $this->piVars['query'],
                'fulltextSearch' => (!empty($this->piVars['fulltext']) ? '1' : '0'),
                'options' => $results->metadata['options']
            );

            $results->save();

            // Clean output buffer.
            \TYPO3\CMS\Core\Utility\GeneralUtility::cleanOutputBuffers();

            $additionalParams = array ();

            if (!empty($this->piVars['logicalPage'])) {

                $additionalParams['logicalPage'] = $this->piVars['logicalPage'];

            }

            // Jump directly to the page view, if there is only one result and it is configured
            if ($results->count() == 1 && !empty($this->conf['showSingleResult'])) {

                $linkConf['parameter'] = $this->conf['targetPidPageView'];

                $additionalParams['id'] = $results->current()['uid'];
                $additionalParams['highlight_word'] = preg_replace('/\s\s+/', ';', $results->metadata['searchString']);
                $additionalParams['page'] = count($results[0]['subparts']) == 1 ? $results[0]['subparts'][0]['page'] : 1;

            } else {

                // Keep some plugin variables.
                $linkConf['parameter'] = $this->conf['targetPid'];

                if (!empty($this->piVars['order'])) {

                    $additionalParams['order'] = $this->piVars['order'];
                    $additionalParams['asc'] = !empty($this->piVars['asc']) ? '1' : '0';

                }

            }

            $linkConf['additionalParams'] = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE);

            // Send headers.
            header('Location: '.\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL($linkConf)));

            // Flush output buffer and end script processing.
            ob_end_flush();

            exit;

        }

    }

    /**
     * This builds a menu array for HMENU
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	array		HMENU array
     */
    public function makeFacetsMenuArray($content, $conf) {

        $this->init($conf);

        $menuArray = array ();

        // Set default value for facet search.
        $search = array (
            'query' => '*',
            'params' => array (
                'component' => array (
                    'facetset' => array (
                        'facet' => array ()
                    )
                )
            )
        );

        // Extract query and filter from last search.
        $list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

        if (!empty($list->metadata['options']['source'])) {

            if ($list->metadata['options']['source'] == 'search') {

                $search['query'] = $list->metadata['options']['select'];

            }

            $search['params'] = $list->metadata['options']['params'];

        }

        // Get applicable facets.
        $solr = tx_dlf_solr::getInstance($this->conf['solrcore']);

        if (!$solr->ready) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_search->makeFacetsMenuArray('.$content.', [data])] Apache Solr not available', $this->extKey, SYSLOG_SEVERITY_ERROR, $conf);

            }

            return array ();

        }

        // Set needed parameters for facet search.
        if (empty($search['params']['filterquery'])) {

            $search['params']['filterquery'] = array ();

        }

        foreach ($this->conf['facets'] as $field => $name) {

            $search['params']['component']['facetset']['facet'][] = array (
                'type' => 'field',
                'key' => $field,
                'field' => $field,
                'limit' => $this->conf['limitFacets']

            );
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

        // Process results.
        foreach ($facet as $field => $values) {

            $entryArray = array ();

            $entryArray['title'] = htmlspecialchars($this->conf['facets'][$field]);

            $entryArray['count'] = 0;

            $entryArray['_OVERRIDE_HREF'] = '';

            $entryArray['doNotLinkIt'] = 1;

            $entryArray['ITEM_STATE'] = 'NO';

            // Count number of facet values.
            $i = 0;

            foreach ($values as $value => $count) {

                if ($count > 0) {

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
