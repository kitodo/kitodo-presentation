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
	 * @return	boolean		TRUE on success or FALSE on error
	 */
	protected function addAutocompleteJS() {

		// Ensure extension "t3jquery" is available.
		if (t3lib_extMgm::isLoaded('t3jquery')) {

			require_once(t3lib_extMgm::extPath('t3jquery').'class.tx_t3jquery.php');

		}

		// Is "t3jquery" loaded and the custom library created?
		if (T3JQUERY === TRUE) {

			tx_t3jquery::addJqJS();

			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_search_suggest'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'plugins/search/tx_dlf_search_suggest.js"></script>';

			return TRUE;

		} else {

			// No autocompletion available!
			return FALSE;

		}

	}

	/**
	 * Adds the encrypted Solr core name to the search form
	 *
	 * @access	protected
	 *
	 * @param	integer		$core: UID of the core
	 *
	 * @return	string		HTML input fields with encrypted core name and hash
	 */
	protected function addEncryptedCoreName($core) {

		// Get core name.
		$name = tx_dlf_helper::getIndexName($core, 'tx_dlf_solrcores');

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

		if (empty($this->piVars['query'])) {

			// Add javascript for autocompletion if available.
			$autocomplete = $this->addAutocompleteJS();

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

			// Fill markers.
			$markerArray = array (
				'###ACTION_URL###' => $this->pi_getPageLink($GLOBALS['TSFE']->id),
				'###LABEL_QUERY###' => $this->pi_getLL('label.query'),
				'###LABEL_SUBMIT###' => $this->pi_getLL('label.submit'),
				'###FIELD_QUERY###' => $this->prefixId.'[query]',
				'###QUERY###' => htmlspecialchars($lastQuery),
				'###ADDITIONAL_INPUTS###' => '',
			);

			// Encrypt Solr core name and add as hidden input field to the search form.
			if ($autocomplete) {

				$markerArray['###ADDITIONAL_INPUTS###'] = $this->addEncryptedCoreName($this->conf['solrcore']);

			}

			// Display search form.
			$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

			return $this->pi_wrapInBaseClass($content);

		} elseif (($solr = tx_dlf_solr::solrConnect($this->conf['solrcore'])) !== NULL) {

			// Perform search.
			$query = $solr->search($this->piVars['query'], 0, $this->conf['limit'], array ());

			$numHits = count($query->response->docs);

			// Set metadata for search.
			$listMetadata = array (
				'label' => htmlspecialchars(sprintf($this->pi_getLL('searchfor', ''), $this->piVars['query'])),
				'description' => '<p class="tx-dlf-search-numHits">'.htmlspecialchars(sprintf($this->pi_getLL('hits', ''), $numHits)).'</p>',
				'options' => array (
					'source' => 'search',
					'select' => $this->piVars['query'],
					'order' => 'relevance'
				)
			);

			$toplevel = array ();

			$checks = array ();

			// Get metadata configuration.
			if ($numHits) {

				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.tokenized AS tokenized,tx_dlf_metadata.indexed AS indexed,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.is_sortable AS is_sortable',
					'tx_dlf_metadata',
					'(tx_dlf_metadata.is_listed=1 OR tx_dlf_metadata.is_sortable=1) AND tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata'),
					'',
					'tx_dlf_metadata.sorting ASC',
					''
				);

				$metadata = array ();

				$sorting = array ();

				while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

					if ($resArray['is_listed']) {

						$metadata[$resArray['index_name']] = $resArray['index_name'].'_'.($resArray['tokenized'] ? 't' : 'u').'s'.($resArray['indexed'] ? 'i' : 'u');

					}

					if ($resArray['is_sortable']) {

						$sorting[$resArray['index_name']] = $resArray['index_name'].'_sorting';

					}

				}

			}

			// Keep track of relevance.
			$i = 0;

			// Process results.
			foreach ($query->response->docs as $doc) {

				// Prepate document's metadata.
				$docMeta = array ();

				foreach ($metadata as $index_name => $solr_name) {

					if (!empty($doc->$solr_name)) {

						$docMeta[$index_name] = (is_array($doc->$solr_name) ? $doc->$solr_name : array ($doc->$solr_name));

					}

				}

				// Prepate document's metadata for sorting.
				$docSorting = array ();

				foreach ($sorting as $index_name => $solr_name) {

					if (!empty($doc->$solr_name)) {

						$docSorting[$index_name] = (is_array($doc->$solr_name) ? $doc->$solr_name[0] : $doc->$solr_name);

					}

				}

				// Add relevance to sorting values.
				$docSorting['relevance'] = str_pad($i, 6, '0', STR_PAD_LEFT);

				// Split toplevel documents from subparts.
				if ($doc->toplevel == 1) {

					$toplevel[$doc->uid] = array (
						'uid' => $doc->uid,
						'page' => $doc->page,
						'metadata' => $docMeta,
						'sorting' => $docSorting,
						'subparts' => (!empty($toplevel[$doc->uid]['subparts']) ? $toplevel[$doc->uid]['subparts'] : array ())
					);

				} else {

					$toplevel[$doc->uid]['subparts'][] = array (
						'uid' => $doc->uid,
						'page' => $doc->page,
						'metadata' => $docMeta,
						'sorting' => $docSorting
					);

					if (!in_array($doc->uid, $check)) {

						$checks[] = $doc->uid;

					}

				}

				$i++;

			}

			// Check if the toplevel documents have metadata.
			foreach ($checks as $check) {

				if (empty($toplevel[$check]['uid'])) {

					// Get information for toplevel document.
					$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'tx_dlf_documents.uid AS uid,tx_dlf_documents.metadata AS metadata,tx_dlf_documents.metadata_sorting AS metadata_sorting',
						'tx_dlf_documents',
						'tx_dlf_documents.uid='.intval($check).tx_dlf_helper::whereClause('tx_dlf_documents'),
						'',
						'',
						'1'
					);

					// Process results.
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

						$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

						// Prepare document's metadata.
						$metadata = unserialize($resArray['metadata']);

						if (!empty($metadata['type'][0]) && t3lib_div::testInt($metadata['type'][0])) {

							$metadata['type'][0] = tx_dlf_helper::getIndexName($metadata['type'][0], 'tx_dlf_structures', $this->conf['pages']);

						}

						if (!empty($metadata['owner'][0]) && t3lib_div::testInt($metadata['owner'][0])) {

							$metadata['owner'][0] = tx_dlf_helper::getIndexName($metadata['owner'][0], 'tx_dlf_libraries', $this->conf['pages']);

						}

						if (!empty($metadata['collection']) && is_array($metadata['collection'])) {

							foreach ($metadata['collection'] as $i => $collection) {

								if (t3lib_div::testInt($collection)) {

									$metadata['collection'][$i] = tx_dlf_helper::getIndexName($metadata['collection'][$i], 'tx_dlf_collections', $this->conf['pages']);

								}

							}

						}

						// Prepare document's metadata for sorting.
						$sorting = unserialize($resArray['metadata_sorting']);

						if (!empty($sorting['type']) && t3lib_div::testInt($sorting['type'])) {

							$sorting['type'] = tx_dlf_helper::getIndexName($sorting['type'], 'tx_dlf_structures', $this->conf['pages']);

						}

						if (!empty($sorting['owner']) && t3lib_div::testInt($sorting['owner'])) {

							$sorting['owner'] = tx_dlf_helper::getIndexName($sorting['owner'], 'tx_dlf_libraries', $this->conf['pages']);

						}

						if (!empty($sorting['collection']) && t3lib_div::testInt($sorting['collection'])) {

							$sorting['collection'] = tx_dlf_helper::getIndexName($sorting['collection'], 'tx_dlf_collections', $this->conf['pages']);

						}

						$toplevel[$check] = array (
							'uid' => $resArray['uid'],
							'page' => 1,
							'metadata' => $metadata,
							'sorting' => $sorting,
							'subparts' => $toplevel[$check]['subparts']
						);

					} else {

						// Clear entry if there is no (accessible) toplevel document.
						unset ($toplevel[$check]);

					}

				}

			}

			// Save list of documents.
			$list = t3lib_div::makeInstance('tx_dlf_list');

			$list->reset();

			$list->add(array_values($toplevel));

			$list->metadata = $listMetadata;

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