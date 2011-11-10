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
 * Plugin 'DLF: List View' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_listview extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/listview/class.tx_dlf_listview.php';

	/**
	 * This holds the list
	 *
	 * @var	tx_dlf_list
	 * @access	protected
	 */
	protected $list;

	/**
	 * Array of labels for the metadata
	 *
	 * @var	array
	 * @access	protected
	 */
	protected $labels = array ();

	/**
	 * Array of sorted metadata
	 *
	 * @var	array
	 * @access	protected
	 */
	protected $metadata = array ();

	/**
	 * Renders the page browser
	 *
	 * @access	protected
	 *
	 * @return	string		The rendered page browser ready for output
	 */
	protected function getPagebrowser() {

		// Get overall number of pages.
		$maxPages = intval(ceil($this->list->count / $this->conf['limit']));

		// Return empty pagebrowser if there is just one page.
		if ($maxPages < 2) {

			return '';

		}

		// Get separator.
		$separator = $this->pi_getLL('separator', ' - ');

		// Add link to previous page.
		if ($this->piVars['pointer'] > 0) {

			$output = $this->pi_linkTP_keepPIvars($this->pi_getLL('prevPage', '&lt;'), array ('pointer' => $this->piVars['pointer'] - 1), TRUE).$separator;

		} else {

			$output = $this->pi_getLL('prevPage', '&lt;').$separator;

		}

		$i = 0;

		while ($i < $maxPages) {

			if ($i < 3 || ($i > $this->piVars['pointer'] - 3 && $i < $this->piVars['pointer'] + 3) || $i > $maxPages - 4) {

				if ($this->piVars['pointer'] != $i) {

					$output .= $this->pi_linkTP_keepPIvars(sprintf($this->pi_getLL('page', '%d'), $i + 1), array ('pointer' => $i), TRUE).$separator;

				} else {

					$output .= sprintf($this->pi_getLL('page', '%d'), $i + 1).$separator;

				}

				$skip = TRUE;

			} elseif ($skip == TRUE) {

				$output .= $this->pi_getLL('skip', '...').$separator;

				$skip = FALSE;

			}

			$i++;

		}

		if ($this->piVars['pointer'] < $maxPages - 1) {

			$output .= $this->pi_linkTP_keepPIvars($this->pi_getLL('nextPage', '&gt;'), array ('pointer' => $this->piVars['pointer'] + 1), TRUE);

		} else {

			$output .= $this->pi_getLL('nextPage', '&gt;');

		}

		return $output;

	}

	/**
	 * Renders one entry of the list
	 *
	 * @access	protected
	 *
	 * @param	integer		$number: The number of the entry
	 * @param	string		$template: Parsed template subpart
	 *
	 * @return	string		The rendered entry ready for output
	 */
	protected function getEntry($number, $template) {

		$markerArray['###NUMBER###'] = $number + 1;

		$markerArray['###METADATA###'] = '';

		$subpart = '';

		foreach ($this->metadata as $_index_name => $_wrap) {

			$hasValue = FALSE;

			if (is_array($this->list->elements[$number][$_index_name]) && !empty($this->labels[$_index_name])) {

				$fieldwrap = $this->parseTS($_wrap);

				$field = $this->cObj->stdWrap(htmlspecialchars($this->labels[$_index_name]), $fieldwrap['key.']);

				foreach ($this->list->elements[$number][$_index_name] as $_value) {

					// Link title to pageview.
					if ($_index_name == 'title') {

						// Get title of parent document if needed.
						if (empty($_value) && $this->conf['getTitle']) {

							$_value = '['.tx_dlf_document::getTitle($this->list->elements[$number]['uid'], TRUE).']';

						}

						// Set fake title if still not present.
						if (empty($_value)) {

							$_value = $this->pi_getLL('noTitle');

						}

						$_value = $this->pi_linkTP(htmlspecialchars($_value), array ($this->prefixId => array ('id' => $this->list->elements[$number]['uid'], 'page' => $this->list->elements[$number]['page'], 'pointer' => $this->piVars['pointer'])), TRUE, $this->conf['targetPid']);

					} elseif ($_index_name == 'type' && !empty($_value)) {

						$_value = $this->pi_getLL($_value, tx_dlf_helper::translate($_value, 'tx_dlf_structures', $this->conf['pages']), FALSE);

					} elseif (!empty($_value)) {

						$_value = htmlspecialchars($_value);

					}

					if (!empty($_value)) {

						$field .= $this->cObj->stdWrap($_value, $fieldwrap['value.']);

						$hasValue = TRUE;

					}

				}

				if ($hasValue) {

					$markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);

				}

			}

		}

//		if (!empty($this->list->elements[$number]['subparts'])) {
//
//			foreach ($this->list->elements[$number]['subparts'] as $_subpart) {
//
//				$subpart = $this->getSubEntry($_subpart, $template);

		return $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($template['entry'], '###SUBTEMPLATE###', $subpart, TRUE), $markerArray);

	}

	/**
	 * Renders one sub-entry of the list
	 *
	 * @access	protected
	 *
	 * @param	integer		$number: The number of the entry
	 * @param	string		$template: Parsed template subpart
	 *
	 * @return	string		The rendered entry ready for output
	 */
	protected function getSubEntry($number, $template) {

	}

	/**
	 * Get metadata configuration from database
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function loadConfig() {

		$this->labels = array (
			'title' => $this->pi_getLL('title', tx_dlf_helper::translate('title', 'tx_dlf_metadata', $this->conf['pages']), TRUE),
			'author' => $this->pi_getLL('author', tx_dlf_helper::translate('author', 'tx_dlf_metadata', $this->conf['pages']), TRUE),
			'year' => $this->pi_getLL('year', tx_dlf_helper::translate('year', 'tx_dlf_metadata', $this->conf['pages']), TRUE),
			'place' => $this->pi_getLL('place', tx_dlf_helper::translate('place', 'tx_dlf_metadata', $this->conf['pages']), TRUE),
			'type' => $this->pi_getLL('type', tx_dlf_helper::translate('type', 'tx_dlf_metadata', $this->conf['pages']), TRUE)
		);

		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.wrap AS wrap',
			'tx_dlf_metadata',
			'tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'tx_dlf_metadata.sorting ASC',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			if (in_array($resArray['index_name'], array_keys($this->labels))) {

				$this->metadata[$resArray['index_name']] = $resArray['wrap'];

			}

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

		// Don't cache the output.
		$this->setCache(FALSE);

		// Load the list.
		$this->list = t3lib_div::makeInstance('tx_dlf_list');

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/listview/template.tmpl'), '###TEMPLATE###');

		}

		$subpartArray['entry'] = $this->cObj->getSubpart($this->template, '###ENTRY###');

		// Set some variable defaults.
		if (!empty($this->piVars['pointer']) && (($this->piVars['pointer'] * $this->conf['limit']) + 1) <= $this->list->count) {

			$this->piVars['pointer'] = max(intval($this->piVars['pointer']), 0);

		} else {

			$this->piVars['pointer'] = 0;

		}

		$this->loadConfig();

		for ($i = $this->piVars['pointer'] * $this->conf['limit'], $j = ($this->piVars['pointer'] + 1) * $this->conf['limit']; $i < $j; $i++) {

			if (empty($this->list->elements[$i])) {

				break;

			} else {

				$content .= $this->getEntry($i, $subpartArray);

			}

		}

		$markerArray['###LISTTITLE###'] = htmlspecialchars($this->list->metadata['label']);

		$markerArray['###LISTDESCRIPTION###'] = $this->list->metadata['description'];

		if ($i) {

			$markerArray['###COUNT###'] = htmlspecialchars(sprintf($this->pi_getLL('count'), ($this->piVars['pointer'] * $this->conf['limit']) + 1, $i, $this->list->count));

		} else {

			$markerArray['###COUNT###'] = $this->pi_getLL('nohits', '', TRUE);

		}

		$markerArray['###PAGEBROWSER###'] = $this->getPageBrowser();

		$content = $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($this->template, '###ENTRY###', $content, TRUE), $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/listview/class.tx_dlf_listview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/listview/class.tx_dlf_listview.php']);
}

?>