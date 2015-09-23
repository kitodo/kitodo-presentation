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
use \ElasticSearch\Client;
/**
 * Elasticsearch class 'tx_dlf_elasticsearch' for the 'dlf' extension.
 *
 * @author	Christopher Timm <timm@effective-webwork.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_elasticsearch {

	/**
	 * This holds the index name
	 *
	 * @var	string
	 * @access protected
	 */
	protected $index = '';

	/**
	 * This holds the type name
	 *
	 * @var	string
	 * @access protected
	 */
	protected $type = '';

	/**
	 * This holds the PID for the configuration
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $cPid = 0;

	/**
	 * The extension key
	 *
	 * @var	string
	 * @access public
	 */
	public static $extKey = 'dlf';

	/**
	 * This holds the max results
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $limit = 50000;

	/**
	 * This holds the number of hits for last search
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $numberOfHits = 0;

	/**
	 * This holds the additional query parameters
	 *
	 * @var	array
	 * @access protected
	 */
	protected $params = array ();

	/**
	 * Is the search instantiated successfully?
	 *
	 * @var	boolean
	 * @access protected
	 */
	protected $ready = FALSE;

	/**
	 * This holds the singleton search objects with their core as array key
	 *
	 * @var	array(tx_dlf_solr)
	 * @access protected
	 */
	protected static $registry = array ();

	/**
	 * This holds the Elasticsearch service object
	 *
	 * @var	ElasticSearchPhpClient
	 * @access protected
	 */
	protected $service;

	/**
	 * This is a singleton class, thus instances must be created by this method
	 *
	 * @access	public
	 *
	 * @param	mixed		$core: Name or UID of the core to load
	 *
	 * @return	tx_dlf_solr		Instance of this class
	 */
	public static function getInstance($conf) {

		// // Save parameter for logging purposes.
		// $_core = $core;

		// // Get core name if UID is given.
		// if (tx_dlf_helper::testInt($core)) {

		// 	$core = tx_dlf_helper::getIndexName($core, 'tx_dlf_solrcores');

		// }

		// // Check if core is set.
		// if (empty($core)) {

		// 	if (TYPO3_DLOG) {

		// 		t3lib_div::devLog('[tx_dlf_solr->getInstance('.$_core.')] Invalid core name "'.$core.'" for Apache Solr', self::$extKey, SYSLOG_SEVERITY_ERROR);

		// 	}

		// 	return;

		// }
 
        $name = implode("_", $conf);

		// Check if there is an instance in the registry already.
		if (is_object(self::$registry[$name]) && self::$registry[$name] instanceof self) {

			// Return singleton instance if available.
			return self::$registry[$name];

		}

		// Create new instance...
		$instance = new self($conf);

		// ...and save it to registry.
		if ($instance->ready) {

			self::$registry[$name] = $instance;

			// Return new instance.
			return $instance;

		} else {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_solr->getInstance()] Could not connect to Elasticsearch server', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return;

		}

	}

	/**
	 * Returns the request URL for a specific ElasticSearch index
	 *
	 * @access	public
	 *
	 * @param	string		$index: Name of the index to load
	 *
	 * @return	string		The request URL for a specific index
	 */
	public static function getElasticSearchUrl($index = '') {

		// Extract extension configuration.
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

		// Derive Elasticsearch host name.
		$host = ($conf['elasticSearchHost'] ? $conf['elasticSearchHost'] : 'localhost');

		// Prepend username and password to hostname.
		// if ($conf['solrUser'] && $conf['solrPass']) {

		// 	$host = $conf['solrUser'].':'.$conf['solrPass'].'@'.$host;

		// }

		// Set port if not set.
		$port = tx_dlf_helper::intInRange($conf['elasticSearchPort'], 1, 65535, 8180);

		// Append core name to path.
		$path = trim($conf['index'], '/').'/'.$type;

		// Return entire request URL.
		return 'http://'.$host.':'.$port.'/'.$path;

	}

	/**
	 * Processes a search request.
	 *
	 * @access	public
	 *
	 * @param	string		$query: The search query
	 *
	 * @return	tx_dlf_list		The result list
	 */
	public function search($query = '') {

		// Perform search.
		// $results = $this->service->search((string) $query, 0, $this->limit, $this->params);
		// $results = $this->service->search((string) $query);

        $esQuery['query']['bool']['should'][0]['query_string']['query'] = $query;
        $esQuery['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = $query;

        $esQuery['query']['bool']['minimum_should_match'] = "1"; // 1

        $esQuery['query']['bool']['should'][1]['has_child']['child_type'] = "datastream"; // 1

        $results = $this->service->search($esQuery);


		//$this->cPid = 9;
		 
		$this->numberOfHits = $results['hits']['total'];

		$toplevel = array ();

		$checks = array ();

		// Get metadata configuration.
		if ($this->numberOfHits > 0) {

			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_metadata.index_name AS index_name',
				'tx_dlf_metadata',
				'tx_dlf_metadata.is_sortable=1 AND tx_dlf_metadata.pid='.intval($this->cPid).tx_dlf_helper::whereClause('tx_dlf_metadata'),
				'',
				'',
				''
			);

			$sorting = array ();

			while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

				$sorting[$resArray['index_name']] = $resArray['index_name'].'_sorting';

			}

		}

		// Keep track of relevance.
		$i = 0;

		foreach ($results['hits']['hits'] as $doc){
			$toplevel[] = array ( 
				'u' => $doc['_source']['PID'],
				's' => array(),
				'p' => ''
			);
		}


		// Save list of documents.
		$list = t3lib_div::makeInstance('tx_dlf_list');

		$list->reset();

		$list->add(array_values($toplevel));

		// Set metadata for search.
		$list->metadata = array (
			'label' => '',
			'description' => '',
			'options' => array (
				'source' => 'search',
				'engine' => 'elasticsearch',
				'select' => $query,
				'userid' => 0,
				'params' => $this->params,
				// 'core' => $this->core,
				'pid' => $this->cPid,
				'order' => 'relevance',
				'order.asc' => TRUE,
			)
		);

		return $list;

	}

	/**
	 * This returns $this->limit via __get()
	 *
	 * @access	protected
	 *
	 * @return	integer		The max number of results
	 */
	protected function _getLimit() {

		return $this->limit;

	}

	/**
	 * This returns $this->numberOfHits via __get()
	 *
	 * @access	protected
	 *
	 * @return	integer		Total number of hits for last search
	 */
	protected function _getNumberOfHits() {

		return $this->numberOfHits;

	}

	/**
	 * This returns $this->ready via __get()
	 *
	 * @access	protected
	 *
	 * @return	boolean		Is the search instantiated successfully?
	 */
	protected function _getReady() {

		return $this->ready;

	}

	/**
	 * This returns $this->service via __get()
	 *
	 * @access	protected
	 *
	 * @return	Apache_Solr_Service		Apache Solr service object
	 */
	protected function _getService() {

		return $this->service;

	}

	/**
	 * This sets $this->cPid via __set()
	 *
	 * @access	protected
	 *
	 * @param	integer		$value: The new PID for the metadata definitions
	 *
	 * @return	void
	 */
	protected function _setCPid($value) {

		$this->cPid = max(intval($value), 0);

	}

	/**
	 * This sets $this->limit via __set()
	 *
	 * @access	protected
	 *
	 * @param	integer		$value: The max number of results
	 *
	 * @return	void
	 */
	protected function _setLimit($value) {

		$this->limit = max(intval($value), 0);

	}

	/**
	 * This sets $this->params via __set()
	 *
	 * @access	protected
	 *
	 * @param	array		$value: The query parameters
	 *
	 * @return	void
	 */
	protected function _setParams(array $value) {

		$this->params = $value;

	}

	/**
	 * This magic method is called each time an invisible property is referenced from the object
	 *
	 * @access	public
	 *
	 * @param	string		$var: Name of variable to get
	 *
	 * @return	mixed		Value of $this->$var
	 */
	public function __get($var) {

		$method = '_get'.ucfirst($var);

		if (!property_exists($this, $var) || !method_exists($this, $method)) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_solr->__get('.$var.')] There is no getter function for property "'.$var.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		} else {

			return $this->$method();

		}

	}

	/**
	 * This magic method is called each time an invisible property is referenced from the object
	 *
	 * @access	public
	 *
	 * @param	string		$var: Name of variable to set
	 * @param	mixed		$value: New value of variable
	 *
	 * @return	void
	 */
	public function __set($var, $value) {

		$method = '_set'.ucfirst($var);

		if (!property_exists($this, $var) || !method_exists($this, $method)) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_solr->__set('.$var.', [data])] There is no setter function for property "'.$var.'"', self::$extKey, SYSLOG_SEVERITY_WARNING, $value);

			}

		} else {

			$this->$method($value);

		}

	}

	/**
	 * This is a singleton class, thus the constructor should be private/protected
	 *
	 * @access	protected
	 *
	 *
	 * @return	void
	 */
	protected function __construct($elasticsearchConf) {

		$extensionPath = t3lib_extMgm::extPath('dlf');

		require_once $extensionPath . 'lib/ElasticSearchPhpClient/vendor/autoload.php';
		//require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:'.self::$extKey.'/lib/ElasticSearchPhpClient/src/ElasticSearch/Client.php'));

		// get configuration for elasticsearch
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

		// get host
		$host = ($conf['elasticSearchHost'] ? $conf['elasticSearchHost'] : 'localhost');

		// get port
		$port = tx_dlf_helper::intInRange($conf['elasticSearchPort'], 1, 65535, 9200);

		// index
		// $index = $conf['elasticSearchIndex'];

		// //type
		// $type = $conf['elasticSearchType'];
		
		// index
		$this->index = $elasticsearchConf[0];
		//type
		$this->type = $elasticsearchConf[1];
		// configuration array for elasticsearch client
		$params = array();
		
		if ($conf['elasticSearchUser'] && $conf['elasticSearchPass']) {

			// Authentication configuration
			$params['connectionParams']['auth'] = array(
		    $conf['elasticSearchUser'],
		    $conf['elasticSearchPass'],
		    'Basic' 
			);

		}

		

		// establish connection 
		$this->service = Client::connection(array(
		    'servers' => $host.':'.$port,
		    'protocol' => 'http',
		    'index' => $this->index,
		    'type' => $this->type
		    // 'index' => $index,
		    // 'type' => $type
		));

		// Instantiation successful!
		$this->ready = TRUE;

	}

}

/* No xclasses allowed for singleton classes!
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php']);
}
*/

?>
