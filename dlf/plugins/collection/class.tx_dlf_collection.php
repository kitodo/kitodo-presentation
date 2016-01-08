<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
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
 * Plugin 'DLF: Collection' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_collection extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/collection/class.tx_dlf_collection.php';

	/**
	 * This holds the hook objects
	 *
	 * @var	array
	 * @access protected
	 */
	protected $hookObjects = array ();

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

		// Turn cache on.
		$this->setCache(TRUE);

		// Quit without doing anything if required configuration variables are not set.
		if (empty($this->conf['pages'])) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_collection->main('.$content.', [data])] Incomplete plugin configuration', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);

			}

			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/collection/template.tmpl'), '###TEMPLATE###');

		}

		// Get hook objects.
		$this->hookObjects = tx_dlf_helper::getHookObjects($this->scriptRelPath);

		if (!empty($this->piVars['collection'])) {

			$this->showSingleCollection(intval($this->piVars['collection']));

		} else {

			$content .= $this->showCollectionList();

		}

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Builds a collection list
	 *
	 * @access	protected
	 *
	 * @return	string		The list of collections ready to output
	 */
	protected function showCollectionList() {

		$additionalWhere = '';

		$orderBy = 'tx_dlf_collections.label';

		// Handle collections set by configuration.
		if ($this->conf['collections']) {

			if (count(explode(',', $this->conf['collections'])) == 1 && empty($this->conf['dont_show_single'])) {

				$this->showSingleCollection(intval(trim($this->conf['collections'], ' ,')));

			}

			$additionalWhere .= ' AND tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).')';

			$orderBy = 'FIELD(tx_dlf_collections.uid, '.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).')';

		}

		// Should user-defined collections be shown?
		if (empty($this->conf['show_userdefined'])) {

			$additionalWhere .= ' AND tx_dlf_collections.fe_cruser_id=0';

		} elseif ($this->conf['show_userdefined'] > 0) {

			if (!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {

				$additionalWhere .= ' AND tx_dlf_collections.fe_cruser_id='.intval($GLOBALS['TSFE']->fe_user->user['uid']);

			} else {

				$additionalWhere .= ' AND NOT tx_dlf_collections.fe_cruser_id=0';

			}

		}

		// Get collections.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_collections.uid AS uid,tx_dlf_collections.label AS label,tx_dlf_collections.thumbnail AS thumbnail,tx_dlf_collections.description AS description,tx_dlf_collections.priority AS priority,COUNT(tx_dlf_documents.uid) AS titles',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_documents.partof=0 AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').$additionalWhere.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
			'tx_dlf_collections.uid',
			$orderBy,
			''
		);

		$count = $GLOBALS['TYPO3_DB']->sql_num_rows($result);

		$content = '';

		if ($count == 1 && empty($this->conf['dont_show_single'])) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			$this->showSingleCollection(intval($resArray['uid']));

		} elseif ($count > 0) {

			// Get number of volumes per collection.
			$resultVolumes = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dlf_collections.uid AS uid,COUNT(tx_dlf_documents.uid) AS volumes',
				'tx_dlf_documents',
				'tx_dlf_relations',
				'tx_dlf_collections',
				'AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND NOT tx_dlf_documents.uid IN (SELECT DISTINCT tx_dlf_documents.partof FROM tx_dlf_documents WHERE NOT tx_dlf_documents.partof=0'.tx_dlf_helper::whereClause('tx_dlf_documents').') AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').$additionalWhere.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
				'tx_dlf_collections.uid',
				'',
				''
			);

			$volumes = array ();

			while ($resArrayVolumes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultVolumes)) {

				$volumes[$resArrayVolumes['uid']] = $resArrayVolumes['volumes'];

			}

			// Process results.
			while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

				// Generate random but unique array key taking priority into account.
				do {

					$_key = ($resArray['priority'] * 1000) + mt_rand(0, 1000);

				} while (!empty($markerArray[$_key]));

				// Merge plugin variables with new set of values.
				$additionalParams = array ('collection' => $resArray['uid']);

				if (is_array($this->piVars)) {

					$piVars = $this->piVars;

					unset($piVars['DATA']);

					$additionalParams = tx_dlf_helper::array_merge_recursive_overrule($piVars, $additionalParams);

				}

				// Build typolink configuration array.
				$conf = array (
					'useCacheHash' => 1,
					'parameter' => $GLOBALS['TSFE']->id,
					'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE)
				);

				// Link collection's title to list view.
				$markerArray[$_key]['###TITLE###'] = $this->cObj->typoLink(htmlspecialchars($resArray['label']), $conf);

				// Add feed link if applicable.
				if (!empty($this->conf['targetFeed'])) {

					$img = '<img src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'res/icons/txdlffeeds.png" alt="'.$this->pi_getLL('feedAlt', '', TRUE).'" title="'.$this->pi_getLL('feedTitle', '', TRUE).'" />';

					$markerArray[$_key]['###FEED###'] = $this->pi_linkTP($img, array ($this->prefixId => array ('collection' => $resArray['uid'])), FALSE, $this->conf['targetFeed']);

				} else {

					$markerArray[$_key]['###FEED###'] = '';

				}

				// Add thumbnail.
				if (!empty($resArray['thumbnail'])) {

					$markerArray[$_key]['###THUMBNAIL###'] = '<img alt="" title="'.htmlspecialchars($resArray['label']).'" src="'.$resArray['thumbnail'].'" />';

				} else {

					$markerArray[$_key]['###THUMBNAIL###'] = '';

				}

				// Add description.
				$markerArray[$_key]['###DESCRIPTION###'] = $this->pi_RTEcssText($resArray['description']);

				// Build statistic's output.
				$labelTitles = $this->pi_getLL(($resArray['titles'] > 1 ? 'titles' : 'title'), '', FALSE);

				$markerArray[$_key]['###COUNT_TITLES###'] = htmlspecialchars($resArray['titles'].$labelTitles);

				$labelVolumes = $this->pi_getLL(($volumes[$resArray['uid']] > 1 ? 'volumes' : 'volume'), '', FALSE);

				$markerArray[$_key]['###COUNT_VOLUMES###'] = htmlspecialchars($volumes[$resArray['uid']].$labelVolumes);

			}

			// Randomize sorting?
			if (!empty($this->conf['randomize'])) {

				ksort($markerArray, SORT_NUMERIC);

				// Don't cache the output.
				$this->setCache(FALSE);

			}

			$entry = $this->cObj->getSubpart($this->template, '###ENTRY###');

			foreach ($markerArray as $marker) {

				$content .= $this->cObj->substituteMarkerArray($entry, $marker);

			}

			// Hook for getting custom collection hierarchies/subentries (requested by SBB).
			foreach ($this->hookObjects as $hookObj) {

				if (method_exists($hookObj, 'showCollectionList_getCustomCollectionList')) {

					$hookObj->showCollectionList_getCustomCollectionList($this, $this->conf['templateFile'], $content, $markerArray);

				}

			}

			return $this->cObj->substituteSubpart($this->template, '###ENTRY###', $content, TRUE);

		}

		return $content;

	}

	/**
	 * Builds a collection's list
	 *
	 * @access	protected
	 *
	 * @param	integer		$id: The collection's UID
	 *
	 * @return	void
	 */
	protected function showSingleCollection($id) {

		// Should user-defined collections be shown?
		if (empty($this->conf['show_userdefined'])) {

			$additionalWhere = ' AND tx_dlf_collections.fe_cruser_id=0';

		} elseif ($this->conf['show_userdefined'] > 0) {

			$additionalWhere = ' AND NOT tx_dlf_collections.fe_cruser_id=0';

		}

		// Get all documents in collection.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_collections.index_name AS index_name,tx_dlf_collections.label AS collLabel,tx_dlf_collections.description AS collDesc,tx_dlf_collections.thumbnail AS collThumb,tx_dlf_collections.fe_cruser_id AS userid,tx_dlf_documents.uid AS uid,tx_dlf_documents.metadata_sorting AS metadata_sorting,tx_dlf_documents.volume_sorting AS volume_sorting,tx_dlf_documents.partof AS partof',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_collections.uid='.intval($id).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').$additionalWhere.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
			'',
			'tx_dlf_documents.title_sorting ASC',
			''
		);

		$toplevel = array ();

		$subparts = array ();

		$listMetadata = array ();

		// Process results.
		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			if (empty($listMetadata)) {

				$listMetadata = array (
					'label' => htmlspecialchars($resArray['collLabel']),
					'description' => $this->pi_RTEcssText($resArray['collDesc']),
					'thumbnail' => htmlspecialchars($resArray['collThumb']),
					'options' => array (
						'source' => 'collection',
						'select' => $id,
						'userid' => $resArray['userid'],
						'params' => array ('fq' => array ('collection_faceting:"'.$resArray['index_name'].'"')),
						'core' => '',
						'pid' => $this->conf['pages'],
						'order' => 'title',
						'order.asc' => TRUE
					)
				);

			}

			// Split toplevel documents from volumes.
			if ($resArray['partof'] == 0) {

				// Prepare document's metadata for sorting.
				$sorting = unserialize($resArray['metadata_sorting']);

				if (!empty($sorting['type']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['type'])) {

					$sorting['type'] = tx_dlf_helper::getIndexName($sorting['type'], 'tx_dlf_structures', $this->conf['pages']);

				}

				if (!empty($sorting['owner']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['owner'])) {

					$sorting['owner'] = tx_dlf_helper::getIndexName($sorting['owner'], 'tx_dlf_libraries', $this->conf['pages']);

				}

				if (!empty($sorting['collection']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['collection'])) {

					$sorting['collection'] = tx_dlf_helper::getIndexName($sorting['collection'], 'tx_dlf_collections', $this->conf['pages']);

				}

				$toplevel[$resArray['uid']] = array (
					'u' => $resArray['uid'],
					's' => $sorting,
					'p' => array ()
				);

			} else {

				$subparts[$resArray['partof']][$resArray['volume_sorting']] = $resArray['uid'];

			}

		}

		// Add volumes to the corresponding toplevel documents.
		foreach ($subparts as $partof => $parts) {

			if (!empty($toplevel[$partof])) {

				ksort($parts);

				$toplevel[$partof]['p'] = array_values($parts);

			}

		}

		// Save list of documents.
		$list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

		$list->reset();

		$list->add(array_values($toplevel));

		$list->metadata = $listMetadata;

		$list->save();

		// Clean output buffer.
		\TYPO3\CMS\Core\Utility\GeneralUtility::cleanOutputBuffers();

		// Send headers.
		header('Location: '.\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL(array ('parameter' => $this->conf['targetPid']))));

		// Flush output buffer and end script processing.
		ob_end_flush();

		exit;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/collection/class.tx_dlf_collection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/collection/class.tx_dlf_collection.php']);
}
