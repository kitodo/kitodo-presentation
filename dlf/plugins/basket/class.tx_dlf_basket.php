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
 * Plugin 'DLF: List View' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_basket extends tx_dlf_plugin {

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
		$this->list = t3lib_div::makeInstance('tx_dlf_basket');

		

		// set marker

		$markerArray['###LISTTITLE###'] = $this->list->metadata['label'];

		$markerArray['###LISTDESCRIPTION###'] = $this->list->metadata['description'];

		if (!empty($this->list->metadata['thumbnail'])) {

			$markerArray['###LISTTHUMBNAIL###'] = '<img alt="" src="'.$this->list->metadata['thumbnail'].'" />';

		} else {

			$markerArray['###LISTTHUMBNAIL###'] = '';

		}

		if ($i) {

			$markerArray['###COUNT###'] = htmlspecialchars(sprintf($this->pi_getLL('count'), ($this->piVars['pointer'] * $this->conf['limit']) + 1, $i, count($this->list)));

		} else {

			$markerArray['###COUNT###'] = $this->pi_getLL('nohits', '', TRUE);

		}

		$markerArray['###PAGEBROWSER###'] = $this->getPageBrowser();

		$markerArray['###SORTING###'] = $this->getSortingForm();

		$content = $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($this->template, '###ENTRY###', $content, TRUE), $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/basket/class.tx_dlf_basket.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/basket/class.tx_dlf_basket.php']);
}

?>
