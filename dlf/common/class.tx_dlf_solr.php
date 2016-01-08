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
	 * @var	Apache_Solr_Service
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

		// Load class.
		if (!class_exists('Apache_Solr_Service')) {

			require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:'.self::$extKey.'/lib/SolrPhpClient/Apache/Solr/Service.php'));

		}

		// Escape query phrase or term.
		if (preg_match('/^".*"$/', $query)) {

			return '"'.Apache_Solr_Service::escapePhrase(trim($query, '"')).'"';

		} else {

			return Apache_Solr_Service::escape($query);

		}

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
	 * Returns the request URL for a specific Solr core
	 *
	 * @access	public
	 *
	 * @param	string		$core: Name of the core to load
	 *
	 * @return	string		The request URL for a specific Solr core
	 */
	public static function getSolrUrl($core = '') {

		// Extract extension configuration.
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

		// Derive Solr host name.
		$host = ($conf['solrHost'] ? $conf['solrHost'] : 'localhost');

		// Prepend username and password to hostname.
		if ($conf['solrUser'] && $conf['solrPass']) {

			$host = $conf['solrUser'].':'.$conf['solrPass'].'@'.$host;

		}

		// Set port if not set.
		$port = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['solrPort'], 1, 65535, 8180);

		// Append core name to path.
		$path = trim($conf['solrPath'], '/').'/'.$core;

		// Return entire request URL.
		return 'http://'.$host.':'.$port.'/'.$path;

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
	 * @param	string		$query: The search query
	 *
	 * @return	tx_dlf_list		The result list
	 */
	public function search($query = '') {

		// Perform search.
		$results = $this->service->search((string) $query, 0, $this->limit, $this->params);

		$this->numberOfHits = count($results->response->docs);

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

		// Process results.
		foreach ($results->response->docs as $doc) {

			// Split toplevel documents from subparts.
			if ($doc->toplevel == 1) {

				// Prepare document's metadata for sorting.
				$docSorting = array ();

				foreach ($sorting as $index_name => $solr_name) {

					if (!empty($doc->$solr_name)) {

						$docSorting[$index_name] = (is_array($doc->$solr_name) ? $doc->$solr_name[0] : $doc->$solr_name);

					}

				}

				// Preserve relevance ranking.
				if (!empty($toplevel[$doc->uid]['s']['relevance'])) {

					$docSorting['relevance'] = $toplevel[$doc->uid]['s']['relevance'];

				}

				$toplevel[$doc->uid] = array (
					'u' => $doc->uid,
					's' => $docSorting,
					'p' => (!empty($toplevel[$doc->uid]['p']) ? $toplevel[$doc->uid]['p'] : array ())
				);

			} else {

				$toplevel[$doc->uid]['p'][] = $doc->id;

				if (!in_array($doc->uid, $checks)) {

					$checks[] = $doc->uid;

				}

			}

			// Add relevance to sorting values.
			if (empty($toplevel[$doc->uid]['s']['relevance'])) {

				$toplevel[$doc->uid]['s']['relevance'] = str_pad($i, 6, '0', STR_PAD_LEFT);

			}

			$i++;

		}

		// Check if the toplevel documents have metadata.
		foreach ($checks as $check) {

			if (empty($toplevel[$check]['u'])) {

				// Get information for toplevel document.
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_documents.uid AS uid,tx_dlf_documents.metadata_sorting AS metadata_sorting',
					'tx_dlf_documents',
					'tx_dlf_documents.uid='.intval($check).tx_dlf_helper::whereClause('tx_dlf_documents'),
					'',
					'',
					'1'
				);

				// Process results.
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

					$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

					// Prepare document's metadata for sorting.
					$sorting = unserialize($resArray['metadata_sorting']);

					if (!empty($sorting['type']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['type'])) {

						$sorting['type'] = tx_dlf_helper::getIndexName($sorting['type'], 'tx_dlf_structures', $this->cPid);

					}

					if (!empty($sorting['owner']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['owner'])) {

						$sorting['owner'] = tx_dlf_helper::getIndexName($sorting['owner'], 'tx_dlf_libraries', $this->cPid);

					}

					if (!empty($sorting['collection']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['collection'])) {

						$sorting['collection'] = tx_dlf_helper::getIndexName($sorting['collection'], 'tx_dlf_collections', $this->cPid);

					}

					// Preserve relevance ranking.
					if (!empty($toplevel[$check]['s']['relevance'])) {

						$sorting['relevance'] = $toplevel[$check]['s']['relevance'];

					}

					$toplevel[$check] = array (
						'u' => $resArray['uid'],
						's' => $sorting,
						'p' => $toplevel[$check]['p']
					);

				} else {

					// Clear entry if there is no (accessible) toplevel document.
					unset ($toplevel[$check]);

				}

			}

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
				'select' => $query,
				'userid' => 0,
				'params' => $this->params,
				'core' => $this->core,
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

		// Load class.
		if (!class_exists('Apache_Solr_Service')) {

			require_once(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:'.self::$extKey.'/lib/SolrPhpClient/Apache/Solr/Service.php'));

		}

		// Get Solr credentials.
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

		$host = ($conf['solrHost'] ? $conf['solrHost'] : 'localhost');

		// Prepend username and password to hostname.
		if ($conf['solrUser'] && $conf['solrPass']) {

			$host = $conf['solrUser'].':'.$conf['solrPass'].'@'.$host;

		}

		// Set port if not set.
		$port = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($conf['solrPort'], 1, 65535, 8180);

		// Append core name to path.
		$path = trim($conf['solrPath'], '/').'/'.$core;

		// Instantiate Apache_Solr_Service class.
		$this->service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Apache_Solr_Service', $host, $port, $path);

		// Check if connection is established.
		if ($this->service->ping() !== FALSE) {

			// Do not collapse single value arrays.
			$this->service->setCollapseSingleValueArrays = FALSE;

			// Set core name.
			$this->core = $core;

			// Instantiation successful!
			$this->ready = TRUE;

		}

	}

}

/* No xclasses allowed for singleton classes!
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php']);
}
*/
