<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Alexander Bigga <alexander.bigga@slub-dresden.de>
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
 * Plugin 'DFG-Viewer: SRU Client eID script' for the 'dfgviewer' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @copyright	Copyright (c) 2015, Alexander Bigga, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_fulltext_eid extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	/**
	 *
	 */
	public $cObj;


	/**
	 * The main method of the eID-Script
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	void
	 */
	public function main($content = '', $conf = array ()) {

		$this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

		// Load translation files.
		$LANG = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('language');

		$this->extKey = 'dlf';

		$this->scriptRelPath = 'plugins/pageview/class.tx_dlf_fulltext_eid.php';

		$this->LLkey = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L') : 'default';

		$this->pi_loadLL();

		$url = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('url');

		$fulltextData = file_get_contents($url);

		header('Last-Modified: ' . gmdate( "D, d M Y H:i:s" ) . 'GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Length: '.strlen($fulltextData));
		header('Content-Type: text/xml');

		echo $fulltextData;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_fulltext_eid.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_fulltext_eid.php']);
}

$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_fulltext_eid');

$cObj->main();
