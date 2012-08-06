<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2012, Zeutschel GmbH
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
 * Plugin 'DLF: Facets' for the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_facets extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/collection/class.tx_dlf_facets.php';

	/**
	 * Creates and returns an HMENU array based on a Solr search result.
	 * 
	 * @param array $lastSearch last search's properties
	 * @param array $facets facets to be shown, keys are facet index names, values are human-readable facet names
	 * 
	 * @return array HMENU array
	 */
	protected function getEntries($lastSearch, $facets) {

		$menuArray = array ();

		// Process results.
		$hasOneValue = false;

		foreach ($lastSearch['facet.fields'] as $facetField => $facetValues) {
			
			$valueContent = '';

			$hasOneValue = false;

			$entryArray = array();

			$entryArray['title'] = $facets[$facetField];

			$entryArray['doNotLinkIt'] = 1;

			$entryArray['ITEM_STATE'] = 'NO';

			foreach ($facetValues as $value_name => $value_count) {

				if ($value_count > 0) {

					$hasOneValue = true;

					$entryArray['_SUB_MENU'][] = $this->renderMenuEntry($facetField, $value_name, $value_count, $lastSearch);

				}

			}

			if (!$hasOneValue) {

				$entryArray['_SUB_MENU'][] = array(
						'title' => 'keine Einträge',
						'doNotLinkIt' => 1
				);

			}

			$menuArray[] = $entryArray;

		}

		return $menuArray;

	}

	/**
	 * The main method of the plugin.
	 *
	 * @access	public
	 *
	 * @param string $content the plugin content
	 * @param array $conf the plugin configuration
	 *
	 * @return string the content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		$this->setCache(FALSE);

		// Check for typoscript configuration to prevent fatal error.
		if (empty($this->conf['menuConf.'])) {

			trigger_error('No typoscript configuration for facet list available', E_USER_NOTICE);

			return $content;

		}

		// Quit without doing anything if required configuration variables are not set.
		if (empty($this->conf['pages']) || empty($this->conf['facets'])) {

			trigger_error('Incomplete configuration for plugin '.get_class($this), E_USER_NOTICE);

			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/facets/template.tmpl'), '###TEMPLATE###');

		}

		// Get facets to show from plugin configuration
		$facetsToShow = array();
			
		foreach (explode(',', $this->conf['facets']) as $facet ) {
				
			$facetsToShow[$facet] = tx_dlf_facet_helper::translateFacetField($facet, $this->conf['pages']);
				
		}
		
		// Perform search if requested.
		if (!empty($this->piVars['fq'])) {

			t3lib_div::devLog('[tx_dlf_facets.main]   searching...', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

			$search = t3lib_div::makeInstance('tx_dlf_solr_search', $this->conf['solrcore'], $this->conf['pages']);

			$search->filterQuery = $this->piVars['fq'];

			// extract facet fields
			$facetFields = array();
			
			foreach ($facetsToShow as $facetField => $facetName) {
			
				$facetFields[] = $facetField;
			
			}
			
			$search->facetFields = $facetFields;
			
			$search->limit = 10000;

			$search->source = 'facets';

			$search->order = 'relevance';

			$list = t3lib_div::makeInstance('tx_dlf_list');

			// Restore header data, when facetted is used with list generating plugins other than the search plugin.
			if ($list->metadata['options']['source'] != 'search') {

				$search->restoreHeader();

			}

			// Respect the last queries search string in the following search. 
			$search->restoreQueryString();

			// Perform search.
			$list = tx_dlf_solr::search($search);

			// Clean output buffer.
			t3lib_div::cleanOutputBuffers();

			// Send headers.
			header('Location: '.t3lib_div::locationHeaderUrl($this->pi_getPageLink($this->conf['targetPid'])));

			// Flush output buffer and end script processing.
			ob_end_flush();

			exit;

		}

		t3lib_div::devLog('[tx_dlf_facets.main]   rendering facets', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

		// Render facets if no search is requested.
		$_TSconfig = array ();

		$_TSconfig['special'] = 'userfunction';

		$_TSconfig['special.']['userFunc'] = 'tx_dlf_facets->makeMenuArray';
		
		$_TSconfig['special.']['facetsToShow'] = $facetsToShow;

		$_TSconfig = t3lib_div::array_merge_recursive_overrule($this->conf['menuConf.'], $_TSconfig);

		$markerArray['###FACET_MENU###'] = $this->cObj->HMENU($_TSconfig);

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);
		
		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * This builds a menu array for HMENU.
	 *
	 * @access public
	 *
	 * @param string $content the plugin content
	 * @param array $conf the plugin configuration
	 *
	 * @return array HMENU array
	 */
	public function makeMenuArray($content, $conf) {

		$this->init($conf);

		// Extract last search's details.
		$list = t3lib_div::makeInstance('tx_dlf_list');
		
		$lastSearch = array();
		
		if ($list->metadata['options']['source'] !== 'collection') {
		
			$lastSearch['query'] = $list->metadata['options']['select'];
		
		} else {
				
			// Ensure that rendered facet-links contain a query string.
			$lastSearch['query'] = '*';
				
		}
		
		$lastSearch['fq'] = $list->metadata['options']['filter.query'];
		
		$lastSearch['facet.fields'] = unserialize($list->metadata['result']['facet.fields']);
		
		/* 
		 * No facet fields in search result (although they've been configured) means that 
		 * the previous search wasn't performed by this plugin instance. Thus, we need to 
		 * perform the last search again including facet field requests to get facet data.
		 * 
		 * If there is a better solution to this, don't hesitate to discuss. An option
		 * would be a hook-based search but, however, the corresponding hook would have
		 * to be configured by the plugin instance that actually displays facets. This,
		 * in turn, remains in the TSFE rendering cycle.
		 */
		if (empty($lastSearch['facet.fields'])) {
			
			t3lib_div::devLog('[tx_dlf_facets.makeMenuArray]   no facet fields in last search found: performing 2nd search', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

			$solr = tx_dlf_solr::solrConnect($this->conf['solrcore']);
			
			if ($solr === NULL) {
				
				trigger_error('Could not connect to solr instance.', E_USER_NOTICE);
				
				return array();
				
			}
		
			// create facet query
			$facetParams = array(
					'facet' => 'true',
					'fq' => $lastSearch['fq'],
					'facet.field' => array()
			);
		
			foreach ($this->conf['facetsToShow'] as $facetField => $facetName) {
		
				$facetParams['facet.field'][] = $facetField;
		
			}
		
			// Perform search.
			$result = $solr->search($lastSearch['query'], 0, $this->conf['limit'], $facetParams);

			$lastSearch['facet.fields'] = $result->facet_counts->facet_fields;
						
		}
		
		return $this->getEntries($lastSearch, $this->conf['facetsToShow']);
		
	}

	/**
	 * Creates an array for a HMENU entry of a facet value. 
	 * 
	 * @param string $facetField the facet index name
	 * @param string $value facet field's value
	 * @param integer $valueCount number of hits of the passed facet field's value
	 * @param array $lastSearch
	 * 
	 * @return multitype:number string 
	 */
	protected function renderMenuEntry($facetField, $value, $valueCount, $lastSearch) {

		$entryArray = array();

		$renderedValue = tx_dlf_facet_helper::translateFacetValue($facetField, $value, $this->conf['pages']);

		$entryArray['doNotLinkIt'] = 0;

		$entryArray['ITEM_STATE'] = 'NO';

		// append value count
		$renderedValue .= '&nbsp;('.$valueCount.')';

		$selectedIndex = -1;

		$i = 0;

		// Wrap value.
		$value = '"'.$value.'"';

		if (count($lastSearch['fq']) > 0) {

			// check if given facet is already selected
			foreach ($lastSearch['fq'] as $fqPart) {

				$facetSelection = explode(':', $fqPart, 2);

				if (count($facetSelection) == 2) {

					if ($facetField == $facetSelection[0] && $value == $facetSelection[1]) {

						$selectedIndex = $i;

						break;

					}

				}

				$i++;

			}

		}

		// the given value is selected, prepare deselected filter query
		if ($selectedIndex > -1) {

			if (!is_array($lastSearch['fq'])) {

				$lastSearch['fq'] = NULL;

			} else {

				unset($lastSearch['fq'][$selectedIndex]);

				$lastSearch['fq'] = array_values($lastSearch['fq']);

			}

			$entryArray['ITEM_STATE'] = 'ACT';

		}
		// the given value is NOT selected, prepare selection filter query
		else {

			if (empty($lastSearch['fq'])) {

				$lastSearch['fq'] = array();

			} else if (!is_array($lastSearch['fq'])) {

				$lastSearch['fq'] = array( $lastSearch['fq'] );

			}

			$lastSearch['fq'][] = ($facetField.':'.$value);

		}

		$entryArray['title'] = $renderedValue;

		$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array('query' => $lastSearch['query'], 'fq' => $lastSearch['fq']));

		return $entryArray;

	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/facets/class.tx_dlf_facets.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/facets/class.tx_dlf_facets.php']);
}

?>