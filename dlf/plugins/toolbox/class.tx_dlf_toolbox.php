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
 * Plugin 'DLF: Toolbox' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_toolbox extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toolbox/class.tx_dlf_toolbox.php';

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

		// Turn cache off.
		$this->setCache(FALSE);

		// Quit without doing anything if required variable is not set.
		if (empty($this->piVars['id'])) {

			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toolbox/template.tmpl'), '###TEMPLATE###');

		}

		// Set toolbox label.
		$markerArray['###LABEL###'] = $this->pi_getLL('label', '', TRUE);

		// Build data array.
		$data = array ();

		if (t3lib_div::testInt($this->piVars['id'])) {

			$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_dlf_documents',
				'tx_dlf_documents.uid='.intval($this->piVars['id']).tx_dlf_helper::whereClause('tx_dlf_documents'),
				'',
				'',
				'1'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result) > 0) {

				$data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

			}

		} else {

			// TODO: "data"-Array bei externen METS-Dateien selbst bauen.

		}

		// Get template subpart for tools.
		$subpart = $this->cObj->getSubpart($this->template, '###TOOLS###');

		if (!empty($data)) {

			$_tools = explode(',', $this->conf['tools']);

			// Add the tools to the toolbox.
			foreach ($_tools as $_tool) {

				$_tool = trim($_tool);

				$cObj = t3lib_div::makeInstance('tslib_cObj');

				$cObj->data = $data;

				$content .= $this->cObj->substituteMarkerArray($subpart, array ('###TOOL###' => $cObj->cObjGetSingle($GLOBALS['TSFE']->tmpl->setup['plugin.'][$_tool], $GLOBALS['TSFE']->tmpl->setup['plugin.'][$_tool.'.'])));

			}

		}

		return $this->pi_wrapInBaseClass($this->cObj->substituteSubpart($this->cObj->substituteMarkerArray($this->template, $markerArray), '###TOOLS###', $content, TRUE));

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/class.tx_dlf_toolbox.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/class.tx_dlf_toolbox.php']);
}

?>