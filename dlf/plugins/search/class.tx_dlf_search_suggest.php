<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Henrik Lochmann <dev@mentalmotive.com>
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

require_once (PATH_tslib.'class.tslib_pibase.php');

/**
 * Autocompletion for the search plugin of the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_search_suggest extends tslib_pibase {

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

		if (t3lib_div::_GP('encrypted') != '' && t3lib_div::_GP('hashed') != '') {

			$core = tx_dlf_helper::decrypt(t3lib_div::_GP('encrypted'), t3lib_div::_GP('hashed'));

		}

		if (!empty($core)) {

			$url = trim(tx_dlf_solr::getSolrUrl($core), '/').'/suggest/?q='.tx_dlf_solr::escapeQuery(t3lib_div::_GP('q'));

			if ($stream = fopen($url, 'r')) {

				$content .= stream_get_contents($stream);

				fclose($stream);

			}

		}

		echo $content;

	}

}

$cObj = t3lib_div::makeInstance('tx_dlf_search_suggest');

$cObj->main();

?>