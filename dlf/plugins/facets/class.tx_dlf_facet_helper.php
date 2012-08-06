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
 * Helper class 'tx_dlf_facet_helper' for the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_facet_helper {

	/**
	 * Array of facets keyed by the configuration page's UID 
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $facets = array ();

	/**
	 * Returns true, if the first parameter ends with the second parameter; false otherwise.
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * 
	 * @return boolean true, if the first parameter ends with the second parameter; false otherwise. 
	 */
	protected static function endsWith($haystack, $needle) {

		$length = strlen($needle);

		if ($length == 0) {

			return true;

		}

		$start  = $length * -1;

		return (substr($haystack, $start) === $needle);

	}

	/**
	 * Returns facet metadata index names for the passed
	 * configuration page.
	 * 
	 * @param integer $pid  the configuration page's UID
	 * 
	 * @return array of facet index names
	 */
	public static function getFacets($pid) {

		if (!array_key_exists($pid, self::$facets)) {

			self::$facets[$pid] = array();

			self::loadFacets($pid);

		}

		return self::$facets[$pid];

	}

	/**
	 * Load indexing configuration for facets into the static 
	 * $facets array. All metadata index names are adequately
	 * boxed with the succeeding '_faceting' string.
	 *
	 * @access protected
	 *
	 * @param integer $pid  the configuration page's UID
	 *
	 * @return void
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

				self::$facets[$pid][$_indexing['index_name'].'_faceting'] = tx_dlf_helper::translate($_indexing['index_name'], 'tx_dlf_metadata', $pid);

			}

		}

	}

	/**
	 * Translates the passed facet index name into its 
	 * human-readable translation.
	 * 
	 * @param string $facetField the facet index name 
	 * @param integer $pid the configuration page's UID
	 * 
	 * @return string human-readable translation of passed facet field
	 */
	public static function translateFacetField($facetField, $pid) {

		return tx_dlf_helper::translate(preg_replace('{_faceting$}', '', $facetField), 'tx_dlf_metadata', $pid);

	}

	/**
	 * Translates the passed facet field's value into its 
	 * human-readable translation.
	 * 
	 * @param string $facetField the facet index name
	 * @param string $value facet field's value
	 * @param integer $pid the configuration page's UID
	 * 
	 * @return string human-readable translation of passed facet field's value
	 */
	public static function translateFacetValue($facetField, $value, $pid) {
		
		if (empty($facetField) || empty($value)) {
			
			return '';
			
		}
		
		$result = $value;
		
		$index_name = preg_replace('{_faceting$}', '', $facetField);
		
		/*
		 * the following shares code with class tx_dlf_metadata.
		 * TODO: discuss central utlility function to render index *values*
		 */
		
		// Translate name of holding library.
		if ($index_name == 'owner') {

			$result = tx_dlf_helper::translate($value, 'tx_dlf_libraries', $pid);

			// Translate document type.
		} elseif ($index_name == 'type') {

			$result = tx_dlf_helper::translate($value, 'tx_dlf_structures', $pid);

			// Translate ISO 639 language code.
		} elseif ($index_name == 'language') {

			$result = tx_dlf_helper::getLanguageName($value);

		}
		
		return htmlspecialchars($result);

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