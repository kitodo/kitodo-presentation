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

		if (empty($this->piVars['query'])) {

			// Load template file.
			if (!empty($this->conf['templateFile'])) {

				$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

			} else {

				$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/search/template.tmpl'), '###TEMPLATE###');

			}

			// Set last query if applicable.
			$lastQuery = '';

			$_list = t3lib_div::makeInstance('tx_dlf_list');

			if (!empty($_list->metadata['options']['source']) && $_list->metadata['options']['source'] == 'search') {

				$lastQuery = $_list->metadata['options']['select'];

			}

			// Fill markers.
			$markerArray = array (
				'###ACTION_URL###' => $this->pi_getPageLink($GLOBALS['TSFE']->id),
				'###LABEL_QUERY###' => $this->pi_getLL('label.query'),
				'###LABEL_SUBMIT###' => $this->pi_getLL('label.submit'),
				'###FIELD_QUERY###' => $this->prefixId.'[query]',
				'###QUERY###' => htmlspecialchars($lastQuery),
			);

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
				'label' => htmlspecialchars(sprintf($this->pi_getLL('searchfor', ''), $this->piVars['query'])),
				'description' => '<p class="tx-dlf-search-numHits">'.htmlspecialchars(sprintf($this->pi_getLL('hits', ''), $numHits)).'</p>',
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
						'subparts' => (!empty($toplevel[$doc->uid]['subparts']) ? $toplevel[$doc->uid]['subparts'] : array ()),
						'thumbnail' => $doc->thumbnail_usi
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
						'tx_dlf_documents.uid AS uid,tx_dlf_documents.title AS title,tx_dlf_documents.volume AS volume,tx_dlf_documents.author AS author,tx_dlf_documents.place AS place,tx_dlf_documents.year AS year,tx_dlf_documents.structure AS type,tx_dlf_documents.thumbnail AS thumbnail',
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
							'subparts' => $toplevel[$_check]['subparts'],
							'thumbnail' => $resArray['thumbnail']
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