<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2012, Zeutschel GmbH
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
 * Document class 'tx_dlf_solr_search' for the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_solr_search {

	/**
	 * @var integer
	 */
	protected $core;
	
	/**
	 * @var string
	 */
	protected $description;
	
	/**
	 * @var array
	 */
	protected $facetFields;
	
	/**
	 * @var string the filter query
	 */
	protected $filterQuery;

	/**
	 * @var string
	 */
	protected $label;
	
	/**
	 * @var integer
	 */
	protected $limit;
	
	/**
	 * @var string
	 */
	protected $order;
	
	/**
	 * @var integer
	 */
	protected $pid;
	
	/**
	 * @var string
	 */
	protected $queryString;
	
	/**
	 * @var string
	 */
	protected $source;
	
	/**
	 * Returns the passed $list parameter unchaned, if it is not NULL. Otherwise, 
	 * the tx_dlf_list instance stored in the current user session is returned.
	 * 
	 * @param tx_dlf_list $list the tx_dlf_list instance to ensure 
	 * 
	 * @return tx_dlf_list the session list or the passed parameter if it is not NULL. 
	 */
	protected static function ensureList($list) {

		if ($list == NULL) {

			// Instantiate from session.
			$list = t3lib_div::makeInstance('tx_dlf_list');

		}

		return $list;

	}

	public function __construct($core, $pid, $queryString = '', $filterQuery = '', $limit = 10000, $source = '', $order = '', $label = '', $description = '') {

		$this->core = $core;

		$this->pid = $pid;

		$this->queryString = $queryString;

		$this->filterQuery = $filterQuery;

		$this->limit = $limit;

		$this->source = $source;

		$this->order = $order;

		$this->label = $label;

		$this->description = $description;

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

		$getter = 'get'.ucfirst($var);

		// Getter overrides default get.
		if (method_exists($this, $getter)) {

			return $this->$getter();

		}

		return $this->$var;

	}

	public function __isset($var) {

		$value = $this->__get($var);

		return isset($value);

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

		$this->$var = $value;

	}

	public function __toString() {

		$result = get_class($this).' { ';

		$result .= tx_dlf_helper::array_toString(get_object_vars($this));

		$result .= ' }';

		return $result;

	}

	protected function getFilterQuery() {

		if ($this->filterQuery == NULL) {

			$this->filterQuery = array();

		}

		return $this->filterQuery;

	}

	/**
	 * Returns the query string of the search instance or '*'
	 * if query string and filter query are empty. 
	 * 
	 * @return string the query string of the search instance 
	 */
	protected function getQueryString() {

		// An empty query string leads to *-query, while the filter query is non-empty.
		if (empty($this->queryString) && !empty($this->filterQuery)) {

			return '*';

		}

		return $this->queryString;

	}

	/**
	 * Loads the $label and $description fields with corresponding values
	 * of the user session list.
	 * 
	 * @param void
	 */
	public function restoreHeader($list = NULL) {

		$list = self::ensureList($list);

		$this->label = $list->metadata['label'];

		$this->description = $list->metadata['description'];

	}

	/**
	 * Loads the $filterQuery field with corresponding value
	 * of the user session list.
	 *
	 * @param void
	 */
	public function restoreFilterQuery($list = NULL) {

		$list = self::ensureList($list);

		if (!empty($list->metadata['options'])) {

			$this->filterQuery = $list->metadata['options']['filter.query'];

		}

	}

	
	/**
	 * Loads the $queryString field with corresponding value
	 * of the user session list.
	 *
	 * @param void
	 */
	public function restoreQueryString($list = NULL) {

		$list = self::ensureList($list);

		if (!empty($list->metadata['options']) 
				// only search and facets plugins perform searches, yet
				&& (($list->metadata['options']['source'] === 'search') || ($list->metadata['options']['source'] === 'facets'))) {

			$this->queryString = $list->metadata['options']['select'];

		}

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr_search.php']);
}

?>