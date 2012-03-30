<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_solr {

	/**
	 * The extension key
	 *
	 * @var string
	 * @access public
	 */
	public static $extKey = 'dlf';

	/**
	 * Get SolrPhpClient service object and establish connection to Solr server
	 * @see EXT:dlf/lib/SolrPhpClient/Apache/Solr/Service.php
	 *
	 * @access	public
	 *
	 * @param	mixed		$core: Name or UID of the core to load
	 *
	 * @return	mixed		Instance of Apache_Solr_Service or NULL on failure
	 */
	public static function solrConnect($core = 0) {

		// Load class.
		if (!class_exists('Apache_Solr_Service')) {

			require_once(t3lib_div::getFileAbsFileName('EXT:'.self::$extKey.'/lib/SolrPhpClient/Apache/Solr/Service.php'));

		}

		// Get Solr credentials.
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

		$host = ($conf['solrHost'] ? $conf['solrHost'] : 'localhost');

		// Prepend username and password to hostname.
		if ($conf['solrUser'] && $conf['solrPass']) {

			$host = $conf['solrUser'].':'.$conf['solrPass'].'@'.$host;

		}

		// Set port if not set.
		$port = t3lib_div::intInRange($conf['solrPort'], 1, 65535, 8180);

		// Get core name if UID is given.
		if (t3lib_div::testInt($core)) {

			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_solrcores.index_name AS index_name',
				'tx_dlf_solrcores',
				'tx_dlf_solrcores.uid='.intval($core).tx_dlf_helper::whereClause('tx_dlf_solrcores'),
				'',
				'',
				'1'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				list ($core) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

			} else {

				trigger_error('Could not find Solr core with UID '.$core, E_USER_NOTICE);

				return;

			}

		}

		// Append core name to path.
		$path = trim($conf['solrPath'], '/').'/'.$core;

		// Instantiate Apache_Solr_Service class.
		$solr = t3lib_div::makeInstance('Apache_Solr_Service', $host, $port, $path);

		// Check if connection is established.
		if ($solr->ping() !== FALSE) {

			// Do not collapse single value arrays.
			$solr->setCollapseSingleValueArrays = FALSE;

			return $solr;

		} else {

			trigger_error('Could not connect to Solr server with core "'.$core.'"', E_USER_ERROR);

			return;

		}

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
		if (self::solrConnect('dlfCore'.$start) === NULL) {

			return $start;

		} else {

			return self::solrGetCoreNumber($start + 1);

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
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php']);
}
*/

?>