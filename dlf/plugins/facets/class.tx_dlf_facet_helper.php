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
 * Indexing class 'tx_dlf_facet_helper' for the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_facet_helper {

	/**
	 * Array of facets
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $facets = array ();

	public static function getFacets($pid) {

		if (!array_key_exists($pid, self::$facets)) {

			self::$facets[$pid] = array();

			self::loadFacets($pid);

		}

		return self::$facets[$pid];

	}
	
	/**
	 * Load indexing configuration for facets.
	 *
	 * @access	protected
	 *
	 * @param	integer		$pid: The configuration page's UID
	 *
	 * @return	void
	 */
	protected static function loadFacets($pid) {

		// Get the metadata indexing options.
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.is_facet AS is_facet',
			'tx_dlf_metadata',
			'tx_dlf_metadata.pid='.intval($pid).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'',
			''
		);
	
		while ($_indexing = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			if ($_indexing['is_facet']) {

				self::$facets[$pid][$_indexing['index_name']] = tx_dlf_helper::translate($_indexing['index_name'], 'tx_dlf_metadata', $pid);

			}

		}

	}

	/**
	 * This is a static class, thus no instances should be created
	 *
	 * @access	protected
	 */
	protected function __construct() {}

}

/* No xclasses for static classes!
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/facets/class.tx_dlf_facet_helper.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/facets/class.tx_dlf_facet_helper.php']);
}
*/

?>