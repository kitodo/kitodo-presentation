<?php
/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Plugin 'DLF: Viewer' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_toc extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toc/class.tx_dlf_toc.php';

	/**
	 * This holds the active entries according to the currently selected page
	 *
	 * @var	array
	 * @access protected
	 */
	protected $activeEntries = array ();

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
	protected function getMenuEntry(array $entry, $recursive = FALSE) {

		$entryArray = array ();

		// Set "title", "volume", "type" and "pagination" from $entry array.
		$entryArray['title'] = $entry['label'];

		$entryArray['volume'] = $entry['volume'];

		$entryArray['orderlabel'] = $entry['orderlabel'];

		$entryArray['type'] = tx_dlf_helper::translate($entry['type'], 'tx_dlf_structures', $this->conf['pages']);

		$entryArray['pagination'] = $entry['pagination'];

		$entryArray['_OVERRIDE_HREF'] = '';

		$entryArray['doNotLinkIt'] = 1;

		$entryArray['ITEM_STATE'] = 'NO';

		// Build menu links based on the $entry['points'] array.
		if (!empty($entry['points']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($entry['points'])) {

			$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('page' => $entry['points']), TRUE, FALSE, $this->conf['targetPid']);

			$entryArray['doNotLinkIt'] = 0;

		} elseif (!empty($entry['points']) && is_string($entry['points'])) {

			$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('id' => $entry['points'], 'page' => 1), TRUE, FALSE, $this->conf['targetPid']);

			$entryArray['doNotLinkIt'] = 0;

		} elseif (!empty($entry['targetUid'])) {

			$entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(array ('id' => $entry['targetUid'], 'page' => 1), TRUE, FALSE, $this->conf['targetPid']);

			$entryArray['doNotLinkIt'] = 0;

		}

		// Set "ITEM_STATE" to "CUR" if this entry points to current page.
		if (in_array($entry['id'], $this->activeEntries)) {

			$entryArray['ITEM_STATE'] = 'CUR';

		}

		// Build sub-menu if available and called recursively.
		if ($recursive == TRUE && !empty($entry['children'])) {

			// Build sub-menu only if one of the following conditions apply:
			// 1. "expAll" is set for menu
			// 2. Current menu node is in rootline
			// 3. Current menu node points to another file
			// 4. Current menu node has no corresponding images
			if (!empty($this->conf['menuConf.']['expAll']) || $entryArray['ITEM_STATE'] == 'CUR' || is_string($entry['points']) || empty($this->doc->smLinks['l2p'][$entry['id']])) {

				$entryArray['_SUB_MENU'] = array ();

				foreach ($entry['children'] as $child) {

					// Set "ITEM_STATE" to "ACT" if this entry points to current page and has sub-entries pointing to the same page.
					if (in_array($child['id'], $this->activeEntries)) {

						$entryArray['ITEM_STATE'] = 'ACT';

					}

					$entryArray['_SUB_MENU'][] = $this->getMenuEntry($child, TRUE);

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

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_toc->main('.$content.', [data])] Incomplete plugin configuration', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);

			}

			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toc/template.tmpl'), '###TEMPLATE###');

		}

		$TSconfig = array ();

		$TSconfig['special'] = 'userfunction';

		$TSconfig['special.']['userFunc'] = 'tx_dlf_toc->makeMenuArray';

		$TSconfig = tx_dlf_helper::array_merge_recursive_overrule($this->conf['menuConf.'], $TSconfig);

		$markerArray['###TOCMENU###'] = $this->cObj->HMENU($TSconfig);

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

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if required variables are not set.
			return array ();

		} else {

			if (!empty($this->piVars['logicalPage'])) {

				$this->piVars['page'] = $this->doc->getPhysicalPage($this->piVars['logicalPage']);
				unset($this->piVars['logicalPage']);

				}

			// Set default values for page if not set.
			// $this->piVars['page'] may be integer or string (physical structure @ID)
			if ( (int)$this->piVars['page'] > 0 || empty($this->piVars['page'])) {

				$this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int)$this->piVars['page'], 1, $this->doc->numPages, 1);

			} else {

				$this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);

			}

			$this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);

		}

		$menuArray = array ();

		// Does the document have physical elements or is it an external file?
		if ($this->doc->physicalStructure || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->doc->uid)) {

			// Get all logical units the current page or track is a part of.
			if (!empty($this->piVars['page']) && $this->doc->physicalStructure) {

				$this->activeEntries = array_merge((array) $this->doc->smLinks['p2l'][$this->doc->physicalStructure[0]], (array) $this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page']]]);

				if (!empty($this->piVars['double']) && $this->piVars['page'] < $this->doc->numPages) {

					$this->activeEntries = array_merge($this->activeEntries, (array) $this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page'] + 1]]);

				}

			}

			// Go through table of contents and create all menu entries.
			foreach ($this->doc->tableOfContents as $entry) {

				$menuArray[] = $this->getMenuEntry($entry, TRUE);

			}

		} else {

			// Go through table of contents and create top-level menu entries.
			foreach ($this->doc->tableOfContents as $entry) {

				$menuArray[] = $this->getMenuEntry($entry, FALSE);

			}

			// Get all child documents from database.
			$whereClause = 'tx_dlf_documents.partof='.intval($this->doc->uid).' AND tx_dlf_documents.structure=tx_dlf_structures.uid AND tx_dlf_structures.pid='.$this->doc->pid.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_structures');

			if ($this->conf['excludeOther']) {

				$whereClause .= ' AND tx_dlf_documents.pid='.intval($this->conf['pages']);

			}

			// Build table of contents from database.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_documents.uid AS uid,tx_dlf_documents.title AS title,tx_dlf_documents.volume AS volume,tx_dlf_structures.index_name AS type',
				'tx_dlf_documents,tx_dlf_structures',
				$whereClause,
				'',
				'tx_dlf_documents.volume_sorting',
				''
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				$menuArray[0]['ITEM_STATE'] = 'CURIFSUB';

				$menuArray[0]['_SUB_MENU'] = array ();

				while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

					$entry = array (
						'label' => $resArray['title'],
						'type' => $resArray['type'],
						'volume' => $resArray['volume'],
						'pagination' => '',
						'targetUid' => $resArray['uid']
					);

					$menuArray[0]['_SUB_MENU'][] = $this->getMenuEntry($entry, FALSE);

				}

			}

		}

		return $menuArray;

	}

}
