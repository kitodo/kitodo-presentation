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
 * Plugin 'DLF: Viewer' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_toc extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toc/class.tx_dlf_toc.php';

	/**
	 * This builds an array for one menu entry
	 *
	 * @access	protected
	 *
	 * @param	array		$entry: The entry's array from tx_dlf_document->getLogicalStructure
	 * @param	boolean		$recursive: Whether to include the child entries
	 *
	 * @return	array		HMENU array for menu entry
	 */
	protected function getMenuEntry($entry, $recursive = FALSE) {

		$entryArray = array ();

		// Set "title", "type" and "pagination" from $entry array.
		$entryArray['title'] = $entry['label'];

		$entryArray['type'] = $this->pi_getLL($entry['type'], tx_dlf_helper::translate($entry['type'], 'tx_dlf_structures', $this->conf['pages']), FALSE);

		$entryArray['pagination'] = $entry['pagination'];

		$entryArray['doNotLinkIt'] = 0;

		$entryArray['_OVERRIDE_HREF'] = '';

		$entryArray['ITEM_STATE'] = 'NO';

		// Build menu links based on the $entry['points'] array.
		if (!empty($entry['points'][0]) && t3lib_div::testInt($entry['points'][0])) {

			$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('page' => $entry['points'][0]), TRUE, FALSE, $this->conf['targetPid']);

		} elseif (!empty($entry['points'][0]) && is_string($entry['points'][0])) {

			$_doc = tx_dlf_document::getInstance($entry['points'][0], ($this->conf['excludeOther'] ? $this->conf['pages'] : 0));

			$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('id' => ($_doc->pid ? $_doc->uid : $entry['points'][0]), 'page' => 1), TRUE, FALSE, $this->conf['targetPid']);

		} elseif (!empty($entry['points']['doc'])) {

			$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('id' => $entry['points']['doc'], 'page' => 1), TRUE, FALSE, $this->conf['targetPid']);

		} else {

			$entryArray['doNotLinkIt'] = 1;

		}

		// Set "ITEM_STATE" to "CUR" if this entry points to current page.
		if (!empty($this->piVars['page']) && in_array($this->piVars['page'], $entry['points'])) {

			$entryArray['ITEM_STATE'] = 'CUR';

		}

		// Build sub-menu if available and called recursively.
		if ($recursive == TRUE && !empty($entry['children'])) {

			// Build sub-menu only if one of this conditions apply:
			// 1. "expAll" is set for menu
			// 2. Current menu node is in rootline
			// 3. Current menu node points to another file
			// 4. There are no physical pages in the current METS file
			if (!empty($this->conf['menuConf.']['expAll']) || $entryArray['ITEM_STATE'] == 'CUR' || is_string($entry['points'][0]) || !$this->doc->physicalPages) {

				$entryArray['_SUB_MENU'] = array ();

				foreach ($entry['children'] as $_child) {

					// Set "ITEM_STATE" to "ACT" if this entry points to current page and has sub-entries pointing to the same page.
					if (!empty($this->piVars['page']) && in_array($this->piVars['page'], $_child['points'])) {

						$entryArray['ITEM_STATE'] = 'ACT';

					}

					$entryArray['_SUB_MENU'][] = $this->getMenuEntry($_child, TRUE);

				}

			}

			// Append "IFSUB" to "ITEM_STATE" if this entry has sub-entries.
			$entryArray['ITEM_STATE'] = ($entryArray['ITEM_STATE'] == 'NO' ? 'IFSUB' : $entryArray['ITEM_STATE'].'IFSUB');

		}

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

		// Check for typoscript configuration to prevent fatal error.
		if (empty($this->conf['menuConf.'])) {

			trigger_error('No typoscript configuration for table of contents available', E_USER_ERROR);

			return $content;

		}

		// Quit without doing anything if required piVars are not set.
		if (!$this->checkPIvars()) {

			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toc/template.tmpl'), '###TEMPLATE###');

		}

		$_TSconfig = array ();

		$_TSconfig['special'] = 'userfunction';

		$_TSconfig['special.']['userFunc'] = 'tx_dlf_toc->makeMenuArray';

		$_TSconfig = t3lib_div::array_merge_recursive_overrule($this->conf['menuConf.'], $_TSconfig);

		$markerArray['###TOCMENU###'] = $this->cObj->HMENU($_TSconfig);

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

		// Quit without doing anything if required piVars are not set.
		if (!$this->checkPIvars()) {

			return array ();

		}

		// Load current document.
		$this->loadDocument();

		$menuArray = array ();

		// Does the document have physical pages or is it an external file?
		if ($this->doc->physicalPages || !t3lib_div::testInt($this->doc->uid)) {

			// Go through table of contents and create all menu entries.
			foreach ($this->doc->tableOfContents as $_entry) {

				$menuArray[] = $this->getMenuEntry($_entry, TRUE);

			}

		} else {

			// Go through table of contents and create top-level menu entries.
			foreach ($this->doc->tableOfContents as $_entry) {

				$menuArray[] = $this->getMenuEntry($_entry, FALSE);

			}

			// Get all child documents from database.
			$whereClause = 'tx_dlf_documents.partof='.intval($this->doc->uid).' AND tx_dlf_documents.structure=tx_dlf_structures.uid AND tx_dlf_structures.pid='.$this->doc->pid.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_structures');

			if ($this->conf['excludeOther']) {

				$whereClause .= ' AND tx_dlf_documents.pid='.intval($this->conf['pages']);

			}

			// Build table of contents from database.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_documents.uid AS uid,tx_dlf_documents.title AS title,tx_dlf_structures.index_name AS type',
				'tx_dlf_documents,tx_dlf_structures',
				$whereClause,
				'',
				'tx_dlf_documents.volume_sorting',
				''
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				$menuArray[0]['ITEM_STATE'] .= 'IFSUB';

				$menuArray[0]['_SUB_MENU'] = array ();

				while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

					$_entry = array (
						'label' => $resArray['title'],
						'type' => $resArray['type'],
						'pagination' => '',
						'points' => array ('doc' => $resArray['uid'])
					);

					$menuArray[0]['_SUB_MENU'][] = $this->getMenuEntry($_entry, FALSE);

				}

			}

		}

		return $menuArray;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toc/class.tx_dlf_toc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toc/class.tx_dlf_toc.php']);
}

?>