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
 * Helper class 'tx_dlf_helper' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_helper {

	/**
	 * The extension key
	 *
	 * @var string
	 * @access public
	 */
	public static $extKey = 'dlf';

	/**
	 * Searches the array recursively for a given value and returns the corresponding key if successful
	 * @see http://php.net/array_search
	 *
	 * @access	public
	 *
	 * @param	mixed		$needle: The searched value
	 * @param	array		$haystack: The array to search in
	 * @param	boolean		$strict: Check needle's type, too?
	 *
	 * @return	mixed		Returns the needle's key if found and FALSE otherwise
	 */
	public static function array_search_recursive($needle, $haystack, $strict = FALSE) {

		foreach ($haystack as $key => $value) {

			$current = $key;

			if (($strict && $value === $needle) || (!$strict && $value == $needle) || (is_array($value) && self::array_search_recursive($needle, $value, $strict) !== FALSE)) {

				return $current;

			}

		}

		return FALSE;

	}

	/**
	 * Check if given identifier is a valid identifier of the German National Library
	 * @see	http://support.d-nb.de/iltis/onlineRoutinen/Pruefziffernberechnung.htm
	 *
	 * @access	public
	 *
	 * @param	string		$id: The identifier to check
	 * @param	string		$type: What type is the identifier supposed to be?
	 * 						Possible values: PPN, IDN, PND, ZDB, SWD, GKD
	 *
	 * @return	boolean		Is $id a valid GNL identifier of the given $type?
	 */
	public static function checkIdentifier($id, $type) {

		$digits = substr($id, 0, 8);

		$checksum = 0;

		for ($i = 0, $j = strlen($digits); $i < $j; $i++) {

			$checksum += (9 - $i) * intval(substr($digits, $i, 1));

		}

		$checksum = (11 - ($checksum % 11)) % 11;

		switch (strtoupper($type)) {

			case 'PPN':
			case 'IDN':
			case 'PND':

				if ($checksum == 10) {

					$checksum = 'X';

				}

				if (!preg_match('/[0-9]{8}[0-9X]{1}/i', $id)) {

					return FALSE;

				} elseif (strtoupper(substr($id, -1, 1)) != $checksum) {

					return FALSE;

				}

				break;

			case 'ZDB':

				if ($checksum == 10) {

					$checksum = 'X';

				}

				if (!preg_match('/[0-9]{8}-[0-9X]{1}/i', $id)) {

					return FALSE;

				} elseif (strtoupper(substr($id, -1, 1)) != $checksum) {

					return FALSE;

				}

				break;

			case 'SWD':

				$checksum = 11 - $checksum;

				if (!preg_match('/[0-9]{8}-[0-9]{1}/i', $id)) {

					return FALSE;

				} elseif ($checksum == 10) {

					return self::checkIdentifier(($digits + 1).substr($id, -2, 2), 'SWD');

				} elseif (substr($id, -1, 1) != $checksum) {

					return FALSE;

				}

				break;

			case 'GKD':

				$checksum = 11 - $checksum;

				if ($checksum == 10) {

					$checksum = 'X';

				}

				if (!preg_match('/[0-9]{8}-[0-9X]{1}/i', $id)) {

					return FALSE;

				} elseif (strtoupper(substr($id, -1, 1)) != $checksum) {

					return FALSE;

				}

				break;

		}

		return TRUE;

	}

	/**
	 * Get a backend user object (even in frontend mode)
	 *
	 * @access	public
	 *
	 * @return	t3lib_beUserAuth		Instance of t3lib_beUserAuth or NULL on failure
	 */
	public static function getBeUser() {

		if (TYPO3_MODE === 'FE') {

			// TODO: Anpassen! (aus typo3/init.php übernommen)
			$userObj = t3lib_div::makeInstance('t3lib_beUserAuth');

			$userObj->start();

			$userObj->backendCheckLogin();

			return $userObj;

		} elseif (TYPO3_MODE === 'BE') {

			return $GLOBALS['BE_USER'];

		} else {

			trigger_error('Unexpected TYPO3_MODE', E_USER_WARNING);

			return;

		}

	}

	/**
	 * Get the "index_name" for an UID
	 *
	 * @access	public
	 *
	 * @param	integer		$uid: The UID of the record
	 * @param	string		$table: Get the "index_name" from this table
	 * @param	string		$pid: Get the "index_name" from this page
	 *
	 * @return	string		"index_name" for the given UID
	 */
	public static function getIndexName($uid, $table, $pid) {

		$uid = max(intval($uid), 0);

		$pid = max(intval($pid), 0);

		if (!$uid || !$pid || !in_array($table, array ('tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures'))) {

			trigger_error('At least one argument is not valid: UID='.$uid.' PID='.$pid.' TABLE='.$table, E_USER_WARNING);

			return '';

		}

		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$table.'.index_name AS index_name',
			$table,
			$table.'.uid='.$uid.' AND '.$table.'.pid='.$pid.self::whereClause($table),
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result) > 0) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

			return $resArray['index_name'];

		}

		trigger_error('No "index_name" with UID '.$uid.' found for PID '.$pid.' in TABLE '.$table, E_USER_WARNING);

		return '';

	}

	/**
	 * Get language name from 'static_info_tables'
	 * TODO: 3-stellige Sprachcodes
	 *
	 * @access	public
	 *
	 * @param	string		$code: ISO 3166-2 language code
	 *
	 * @return	string		Localized full name of language or unchanged input
	 */
	public static function getLanguageName($code) {

		$code = strtoupper(trim($code));

		if (!preg_match('/^[A-Z]{2}$/', $code) || !t3lib_extMgm::isLoaded('static_info_tables')) {

			trigger_error('Invalid language code or extension "static_info_tables" not loaded', E_USER_WARNING);

			return $code;

		}

		if (!$GLOBALS['TSFE']->lang || !t3lib_extMgm::isLoaded('static_info_tables_'.$GLOBALS['TSFE']->lang)) {

			$field = 'lg_name_en';

		} else {

			$field = 'lg_name_'.$GLOBALS['TSFE']->lang;

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'static_languages.'.$field.' AS language',
			'static_languages',
			'static_languages.lg_iso_2='.$GLOBALS['TYPO3_DB']->fullQuoteStr($code, 'static_languages'),
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			return $resArray['language'];

		} else {

			trigger_error('Language code not found in extension "static_info_tables"', E_USER_WARNING);

			return $code;

		}

	}

	/**
	 * Get the URN of an object
	 * @see	http://www.persistent-identifier.de/?link=316
	 *
	 * @access	public
	 *
	 * @param	string		$base: The namespace and base URN
	 * @param	string		$id: The object's identifier
	 *
	 * @return	string		Uniform Resource Name as string
	 */
	public static function getURN($base, $id) {

		$concordance = array(
			'0' => 1,
			'1' => 2,
			'2' => 3,
			'3' => 4,
			'4' => 5,
			'5' => 6,
			'6' => 7,
			'7' => 8,
			'8' => 9,
			'9' => 41,
			'a' => 18,
			'b' => 14,
			'c' => 19,
			'd' => 15,
			'e' => 16,
			'f' => 21,
			'g' => 22,
			'h' => 23,
			'i' => 24,
			'j' => 25,
			'k' => 42,
			'l' => 26,
			'm' => 27,
			'n' => 13,
			'o' => 28,
			'p' => 29,
			'q' => 31,
			'r' => 12,
			's' => 32,
			't' => 33,
			'u' => 11,
			'v' => 34,
			'w' => 35,
			'x' => 36,
			'y' => 37,
			'z' => 38,
			'-' => 39,
			':' => 17,
		);

		$urn = strtolower($base.$id);

		if (preg_match('/[^a-z0-9:-]/', $urn)) {

			trigger_error('Invalid chars in URN', E_USER_WARNING);

			return '';

		}

		$digits = '';

		for ($i = 0, $j = strlen($urn); $i < $j; $i++) {

			$digits .= $concordance[substr($urn, $i, 1)];

		}

		$checksum = 0;

		for ($i = 0, $j = strlen($digits); $i < $j; $i++) {

			$checksum += ($i + 1) * intval(substr($digits, $i, 1));

		}

		$checksum = substr(intval($checksum / intval(substr($digits, -1, 1))), -1, 1);

		return $base.$id.$checksum;

	}

	/**
	 * Check if given ID is a valid Pica Production Number (PPN)
	 *
	 * @access	public
	 *
	 * @param	string		$ppn: The identifier to check
	 *
	 * @return	boolean		Is $id a valid PPN?
	 */
	public static function isPPN($id) {

		return self::checkIdentifier($id, 'PPN');

	}

	/**
	 * Check if given internal "index_name" is translatable
	 *
	 * @access	public
	 *
	 * @param	string		$index_name: The internal "index_name" to translate
	 * @param	string		$table: Get the translation from this table
	 * @param	string		$pid: Get the translation from this page
	 *
	 * @return	boolean		Is $index_name translatable?
	 */
	public static function isTranslatable($index_name, $table, $pid = 0) {

		return self::translate($index_name, $table, $pid, TRUE);

	}

	/**
	 * Load value from user's session.
	 *
	 * @access	public
	 *
	 * @param	string		$key: Session key for retrieval
	 *
	 * @return	mixed		Session value for given key or NULL on failure
	 */
	public static function loadFromSession($key) {

		// Cast to string for security reasons.
		$key = (string) $key;

		if (!$key) {

			trigger_error('No session key given', E_USER_WARNING);

			return;

		}

		if (TYPO3_MODE === 'FE') {

			return $GLOBALS['TSFE']->fe_user->getKey('ses', $key);

		} elseif (TYPO3_MODE === 'BE') {

			return $GLOBALS['BE_USER']->getSessionData($key);

		} else {

			trigger_error('Unexpected TYPO3_MODE', E_USER_WARNING);

			return;

		}

	}

	/**
	 * Process a data and/or command map with TYPO3 core engine.
	 *
	 * @access	public
	 *
	 * @return	array		Array of substituted "NEW..." identifiers and their actual UIDs.
	 */
	public static function processDB(array $data = array (), array $cmd = array (), $reverseOrder = FALSE) {

		// Instantiate TYPO3 core engine.
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');

		// Set some configuration variables.
		$tce->stripslashes_values = FALSE;

		// Load data and command arrays.
		$tce->start($data, $cmd, self::getBeUser());

		// Process command map first if default order is reversed.
		if ($cmd && $reverseOrder) {

			$tce->process_cmdmap();

		}

		// Process data map.
		if ($data) {

			$tce->process_datamap();

		}

		// Process command map if processing order is not reversed.
		if ($cmd && !$reverseOrder) {

			$tce->process_cmdmap();

		}

		return $tce->substNEWwithIDs;

	}

	/**
	 * Save given value to user's session.
	 *
	 * @access	public
	 *
	 * @param	string		$value: Value to save
	 * @param	string		$key: Session key for retrieval
	 *
	 * @return	boolean		TRUE on success, FALSE on failure
	 */
	public static function saveToSession($value, $key) {

		// Cast to string for security reasons.
		$key = (string) $key;

		if (!$key) {

			trigger_error('No session key given', E_USER_WARNING);

			return FALSE;

		}

		if (TYPO3_MODE === 'FE') {

			$GLOBALS['TSFE']->fe_user->setKey('ses', $key, $value);

			$GLOBALS['TSFE']->fe_user->storeSessionData();

			return TRUE;

		} elseif (TYPO3_MODE === 'BE') {

			$GLOBALS['BE_USER']->setAndSaveSessionData($key, $value);

			return TRUE;

		} else {

			trigger_error('Unexpected TYPO3_MODE', E_USER_WARNING);

			return FALSE;

		}

	}

	/**
	 * This validates a METS file against its schemas
	 * TODO: nicht funktionstüchtig!
	 *
	 * @access	public
	 *
	 * @param	SimpleXMLElement		$xml:
	 *
	 * @return	void
	 */
	public static function schemaValidate(SimpleXMLElement $xml) {

		$_libxmlErrors = libxml_use_internal_errors(TRUE);

		// Get schema locations.
		$xml->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

		$_schemaLocations = $xml->xpath('//*[@xsi:schemaLocation]');

		foreach ($_schemaLocations as $_schemaLocation) {

			$_schemas = explode(' ', (string) $_schemaLocation->attributes('http://www.w3.org/2001/XMLSchema-instance')->schemaLocation);

			for ($i = 1, $j = count($_schemas); $i <= $j; $i++) {

				if ($_schemas[$i] == 'http://www.loc.gov/METS/') {

					$schema['mets'] = $_schemas[$i + 1];

				} elseif ($_schemas[$i] == 'http://www.loc.gov/mods/v3') {

					$schema['mods'] = $_schemas[$i + 1];

				}

			}

		}
		// TODO: Error-Handling (keine Schemas gefunden)

		// Validate METS part against schema.
		$dom = new DOMDocument('1.0', 'UTF-8');

		$dom->appendChild($dom->importNode(dom_import_simplexml($this->mets), TRUE));

		$dom->schemaValidate($schema['mets']);

		// TODO: Error-Handling (invalider METS-Part)
		// libxml_get_last_error() || libxml_get_errors() || libxml_clear_errors()

		// Validate dmdSec parts against schema.
		foreach ($this->dmdSec as $dmdSec) {

			switch ($dmdSec['type']) {

				case 'MODS':

					$dom = new DOMDocument('1.0', 'UTF-8');

					$dom->appendChild($dom->importNode(dom_import_simplexml($dmdSec['xml']), TRUE));

					$dom->schemaValidate($schema['mods']);

					// TODO: Error-Handling (invalider MODS-Part)
					// libxml_get_last_error() || libxml_get_errors() || libxml_clear_errors()

					break;

			}

		}

		libxml_use_internal_errors($_libxmlErrors);

	}

	/**
	 * This translates an internal "index_name"
	 *
	 * @access	public
	 *
	 * @param	string		$index_name: The internal "index_name" to translate
	 * @param	string		$table: Get the translation from this table
	 * @param	string		$pid: Get the translation from this page
	 * @param	boolean		$checkOnly: Don't translate, only check for translation
	 *
	 * @return	mixed		Translated label or boolean value if $checkOnly is set
	 */
	public static function translate($index_name, $table, $pid, $checkOnly = FALSE) {

		static $labels = array ();

		$pid = max(intval($pid), 0);

		if (!$pid) {

			trigger_error('No PID given for translations', E_USER_WARNING);

			return $index_name;

		}

		// Check if "index_name" is an UID.
		if (t3lib_div::testInt($index_name)) {

			$index_name = self::getIndexName($index_name, $table, $pid);

		}

		// Check if we already got a translation.
		if (empty($labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name])) {

			// Check if this table is allowed for translation.
			if (in_array($table, array ('tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures'))) {

				$additionalWhere = ' AND sys_language_uid IN (-1,0)';

				if ($GLOBALS['TSFE']->sys_language_content > 0) {

					$additionalWhere = ' AND (sys_language_uid IN (-1,0) OR (sys_language_uid='.intval($GLOBALS['TSFE']->sys_language_content).' AND l18n_parent=0))';

				}

				// Get labels from database.
				$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$table,
					'pid='.$pid.$additionalWhere.self::whereClause($table),
					'',
					'',
					''
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result) > 0) {

					while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

						// Overlay localized labels if available.
						if ($GLOBALS['TSFE']->sys_language_content > 0) {

							$resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay($table, $resArray, $GLOBALS['TSFE']->sys_language_content, ($GLOBALS['TSFE']->sys_language_mode == 'strict' ? 'hideNonTranslated' : ''));

						}

						if ($resArray) {

							$labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$resArray['index_name']] = $resArray['label'];

						}

					}

				} else {

					trigger_error('There are no entries with PID '.$pid.' in table '.$table.' or you are not allowed to access them', E_USER_ERROR);

				}

			} else {

				trigger_error('The table '.$table.' is not allowed for translation', E_USER_ERROR);

			}

		}

		if (!empty($labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name])) {

			if ($checkOnly) {

				return TRUE;

			} else {

				return $labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name];

			}

		} else {

			if ($checkOnly) {

				return FALSE;

			} else {

				return $index_name;

			}

		}

	}

	/**
	 * This returns the additional WHERE clause of a table based on its TCA configuration
	 *
	 * @access	public
	 *
	 * @param	string		$table: Table name as defined in TCA
	 *
	 * @return	string		Additional WHERE clause
	 */
	public static function whereClause($table) {

		if (TYPO3_MODE === 'FE') {

			// Tables "tx_dlf_solrcores" and "tx_dlf_formats" always have PID 0.
			if (in_array($table, array ('tx_dlf_solrcores', 'tx_dlf_formats'))) {

				return t3lib_BEfunc::deleteClause($table);

			}

			// $GLOBALS['TSFE']->sys_page is not always available in frontend.
			if (is_object($GLOBALS['TSFE']->sys_page)) {

				return $GLOBALS['TSFE']->sys_page->enableFields($table);

			} else {

				$t3lib_pageSelect = t3lib_div::makeInstance('t3lib_pageSelect');

				$GLOBALS['TSFE']->includeTCA();

				return $t3lib_pageSelect->enableFields($table);

			}

		} elseif (TYPO3_MODE === 'BE') {

			return t3lib_BEfunc::deleteClause($table);

		} else {

			trigger_error('Unexpected TYPO3_MODE', E_USER_ERROR);

			return ' AND 1=-1';

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
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_helper.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_helper.php']);
}
*/

?>