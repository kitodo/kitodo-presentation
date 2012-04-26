<?php
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