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
* @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
* @package	TYPO3
* @subpackage	tx_dlf
* @access	public
*/
class tx_dlf_search extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/search/class.tx_dlf_search.php';

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

			trigger_error('Incomplete configuration for plugin '.get_class($this), E_USER_NOTICE);

			return $content;

		}

		// Perform search if requested.
		if (!empty($this->piVars['query'])) {

			t3lib_div::devLog('[tx_dlf_search.main]   searching...', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

			$search = t3lib_div::makeInstance('tx_dlf_solr_search', $this->conf['solrcore'], $this->conf['pages'], $this->piVars['query']);

			$search->limit = $this->conf['limit'];

			$search->source = 'search';

			$search->order = 'relevance';

			// TODO: we need a flag for respecting the FQ value (facilitates search in facet space)
		    // $search->restoreFilterQuery();

			$list = tx_dlf_solr::search($search);

			// Clean output buffer.
			t3lib_div::cleanOutputBuffers();

			// Send headers.
			header('Location: '.t3lib_div::locationHeaderUrl($this->pi_getPageLink($this->conf['targetPid'])));

			// Flush output buffer and end script processing.
			ob_end_flush();

			exit;

		}

		t3lib_div::devLog('[tx_dlf_search.main]   rendering search form', 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

		// Render search form if no search is requested.
		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/search/template.tmpl'), '###TEMPLATE###');

		}

		// Fill markers.
		$markerArray = array (
				'###ACTION_URL###' => $this->pi_getPageLink($GLOBALS['TSFE']->id),
				'###LABEL_QUERY###' => $this->pi_getLL('label.query'),
				'###LABEL_SUBMIT###' => $this->pi_getLL('label.submit'),
				'###FIELD_QUERY###' => $this->prefixId.'[query]',
				'###QUERY###' => '',
		);

		// Display search form.
		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php']);
}

?>