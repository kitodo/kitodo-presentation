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
 * Plugin 'DLF: Collection' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_collection extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/collection/class.tx_dlf_collection.php';

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

		// Quit without doing anything if required configuration variables are not set.
		if (!$this->conf['pages']) {

			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/collection/template.tmpl'), '###TEMPLATE###');

		}

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

			if (count(explode(',', $this->conf['collections'])) == 1) {

				$this->showSingleCollection(intval(trim($this->conf['collections'], ' ,')));

			}

			$additionalWhere .= ' AND tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).')';

			$orderBy = 'FIELD(tx_dlf_collections.uid, '.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).')';

		}

		// Should user-defined collections be shown, too?
		if ($this->conf['show_userdefined']) {

			$additionalWhere .= ' AND NOT tx_dlf_collections.fe_cruser_id=0';

		} else {

			$additionalWhere .= ' AND tx_dlf_collections.fe_cruser_id=0';

		}

		// Get collections.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_collections.uid AS uid,tx_dlf_collections.label AS label,tx_dlf_collections.description AS description,COUNT(tx_dlf_documents.uid) AS titles',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_documents.partof=0'.$additionalWhere.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
			'tx_dlf_collections.uid',
			$orderBy,
			''
		);

		$count = $GLOBALS['TYPO3_DB']->sql_num_rows($result);

		$content = '';

		if ($count == 1) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			$this->showSingleCollection(intval($resArray['uid']));

		} elseif ($count > 1) {

			// Get number of volumes per collection.
			$resultVolumes = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dlf_collections.uid AS uid,COUNT(tx_dlf_documents.uid) AS volumes',
				'tx_dlf_documents',
				'tx_dlf_relations',
				'tx_dlf_collections',
				'AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND NOT tx_dlf_documents.uid IN (SELECT DISTINCT tx_dlf_documents.partof FROM tx_dlf_documents WHERE NOT tx_dlf_documents.partof=0'.tx_dlf_helper::whereClause('tx_dlf_documents').')'.$additionalWhere.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
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

				// Merge plugin variables with new set of values.
				$additionalParams = array ('collection' => $resArray['uid']);

				if (is_array($this->piVars)) {

					$piVars = $this->piVars;

					unset($piVars['DATA']);

					$additionalParams = t3lib_div::array_merge_recursive_overrule($piVars, $additionalParams);

				}

				// Build typolink configuration array.
				$conf = array ();

				$conf['useCacheHash'] = 1;

				$conf['parameter'] = $GLOBALS['TSFE']->id;

				$conf['additionalParams'] = t3lib_div::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE);

				// Link collection's title to list view.
				$markerArray[$resArray['uid']]['###TITLE###'] = $this->cObj->typoLink(htmlspecialchars($resArray['label']), $conf);

				// Add description.
				$markerArray[$resArray['uid']]['###DESCRIPTION###'] = $this->pi_RTEcssText($resArray['description']);

				// Build statistic's output.
				$_labelTitles = $this->pi_getLL(($resArray['titles'] > 1 ? 'titles' : 'title'), '', FALSE);

				$markerArray[$resArray['uid']]['###COUNT_TITLES###'] = htmlspecialchars($resArray['titles'].$_labelTitles);

				$_labelVolumes = $this->pi_getLL(($volumes[$resArray['uid']] > 1 ? 'volumes' : 'volume'), '', FALSE);

				$markerArray[$resArray['uid']]['###COUNT_VOLUMES###'] = htmlspecialchars($volumes[$resArray['uid']].$_labelVolumes);

			}

			$entry = $this->cObj->getSubpart($this->template, '###ENTRY###');

			foreach ($markerArray as $_markerArray) {

				$content .= $this->cObj->substituteMarkerArray($entry, $_markerArray);

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

		$orderBy = 'tx_dlf_documents.title_sorting';

		if (!empty($this->piVars['order'])) {

			switch ($this->piVars['order']) {

				case 'title':
				case 'author':
				case 'place':
				case 'year':

					$orderBy = 'tx_dlf_documents.'.$this->piVars['order'].'_sorting ASC';

					break;

				case '-title':
				case '-author':
				case '-place':
				case '-year':

					$orderBy = 'tx_dlf_documents.'.substr($this->piVars['order'], 1).'_sorting DESC';

					break;

			}

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_collections.label AS collLabel,tx_dlf_collections.description AS collDesc,tx_dlf_documents.uid AS uid,tx_dlf_documents.title AS title,tx_dlf_documents.volume AS volume,tx_dlf_documents.author AS author,tx_dlf_documents.place AS place,tx_dlf_documents.year AS year,tx_dlf_documents.structure AS type',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_documents.partof=0 AND tx_dlf_collections.uid='.$GLOBALS['TYPO3_DB']->fullQuoteStr($id, 'tx_dlf_collections').' AND tx_dlf_collections.pid='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->conf['pages'], 'tx_dlf_collections').tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
			'',
			$orderBy,
			''
		);

		$_list = array ();

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			if (empty($_metadata)) {

				$_metadata = array (
					'uid' => $id,
					'label' => $resArray['collLabel'],
					'description' => $this->pi_RTEcssText($resArray['collDesc']),
					'options' => array (
						'orderBy' => $this->piVars['order']
					)
				);

			}

			$_list[] = array (
				'uid' => $resArray['uid'],
				'page' => 1,
				'title' => array ($resArray['title']),
				'volume' => array ($resArray['volume']),
				'author' => array ($resArray['author']),
				'year' => array ($resArray['year']),
				'place' => array ($resArray['place']),
				'type' => array (tx_dlf_helper::getIndexName($resArray['type'], 'tx_dlf_structures', $this->conf['pages'])),
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/collection/class.tx_dlf_collection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/collection/class.tx_dlf_collection.php']);
}

?>