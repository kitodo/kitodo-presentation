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
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>, Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_search extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/search/class.tx_dlf_search.php';

	/**
	 * Adds the HTML content for two hidden input fields, namely 'encrypted' and 'hashed', containing 
	 * the index_name if the passed solrCore number, to the passed marker arrays value
	 * '###ADDITIONAL_INPUTS###'. This is necessary to savely render search suggestions.
	 *
	 * @access	protected
	 *
	 * @return	array
	 */
	protected function addEncryptedSolrCore($markerArray, $solrCore) {
	
		// Extract internal dlf core name.
		$table = 'tx_dlf_solrcores';
		
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$table.'.index_name AS index_name',
				$table,
				$table.'.uid='.$solrCore.tx_dlf_helper::whereClause($table),
				'',
				'',
				'1'
		);
		
		$dlfCoreName = '';
		
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result) > 0) {
		
			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);
		
			$dlfCoreName = $resArray['index_name'];
		
		}
		
		if (empty($dlfCoreName)) {
			
			trigger_error('Failed to query for internal solr core name of core number '.$solrCore, E_USER_NOTICE);
			
			return;
			
		}

		$encryptedCore = tx_dlf_search_suggest::encrypt($dlfCoreName);

		if (empty($encryptedCore)) {
			
			$markerArray['###ADDITIONAL_INPUTS###'] = '';

		} else {
			
			$markerArray['###ADDITIONAL_INPUTS###'] = '<input type="hidden" name="encrypted" value="'.$encryptedCore['value'].'"/>'
				.'<input type="hidden" name="hashed" value="'.$encryptedCore['hash'].'"/>';
			
		}
	
		return $markerArray;

	}

	/**
	 * Adds the JS files necessary for search sugestions to the
	 * page header.
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function addSuggestSupport() {
		// ensure jquery is loaded
		if (t3lib_extMgm::isLoaded('t3jquery')) {
			require_once(t3lib_extMgm::extPath('t3jquery').'class.tx_t3jquery.php');
		}
	
		// if t3jquery is loaded and the custom Library had been created
		if (T3JQUERY === true) {
			tx_t3jquery::addJqJS();
		} else {
			/*
			 * suggestions will not work; we don't indicate an no error
			* because this is caused by insufficient configuration and
			* this feature does not harm the rest of the framework's
			* functionality
			*/
			return;
		}
	
		$libs = array(
				"search_suggest" => "search_suggest.js"
		);
	
		foreach ($libs as $lib_key => $lib_file) {
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.$lib_key] = '	<script type="text/javascript" src="'
			.t3lib_extMgm::siteRelPath($this->extKey)
			.'plugins/search/'.$lib_file
			.'"></script>';
		}
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
		
			trigger_error('Incomplete configuration for plugin '.get_class($this), E_USER_NOTICE);

			return $content;

		}
		
		if (empty($this->piVars['query'])) {
			
			// Add suggest JavaScript file.
			$this->addSuggestSupport();
			
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
			
			// Encrypt solr core name and add as hidden input field to marker array to enable for search suggestions.
			$markerArray = $this->addEncryptedSolrCore($markerArray, $this->conf['solrcore']);
			
			// Display search form.
			$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

			return $this->pi_wrapInBaseClass($content);

		} elseif (($solr = tx_dlf_solr::solrConnect($this->conf['solrcore'])) !== NULL) {

			// Perform search.
			$query = $solr->search($this->piVars['query'], 0, $this->conf['limit'], array ());

			$numHits = count($query->response->docs);

			$_list = array ();

			// Set metadata for search.
			$_metadata = array (
				'label' => sprintf($this->pi_getLL('searchfor', ''), $this->piVars['query']),
				'description' => sprintf($this->pi_getLL('hits', ''), $numHits),
				'options' => array (
					'source' => 'search',
					'select' => $this->piVars['query'],
					'order' => 'relevance'
				)
			);

			$toplevel = array ();

			$check = array ();

			// Process results.
			foreach ($query->response->docs as $doc) {

				// Split toplevel documents from subparts.
				if ($doc->toplevel == 1) {

					$toplevel[$doc->uid] = array (
						'uid' => $doc->uid,
						'page' => $doc->page,
						'title' => (is_array($doc->title) ? $doc->title : array ($doc->title)),
						'volume' => (is_array($doc->volume) ? $doc->volume : array ($doc->volume)),
						'author' => (is_array($doc->author) ? $doc->author : array ($doc->author)),
						'year' => (is_array($doc->year) ? $doc->year : array ($doc->year)),
						'place' => (is_array($doc->place) ? $doc->place : array ($doc->place)),
						'type' => (is_array($doc->type) ? $doc->type : array ($doc->type)),
						'subparts' => (!empty($toplevel[$doc->uid]['subparts']) ? $toplevel[$doc->uid]['subparts'] : array ())
					);

				} else {

					$toplevel[$doc->uid]['subparts'][] = array (
						'uid' => $doc->uid,
						'page' => $doc->page,
						'title' => (is_array($doc->title) ? $doc->title : array ($doc->title)),
						'volume' => (is_array($doc->volume) ? $doc->volume : array ($doc->volume)),
						'author' => (is_array($doc->author) ? $doc->author : array ($doc->author)),
						'year' => (is_array($doc->year) ? $doc->year : array ($doc->year)),
						'place' => (is_array($doc->place) ? $doc->place : array ($doc->place)),
						'type' => (is_array($doc->type) ? $doc->type : array ($doc->type))
					);

					if (!in_array($doc->uid, $check)) {

						$check[] = $doc->uid;

					}

				}

			}

			// Check if the toplevel documents have metadata.
			foreach ($check as $_check) {

				if (empty($toplevel[$_check]['uid'])) {

					// Get information for toplevel document.
					$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'tx_dlf_documents.uid AS uid,tx_dlf_documents.title AS title,tx_dlf_documents.volume AS volume,tx_dlf_documents.author AS author,tx_dlf_documents.place AS place,tx_dlf_documents.year AS year,tx_dlf_documents.structure AS type',
						'tx_dlf_documents',
						'tx_dlf_documents.uid='.intval($_check).tx_dlf_helper::whereClause('tx_dlf_documents'),
						'',
						'',
						'1'
					);

					// Process results.
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

						$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

						$toplevel[$_check] = array (
							'uid' => $resArray['uid'],
							'page' => 1,
							'title' => array ($resArray['title']),
							'volume' => array ($resArray['volume']),
							'author' => array ($resArray['author']),
							'year' => array ($resArray['year']),
							'place' => array ($resArray['place']),
							'type' => array (tx_dlf_helper::getIndexName($resArray['type'], 'tx_dlf_structures', $this->conf['pages'])),
							'subparts' => $toplevel[$_check]['subparts']
						);

					} else {

						// Clear entry if there is no (accessible) toplevel document.
						unset ($toplevel[$_check]);

					}

				}

			}

			// Save list of documents.
			$list = t3lib_div::makeInstance('tx_dlf_list');

			$list->reset();

			$list->add(array_values($toplevel));

			$list->metadata = $_metadata;

			$list->save();

			// Clean output buffer.
			t3lib_div::cleanOutputBuffers();

			// Send headers.
			header('Location: '.t3lib_div::locationHeaderUrl($this->pi_getPageLink($this->conf['targetPid'])));

			// Flush output buffer and end script processing.
			ob_end_flush();

			exit;

		}

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search.php']);
}

?>