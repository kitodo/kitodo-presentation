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

		if (empty($this->piVars['query'])) {

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

		} else {

			$solr = tx_dlf_solr::solrConnect($this->conf['solrcore']);

			$query = $solr->search($this->piVars['query'], 0, $this->conf['limit'], array ());

			$_list = array ();

			$_metadata = array (
				'uid' => 0,
				'label' => sprintf($this->pi_getLL('searchfor', ''), $this->piVars['query']),
				'description' => '',
				'options' => array ()
			);

			foreach ($query->response->docs as $doc) {

				$_list[] = array (
					'uid' => $doc->uid,
					'page' => $doc->page,
					'title' => array ($doc->title),
					'volume' => array ($doc->volume),
					'author' => array ($doc->author),
					'year' => array ($doc->year),
					'place' => array ($doc->place),
					'type' => array ($doc->type),
					'subparts' => array ()
				);

			}

			$list = t3lib_div::makeInstance('tx_dlf_list');

			$list->reset();

			$list->add($_list);

			$list->metadata = $_metadata;

			$list->save();

			header('Location: '.t3lib_div::locationHeaderUrl($this->pi_getPageLink($this->conf['targetPid'])));

			exit;

		}

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php']);
}

?>