<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Plugin 'DLF: Search' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_search extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/search/class.tx_dlf_search.php';

	/**
	 * Adds the JS files necessary for autocompletion
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
			'tx_dlf_metadata.autocomplete=1 AND tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'',
			'1'
		);

		// Ensure extension "t3jquery" is available.
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) && t3lib_extMgm::isLoaded('t3jquery')) {

			require_once(t3lib_extMgm::extPath('t3jquery').'class.tx_t3jquery.php');

		}

		// Is "t3jquery" loaded?
		if (T3JQUERY === TRUE) {

			tx_t3jquery::addJqJS();

			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_search_suggest'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'plugins/search/tx_dlf_search_suggest.js"></script>';

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
		$list = t3lib_div::makeInstance('tx_dlf_list');

		if (!empty($list->metadata['options']['source']) && $list->metadata['options']['source'] == 'collection') {

			// Get collection's UID.
			return '<input type="hidden" name="'.$this->prefixId.'[collection]" value="'.$list->metadata['options']['select'].'" />';

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

		// Load current document.
		if (!empty($this->piVars['id']) && t3lib_div::testInt($this->piVars['id'])) {

			$this->loadDocument();

			// Get document's UID or parent ID.
			if ($this->doc->ready) {

				return '<input type="hidden" name="'.$this->prefixId.'[id]" value="'.($this->doc->parentId > 0 ? $this->doc->parentId : $this->doc->uid).'" />';

			}

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

				t3lib_div::devLog('[tx_dlf_search->addFacetsMenu()] Incomplete plugin configuration', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

			return '';

		}

		// Quit without doing anything if no facets are selected.
		if (empty($this->conf['facets'])) {

			return '';

		}

		// Get facets from plugin configuration.
		$facets = array ();

		foreach (t3lib_div::trimExplode(',', $this->conf['facets'], TRUE) as $facet) {

			$facets[$facet.'_faceting'] = tx_dlf_helper::translate($facet, 'tx_dlf_metadata', $this->conf['pages']);

		}

		// Render facets menu.
		$TSconfig = array ();

		$TSconfig['special'] = 'userfunction';

		$TSconfig['special.']['userFunc'] = 'tx_dlf_search->makeFacetsMenuArray';

		$TSconfig['special.']['facets'] = $facets;

		$TSconfig['special.']['limit'] = max(intval($this->conf['limitFacets']), 1);

		$TSconfig = t3lib_div::array_merge_recursive_overrule($this->conf['facetsConf.'], $TSconfig);

		return $this->cObj->HMENU($TSconfig);

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

		$entryArray = array();

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
		$index = array_search($field.':"'.$value.'"', $search['params']['fq']);

		if ($index !== FALSE) {

			// Facet is selected, thus remove it from filter.
			unset($search['params']['fq'][$index]);

			$search['params']['fq'] = array_values($search['params']['fq']);

			$entryArray['ITEM_STATE'] = 'CUR';

			$state = 'ACTIFSUB';

		} else {

			// Facet is not selected, thus add it to filter.
			$search['params']['fq'][] = $field.':"'.$value.'"';

			$entryArray['ITEM_STATE'] = 'NO';

		}

		$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('query' => $search['query'], 'fq' => $search['params']['fq']));

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

				t3lib_div::devLog('[tx_dlf_search->main('.$content.', [data])] Incomplete plugin configuration', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);

			}

			return $content;

		}

		if (!isset($this->piVars['query'])) {

			// Add javascript for autocompletion if available.
			$this->addAutocompleteJS();

			// Load template file.
			if (!empty($this->conf['templateFile'])) {

				$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

			} else {

				$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/search/template.tmpl'), '###TEMPLATE###');

			}

			// Set last query if applicable.
			$lastQuery = '';

			$list = t3lib_div::makeInstance('tx_dlf_list');

			if (!empty($list->metadata['options']['source']) && $list->metadata['options']['source'] == 'search') {

				$lastQuery = $list->metadata['options']['select'];

			}

			// Configure @action URL for form.
			$linkConf = array (
				'parameter' => $GLOBALS['TSFE']->id,
				'forceAbsoluteUrl' => 1
			);

			// Fill markers.
			$markerArray = array (
				'###ACTION_URL###' => $this->cObj->typoLink_URL($linkConf),
				'###LABEL_QUERY###' => $this->pi_getLL('label.query'),
				'###LABEL_SUBMIT###' => $this->pi_getLL('label.submit'),
				'###FIELD_QUERY###' => $this->prefixId.'[query]',
				'###QUERY###' => htmlspecialchars($lastQuery),
				'###FIELD_DOC###' => $this->addCurrentDocument(),
				'###FIELD_COLL###' => $this->addCurrentCollection(),
				'###ADDITIONAL_INPUTS###' => $this->addEncryptedCoreName(),
				'###FACETS_MENU###' => $this->addFacetsMenu()
			);

			// Display search form.
			$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

			return $this->pi_wrapInBaseClass($content);

		} else {

			// Instantiate search object.
			$solr = tx_dlf_solr::getInstance($this->conf['solrcore']);

			if (!$solr->ready) {

				if (TYPO3_DLOG) {

					t3lib_div::devLog('[tx_dlf_search->main('.$content.', [data])] Apache Solr not available', $this->extKey, SYSLOG_SEVERITY_ERROR, $conf);

				}

				return $content;

			}

			// Build label for result list.
			$label = $this->pi_getLL('search', '', TRUE);

			if (!empty($this->piVars['query'])) {

				$label .= htmlspecialchars(sprintf($this->pi_getLL('for', ''), $this->piVars['query']));

			}

			// Set search parameters.
			$solr->limit = max(intval($this->conf['limit']), 1);

			$solr->cPid = $this->conf['pages'];

			// Set query parameters.
			$params = array ();

			// Add filter query for faceting.
			if (!empty($this->piVars['fq'])) {

				$params = array ('fq' => $this->piVars['fq']);

			}

			// Add filter query for in-document searching.
			if ($this->conf['searchIn'] == 'document' || $this->conf['searchIn'] == 'all') {

				if (!empty($this->piVars['id']) && t3lib_div::testInt($this->piVars['id'])) {

					$params['fq'][] = 'uid:'.$this->piVars['id'].' OR partof:'.$this->piVars['id'];

					$label .= htmlspecialchars(sprintf($this->pi_getLL('in', ''), tx_dlf_document::getTitle($this->piVars['id'])));

				}

			}

			// Add filter query for in-collection searching.
			if ($this->conf['searchIn'] == 'collection' || $this->conf['searchIn'] == 'all') {

				if (!empty($this->piVars['collection']) && t3lib_div::testInt($this->piVars['collection'])) {

					$index_name = tx_dlf_helper::getIndexName($this->piVars['collection'], 'tx_dlf_collections', $this->conf['pages']);

					$params['fq'][] = 'collection_faceting:"'.$index_name.'"';

					$label .= sprintf($this->pi_getLL('in', '', TRUE), tx_dlf_helper::translate($index_name, 'tx_dlf_collections', $this->conf['pages']));

				}

			}

			$solr->params = $params;

			// Perform search.
			$results = $solr->search($this->piVars['query']);

			$results->metadata = array (
				'label' => $label,
				'description' => '<p class="tx-dlf-search-numHits">'.htmlspecialchars(sprintf($this->pi_getLL('hits', ''), $solr->numberOfHits, $results->count)).'</p>',
				'options' => $results->metadata['options']
			);

			$results->save();

			// Clean output buffer.
			t3lib_div::cleanOutputBuffers();

			// Send headers.
			header('Location: '.t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(array ('parameter' => $this->conf['targetPid']))));

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
			'params' => array ()
		);

		// Extract query and filter from last search.
		$list = t3lib_div::makeInstance('tx_dlf_list');

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

				t3lib_div::devLog('[tx_dlf_search->makeFacetsMenuArray('.$content.', [data])] Apache Solr not available', $this->extKey, SYSLOG_SEVERITY_ERROR, $conf);

			}

			return array ();

		}

		// Set needed parameters for facet search.
		if (empty($search['params']['fq'])) {

			$search['params']['fq'] = array ();

		}

		$search['params']['facet'] = 'true';

		$search['params']['facet.field'] = array_keys($this->conf['facets']);

		// Perform search.
		$results = $solr->service->search($search['query'], 0, $this->conf['limit'], $search['params']);

		// Process results.
		foreach ($results->facet_counts->facet_fields as $field => $values) {

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

					$hasValue = TRUE;

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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php']);
}

?>