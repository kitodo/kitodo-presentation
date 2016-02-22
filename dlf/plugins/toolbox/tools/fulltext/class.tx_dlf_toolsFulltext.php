<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
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
 * Tool 'Fulltext selection' for the plugin 'DLF: Toolbox' of the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_toolsFulltext extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php';

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

		// Merge configuration with conf array of toolbox.
		$this->conf = tx_dlf_helper::array_merge_recursive_overrule($this->cObj->data['conf'], $this->conf);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL || $this->doc->numPages < 1 || empty($this->conf['fileGrpFulltext'])) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values if not set.
			// page may be integer or string (physical page attribute)
			if ( (int)$this->piVars['page'] > 0 || empty($this->piVars['page'])) {

				$this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);

			} else {

				$this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalPages);

			}

			$this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toolbox/tools/fulltext/template.tmpl'), '###TEMPLATE###');

		}


		$fullTextFile = $this->doc->physicalPagesInfo[$this->doc->physicalPages[$this->piVars['page']]]['files'][$this->conf['fileGrpFulltext']];

		if (!empty($fullTextFile)) {
			$markerArray['###FULLTEXT_SELECT###'] = '<a class="select switchoff" id="tx-dlf-tools-fulltext" title="" data-dic="fulltext-on:'
					.$this->pi_getLL('fulltext-on', '', TRUE).';fulltext-off:'
					.$this->pi_getLL('fulltext-off', '', TRUE).'"></a>';
		} else {
			$markerArray['###FULLTEXT_SELECT###'] = $this->pi_getLL('fulltext-not-available', '', TRUE);
		}

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php']);
}
