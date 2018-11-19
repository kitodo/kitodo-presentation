<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Solr class 'tx_dlf_solr' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_solr {

    /**
     * This holds the core name
     *
     * @var	string
     * @access protected
     */
    protected $core = '';

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
     * This holds the Solr service object
     *
     * @var	Solarium\Client
     * @access protected
     */
    protected $service;

    /**
     * Escape all special characters in a query string
     *
     * @access	public
     *
     * @param	string		$query: The query string
     *
     * @return	string		The escaped query string
     */
    public static function escapeQuery($query) {

        $helper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Solarium\Core\Query\Helper');

        // Escape query phrase or term.
        if (preg_match('/^".*"$/', $query)) {

            return '"'.$helper->escapePhrase(trim($query, '"')).'"';

        } else {

            return $helper->escapeTerm($query);

        }

    }

    /**
     * Escape all special characters in a query string while retaining valid field queries
     *
     * @access	public
     *
     * @param	string		$query: The query string
     * @param	integer		$pid: The PID for the field configuration
     *
     * @return	string		The escaped query string
     */
    public static function escapeQueryKeepField($query, $pid) {

        // Is there a field query?
        if (preg_match('/^[[:alnum:]]+_[tu][su]i:\(?.*\)?$/', $query)) {

            // Get all indexed fields.
            $fields = array ();

            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_metadata.index_name,tx_dlf_metadata.index_tokenized,tx_dlf_metadata.index_stored',
                'tx_dlf_metadata',
                'tx_dlf_metadata.index_indexed=1 AND tx_dlf_metadata.pid='.intval($pid).' AND (tx_dlf_metadata.sys_language_uid IN (-1,0) OR tx_dlf_metadata.l18n_parent=0)'.tx_dlf_helper::whereClause('tx_dlf_metadata'),
                '',
                '',
                ''
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

                while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_row($result)) {

                    $fields[] = $resArray[0].'_'.($resArray[1] ? 't' : 'u').($resArray[2] ? 's' : 'u').'i';

                }

            }

            // Check if queried field is valid.
            $splitQuery = explode(':', $query, 2);

            if (in_array($splitQuery[0], $fields)) {

                $query = $splitQuery[0].':('.self::escapeQuery(trim($splitQuery[1], '()')).')';

            } else {

                $query = self::escapeQuery($query);

            }

        } elseif (!empty($query) && $query !== '*') {

            // Don't escape plain asterisk search.
            $query = self::escapeQuery($query);

        }

        return $query;

    }

    /**
     * This is a singleton class, thus instances must be created by this method
     *
     * @access	public
     *
     * @param	mixed		$core: Name or UID of the core to load
     *
     * @return	tx_dlf_solr		Instance of this class
     */
    public static function getInstance($core) {

        // Save parameter for logging purposes.
        $_core = $core;

        // Get core name if UID is given.
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($core)) {

            $core = tx_dlf_helper::getIndexName($core, 'tx_dlf_solrcores');

        }

        // Check if core is set.
        if (empty($core)) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_solr->getInstance('.$_core.')] Invalid core name "'.$core.'" for Apache Solr', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return;

        }

        // Check if there is an instance in the registry already.
        if (is_object(self::$registry[$core]) && self::$registry[$core] instanceof self) {

            // Return singleton instance if available.
            return self::$registry[$core];

        }

        // Create new instance...
        $instance = new self($core);

        // ...and save it to registry.
        if ($instance->ready) {

            self::$registry[$core] = $instance;

            // Return new instance.
            return $instance;

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_solr->getInstance('.$_core.')] Could not connect to Apache Solr server', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return;

        }

    }

    /**
     * Returns the connection information for Solr
     *
     * @access	public
     *
     * @return	string		The connection parameters for a specific Solr core
     */
    public static function getSolrConnectionInfo() {

        $solrInfo = array ();

        // Extract extension configuration.
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

        // Derive Solr scheme
        $solrInfo['scheme'] = empty($conf['solrHttps']) ? 'http' : 'https';

        // Derive Solr host name.
        $solrInfo['host'] = ($conf['solrHost'] ? $conf['solrHost'] : '127.0.0.1');

        // Set username and password.
        $solrInfo['username'] = $conf['solrUser'];
        $solrInfo['password'] = $conf['solrPass'];

        // Set port if not set.
        $solrInfo['port'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['solrPort'], 1, 65535, 8983);

        // Append core name to path.
        $solrInfo['path'] = trim($conf['solrPath'], '/');

        // Timeout
        $solrInfo['timeout'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['solrTimeout'], 1, intval(ini_get('max_execution_time')), 10);

        return $solrInfo;

    }

    /**
     * Returns the request URL for a specific Solr core
     *
     * @access	public
     *
     * @param	string		$core: Name of the core to load
     *
     * @return	string		The request URL for a specific Solr core
     */
    public static function getSolrUrl($core = '') {

        // Get Solr connection information.
        $solrInfo = self::getSolrConnectionInfo();

        if ($solrInfo['username'] && $solrInfo['password']) {

            $host = $solrInfo['username'].':'.$solrInfo['password'].'@'.$solrInfo['host'];

        } else {

            $host = $solrInfo['host'];

        }

        // Return entire request URL.
        return $solrInfo['scheme'].'://'.$host.':'.$solrInfo['port'].'/'.$solrInfo['path'].'/'.$core;

    }

    /**
     * Get next unused Solr core number
     *
     * @access	public
     *
     * @param	integer		$start: Number to start with
     *
     * @return	integer		First unused core number found
     */
    public static function solrGetCoreNumber($start = 0) {

        $start = max(intval($start), 0);

        // Check if core already exists.
        if (self::getInstance('dlfCore'.$start) === NULL) {

            return $start;

        } else {

            return self::solrGetCoreNumber($start + 1);

        }

    }

    /**
     * Processes a search request.
     *
     * @access	public
     *
     * @return	tx_dlf_list		The result list
     */
    public function search() {

        $toplevel = array ();

        // Take over query parameters.
        $params =  $this->params;

        $params['filterquery'] = isset($params['filterquery']) ? $params['filterquery'] : array ();

        // Set some query parameters.
        $params['start'] = 0;
        $params['rows'] = 0;

        // Perform search to determine the total number of hits without fetching them.
        $selectQuery = $this->service->createSelect($params);
        $results = $this->service->select($selectQuery);

        $this->numberOfHits = $results->getNumFound();

        // Restore query parameters
        $params =  $this->params;

        $params['filterquery'] = isset($params['filterquery']) ? $params['filterquery'] : array ();

        // Restrict the fields to the required ones.
        $params['fields'] = 'uid,id';

        // Extend filter query to get all documents with the same uids.
        foreach ($params['filterquery'] as $key=>$value) {

            $params['filterquery'][$key] = array ('query' => '{!join from=uid to=uid}'.$value['query']);

        }

        // Set filter query to just get toplevel documents.
        $params['filterquery'][] = array ('query' => 'toplevel:true');

        // Set join query to get all documents with the same uids.
        $params['query'] = '{!join from=uid to=uid}'. $params['query'];

        // Perform search to determine the total number of toplevel hits and fetch the required rows.
        $selectQuery = $this->service->createSelect($params);
        $results = $this->service->select($selectQuery);

        $numberOfToplevelHits = $results->getNumFound();

        // Process results.
        foreach ($results as $doc) {

            $toplevel[$doc->id] = array (
                'u' => $doc->uid,
                'h' => '',
                's' => '',
                'p' => array ()
            );

        }

        // Save list of documents.
        $list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

        $list->reset();

        $list->add(array_values($toplevel));

        // Set metadata for search.
        $list->metadata = array (
            'label' => '',
            'description' => '',
            'options' => array (
                'source' => 'search',
                'engine' => 'solr',
                'select' => $this->params['query'],
                'userid' => 0,
                'params' => $this->params,
                'core' => $this->core,
                'pid' => $this->cPid,
                'order' => 'score',
                'order.asc' => TRUE,
                'numberOfHits' => $this->numberOfHits,
                'numberOfToplevelHits' => $numberOfToplevelHits
            )
        );

        return $list;

    }

    /**
     * Processes a search request and returns the raw Apache Solr Documents.
     *
     * @access	public
     *
     * @param	string		$query: The search query
     *
     * @return	array       The Apache Solr Documents that were fetched
     */
    public function search_raw($query = '', $parameters = array ()) {

        // Set additional query parameters.
        $parameters['start'] = 0;
        $parameters['rows'] = $this->limit;

        // Set query.
        $parameters['query'] = $query;

        // Perform search.
        $selectQuery = $this->service->createSelect(array_merge($this->params, $parameters));
        $solr_response = $this->service->select($selectQuery);

        $searchresult = array ();

        foreach ($solr_response as $doc) {
            $searchresult[] = $doc;
        }

        return $searchresult;
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
     * @return	Solarium\Client		Apache Solr service object
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

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_solr->__get('.$var.')] There is no getter function for property "'.$var.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

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

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_solr->__set('.$var.', [data])] There is no setter function for property "'.$var.'"', self::$extKey, SYSLOG_SEVERITY_WARNING, $value);

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
     * @param	string		$core: The name of the core to use
     *
     * @return	void
     */
    protected function __construct($core) {

        $solrInfo = self::getSolrConnectionInfo();

        $config = array (
            'endpoint' => array (
                'dlf' => array (
                    'scheme' => $solrInfo['scheme'],
                    'host' => $solrInfo['host'],
                    'port' => $solrInfo['port'],
                    'path' => '/'.$solrInfo['path'].'/',
                    'core' => $core,
                    'username' => $solrInfo['username'],
                    'password' => $solrInfo['password'],
                    'timeout' => $solrInfo['timeout']
                )
            )
        );

        // Instantiate Solarium\Client class.
        $this->service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Solarium\Client', $config);

        // Check if connection is established.
        $ping = $this->service->createPing();

        try {

            $this->service->ping($ping);

            // Set core name.
            $this->core = $core;

            // Instantiation successful!
            $this->ready = TRUE;

        } catch (Exception $e) {

            // Nothing to do here.

        }

    }

}
