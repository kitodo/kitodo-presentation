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
 * Search suggestions for the plugin 'DLF: Search' of the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_search_suggest extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	public $scriptRelPath = 'plugins/search/class.tx_dlf_search_suggest.php';

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	void
	 */
	public function main($content = '', $conf = array ()) {

		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted') != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed') != '') {

			$core = tx_dlf_helper::decrypt(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed'));

		}

		if (!empty($core)) {

			$url = trim(tx_dlf_solr::getSolrUrl($core), '/').'/suggest/?q='.tx_dlf_solr::escapeQuery(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('q'));

			if ($stream = fopen($url, 'r')) {

				$content .= stream_get_contents($stream);

				fclose($stream);

			}

		}

		echo $content;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search_suggest.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_search_suggest.php']);
}

$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_search_suggest');

$cObj->main();
