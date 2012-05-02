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

/**
 * Search suggestion Ajax backend for the Plugin 'DLF: Search' of the 
 * 'dlf' extension. This class is invoked using the eID bypass.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
require_once(PATH_tslib.'class.tslib_pibase.php');
class tx_dlf_search_suggest extends tslib_pibase {

	private $content;

	public $scriptRelPath = 'plugins/search/class.tx_dlf_search_suggest.php';
	
	private static function getSolrSuggestUrl($query) {
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][tx_dlf_solr::$extKey]);
		$host = ($conf['solrHost'] ? $conf['solrHost'] : 'localhost');
		$port = $conf['solrPort'];
		$path = trim($conf['solrPath'], '/').'/'.$core;
		return "http://".$host.":".$port."/".$path."suggest/?q=".$query;
	}
	
	public function main() {
		$url = tx_dlf_search_suggest::getSolrSuggestUrl(t3lib_div::_POST('q'));
		
		if ($stream = fopen($url, 'r')) {
		    $this->content .= stream_get_contents($stream);
    		fclose($stream);
		} else {
			$this->content .= "Could not connect to index server.";
		}
	}
	
	public function printContent() {
		echo $this->content;
	}
}

$suggest = t3lib_div::makeInstance('tx_dlf_search_suggest');
$suggest->main();
$suggest->printContent();

?>