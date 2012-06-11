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

	protected function getEntries($result, $facets, $lastSearch) {

		$menuArray = array ();

		// Process results.
		$hasOneValue = false;

		foreach ($result->facet_counts->facet_fields as $facetField => $facetValues) {

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

	protected function getLastQuery() {
		// Set last query if applicable.
		$lastQuery = array();

		$_list = t3lib_div::makeInstance('tx_dlf_list');

		if ($_list->metadata['options']['source'] !== 'collection') {

			$lastQuery['query'] = $_list->metadata['options']['select'];

		}

		$lastQuery['fq'] = $_list->metadata['options']['filter.query'];

		return $lastQuery;
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

		// Perform search if requested.
		if (!empty($this->piVars['fq'])) {

			t3lib_div::devLog('[main]   facets searching...', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

			$search = t3lib_div::makeInstance('tx_dlf_solr_search', $this->conf['solrcore'], $this->conf['pages']);

			$search->filterQuery = $this->piVars['fq'];

			$search->limit = 10000;

			$search->source = 'facets';

			$search->order = 'relevance';

			$list = t3lib_div::makeInstance('tx_dlf_list');

			if ($list->metadata['options']['source'] != 'search') {

				$search->restoreHeader();

			}

			$search->restoreQueryString();

			$list = tx_dlf_solr::search($search);

			// Clean output buffer.
			t3lib_div::cleanOutputBuffers();

			// Send headers.
			header('Location: '.t3lib_div::locationHeaderUrl($this->pi_getPageLink($this->conf['targetPid'])));

			// Flush output buffer and end script processing.
			ob_end_flush();

			exit;

		}

		t3lib_div::devLog('[main]   rendering facets', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

		// Render facets if no search is requested.
		$_TSconfig = array ();

		$_TSconfig['special'] = 'userfunction';

		$_TSconfig['special.']['userFunc'] = 'tx_dlf_facets->makeMenuArray';

		$_TSconfig = t3lib_div::array_merge_recursive_overrule($this->conf['menuConf.'], $_TSconfig);

		$markerArray['###FACET_MENU###'] = $this->cObj->HMENU($_TSconfig);

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

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
	public function makeMenuArray($content, $conf) {

		$this->init($conf);

		// extract search query
		$lastSearch = $this->getLastQuery();

		if (empty($lastSearch['query'])) {

			$lastSearch['query'] = '*';

		}

		t3lib_div::devLog('[makeMenuArray]   lastSearchFQ='.tx_dlf_helper::array_toString($lastSearch['fq']).', lastSearchQuery='.$lastSearch['query'], 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

		if (($solr = tx_dlf_solr::solrConnect($this->conf['solrcore'])) !== NULL) {

			// get facets to show from plugin configuration
			$facetsToShow = array();

			foreach (explode(',', $this->conf['facets']) as $facet ) {

				$facetsToShow[$facet] = tx_dlf_facet_helper::translateFacetField($facet, $this->conf['pages']);

			}

			// create facet query
			$facetParams = array(
					'facet' => 'true',
					'fq' => $lastSearch['fq'],
					'facet.field' => array()
			);

			foreach ($facetsToShow as $facetField => $facetName) {

				$facetParams['facet.field'][] = $facetField;

			}

			// 			t3lib_div::devLog('facetParams='.tx_dlf_helper::array_toString($facetParams).'  [makeMenuArray]', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

			// Perform search.
			$result = $solr->search($lastSearch['query'], 0, $this->conf['limit'], $facetParams);

			return $this->getEntries($result, $facetsToShow, $lastSearch);

		}

	}

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

				// t3lib_div::devLog('[renderMenuEntry]   fqPart'.tx_dlf_helper::array_toString($facetSelection), 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

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