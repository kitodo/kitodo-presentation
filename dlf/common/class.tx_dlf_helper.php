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
 * Helper class 'tx_dlf_helper' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_helper {

	/**
	 * The extension key
	 *
	 * @var	string
	 * @access public
	 */
	public static $extKey = 'dlf';

	/**
	 * The locallang array for common use
	 *
	 * @var	array
	 * @access protected
	 */
	protected static $locallang = array ();

	/**
	 * Implements array_merge_recursive_overrule() in a cross-version way.
	 *
	 * This code is a copy from realurl, written by Dmitry Dulepov <dmitry.dulepov@gmail.com>.
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	static public function array_merge_recursive_overrule($array1, $array2) {
		if (class_exists('\\TYPO3\\CMS\\Core\\Utility\\ArrayUtility')) {
			/** @noinspection PhpUndefinedClassInspection PhpUndefinedNamespaceInspection */
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($array1, $array2);
		}
		else {
			/** @noinspection PhpDeprecationInspection */
			$array1 = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($array1, $array2);
		}

		return $array1;
	}

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
	 *
	 * @deprecated because of its inefficiency
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
	 * Decrypt encrypted value with given control hash
	 * @see http://yavkata.co.uk/weblog/php/securing-html-hidden-input-fields-using-encryption-and-hashing/
	 *
	 * @access	public
	 *
	 * @param	string		$encrypted: The encrypted value to decrypt
	 * @param	string		$hash: The control hash for decrypting
	 *
	 * @return	mixed		The decrypted value or NULL on error
	 */
	public static function decrypt($encrypted, $hash) {

		$decrypted = NULL;

		// Check for PHP extension "mcrypt".
		if (!extension_loaded('mcrypt')) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->decrypt('.$encrypted.', '.$hash.')] PHP extension "mcrypt" not available', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		}

		if (empty($encrypted) || empty($hash)) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->decrypt('.$encrypted.', '.$hash.')] Invalid parameters given for decryption', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return;

		}

		if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->decrypt('.$encrypted.', '.$hash.')] No encryption key set in TYPO3 configuration', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return;

		}

		$iv = substr(md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']), 0, mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CFB));

		$decrypted = mcrypt_decrypt(MCRYPT_BLOWFISH, substr($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 0, 56), base64_decode($encrypted), MCRYPT_MODE_CFB, $iv);

		$salt = substr($hash, 0, 10);

		$hashed = $salt.substr(sha1($salt.$decrypted), -10);

		if ($hashed !== $hash) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->decrypt('.$encrypted.', '.$hash.')] Invalid hash "'.$hash.'" given for decryption', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		}

		return $decrypted;

	}

	/**
	 * Encrypt the given string
	 * @see http://yavkata.co.uk/weblog/php/securing-html-hidden-input-fields-using-encryption-and-hashing/
	 *
	 * @access	public
	 *
	 * @param	string		$string: The string to encrypt
	 *
	 * @return	array		Array with encrypted string and control hash
	 */
	public static function encrypt($string) {

		// Check for PHP extension "mcrypt".
		if (!extension_loaded('mcrypt')) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->encrypt('.$string.')] PHP extension "mcrypt" not available', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		}

		if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->encrypt('.$string.')] No encryption key set in TYPO3 configuration', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return;

		}

		$iv = substr(md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']), 0, mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CFB));

		$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, substr($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 0, 56), $string, MCRYPT_MODE_CFB, $iv));

		$salt = substr(md5(uniqid(rand(), TRUE)), 0, 10);

		$hash = $salt.substr(sha1($salt.$string), -10);

		return array ('encrypted' => $encrypted, 'hash' => $hash);

	}

	/**
	 * Get a backend user object (even in frontend mode)
	 *
	 * @access public
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication Instance of \TYPO3\CMS\Core\Authentication\BackendUserAuthentication or NULL on failure
	 */
	public static function getBeUser() {

		if (TYPO3_MODE === 'FE' || TYPO3_MODE === 'BE') {

			// Initialize backend session with CLI user's rights.
			$userObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');

			$userObj->dontSetCookie = TRUE;

			$userObj->start();

			$userObj->setBeUserByName('_cli_dlf');

			$userObj->backendCheckLogin();

			return $userObj;

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getBeUser()] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return;

		}

	}

	/**
	 * Get the current frontend user object
	 *
	 * @access public
	 *
	 * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication Instance of \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication or NULL on failure
	 */
	public static function getFeUser() {

		if (TYPO3_MODE === 'FE') {

			// Check if a user is currently logged in.
			if (!empty($GLOBALS['TSFE']->loginUser)) {

				return $GLOBALS['TSFE']->fe_user;

			} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('eID') !== NULL) {

				return \TYPO3\CMS\Frontend\Utility\EidUtility::initFeUser();

			}

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getFeUser()] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

		}

		return;

	}

	/**
	 * Get the registered hook objects for a class
	 *
	 * @access	public
	 *
	 * @param	string		$scriptRelPath: The path to the class file
	 *
	 * @return	array		Array of hook objects for the class
	 */
	public static function getHookObjects($scriptRelPath) {

		$hookObjects = array ();

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::$extKey.'/'.$scriptRelPath]['hookClass'])) {

			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::$extKey.'/'.$scriptRelPath]['hookClass'] as $classRef) {

				$hookObjects[] = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);

			}

		}

		return $hookObjects;

	}

	/**
	 * Get the "index_name" for an UID
	 *
	 * @access	public
	 *
	 * @param	integer		$uid: The UID of the record
	 * @param	string		$table: Get the "index_name" from this table
	 * @param	integer		$pid: Get the "index_name" from this page
	 *
	 * @return	string		"index_name" for the given UID
	 */
	public static function getIndexName($uid, $table, $pid = -1) {

		// Save parameters for logging purposes.
		$_uid = $uid;

		$_pid = $pid;

		// Sanitize input.
		$uid = max(intval($uid), 0);

		if (!$uid || !in_array($table, array ('tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures', 'tx_dlf_solrcores'))) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getIndexName('.$_uid.', '.$table.', '.$_pid.')] Invalid UID "'.$uid.'" or table "'.$table.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return '';

		}

		$where = '';

		// Should we check for a specific PID, too?
		if ($pid !== -1) {

			$pid = max(intval($pid), 0);

			$where = ' AND '.$table.'.pid='.$pid;

		}

		// Get index_name from database.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$table.'.index_name AS index_name',
			$table,
			$table.'.uid='.$uid.$where.self::whereClause($table),
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			return $resArray['index_name'];

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getIndexName('.$_uid.', '.$table.', '.$_pid.')] No "index_name" with UID "'.$uid.'" and PID "'.$pid.'" found in table "'.$table.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return '';

		}

	}

	/**
	 * Get the UID for a given "index_name"
	 *
	 * @access	public
	 *
	 * @param	integer		$index_name: The index_name of the record
	 * @param	string		$table: Get the "index_name" from this table
	 * @param	integer		$pid: Get the "index_name" from this page
	 *
	 * @return	string		"uid" for the given index_name
	 */
	public static function getIdFromIndexName($index_name, $table, $pid = -1) {

		// Save parameters for logging purposes.
		$_index_name = $index_name;

		$_pid = $pid;

		if (!$index_name || !in_array($table, array ('tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures', 'tx_dlf_solrcores'))) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getIdFromIndexName('.$_index_name.', '.$table.', '.$_pid.')] Invalid UID "'.$index_name.'" or table "'.$table.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return '';

		}

		$where = '';

		// Should we check for a specific PID, too?
		if ($pid !== -1) {

			$pid = max(intval($pid), 0);

			$where = ' AND '.$table.'.pid='.$pid;

		}

		// Get index_name from database.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$table.'.uid AS uid',
			$table,
			$table.'.index_name='.$index_name.$where.self::whereClause($table),
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			return $resArray['uid'];

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getIdFromIndexName('.$_index_name.', '.$table.', '.$_pid.')] No UID for given "index_name" "'.$index_name.'" and PID "'.$pid.'" found in table "'.$table.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return '';

		}

	}

	/**
	 * Get language name from ISO code
	 *
	 * @access	public
	 *
	 * @param	string		$code: ISO 639-1 or ISO 639-2/B language code
	 *
	 * @return	string		Localized full name of language or unchanged input
	 */
	public static function getLanguageName($code) {

		// Analyze code and set appropriate ISO table.
		$isoCode = strtolower(trim($code));

		if (preg_match('/^[a-z]{3}$/', $isoCode)) {

			$file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(self::$extKey).'lib/ISO-639/iso-639-2b.xml';

		} elseif (preg_match('/^[a-z]{2}$/', $isoCode)) {

			$file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(self::$extKey).'lib/ISO-639/iso-639-1.xml';

		} else {

			// No ISO code, return unchanged.
			return $code;

		}

		// Load ISO table and get localized full name of language.
		if (TYPO3_MODE === 'FE') {

			$iso639 = $GLOBALS['TSFE']->readLLfile($file);

			if (!empty($iso639['default'][$isoCode])) {

				$lang = $GLOBALS['TSFE']->getLLL($isoCode, $iso639);

			}

		} elseif (TYPO3_MODE === 'BE') {

			$iso639 = $GLOBALS['LANG']->includeLLFile($file, FALSE, TRUE);

			if (!empty($iso639['default'][$isoCode])) {

				$lang = $GLOBALS['LANG']->getLLL($isoCode, $iso639, FALSE);

			}

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getLanguageName('.$code.')] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return $code;

		}

		if (!empty($lang)) {

			return $lang;

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getLanguageName('.$code.')] Language code "'.$code.'" not found in ISO-639 table', self::$extKey, SYSLOG_SEVERITY_NOTICE);

			}

			return $code;

		}

	}

	/**
	 * Wrapper function for getting localizations in frontend and backend
	 *
	 * @param	string		$key: The locallang key to translate
	 * @param	boolean		$hsc: Should the result be htmlspecialchar()'ed?
	 * @param	string		$default: Default return value if no translation is available
	 *
	 * @return	string		The translated string or the given key on failure
	 */
	public static function getLL($key, $hsc = FALSE, $default = '') {

		// Set initial output to default value.
		$translated = (string) $default;

		// Load common locallang file.
		if (empty(self::$locallang)) {

			$file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(self::$extKey, 'common/locallang.xml');

			if (TYPO3_MODE === 'FE') {

				self::$locallang = $GLOBALS['TSFE']->readLLfile($file);

			} elseif (TYPO3_MODE === 'BE') {

				self::$locallang = $GLOBALS['LANG']->includeLLFile($file, FALSE, TRUE);

			} elseif (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getLL('.$key.', '.$default.', ['.($hsc ? 'TRUE' : 'FALSE').'])] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

		}

		// Get translation.
		if (!empty(self::$locallang['default'][$key])) {

			if (TYPO3_MODE === 'FE') {

				$translated = $GLOBALS['TSFE']->getLLL($key, self::$locallang);

			} elseif (TYPO3_MODE === 'BE') {

				$translated = $GLOBALS['LANG']->getLLL($key, self::$locallang, FALSE);

			} elseif (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getLL('.$key.', '.$default.', ['.($hsc ? 'TRUE' : 'FALSE').'])] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

		}

		// Escape HTML characters if applicable.
		if ($hsc) {

			$translated = htmlspecialchars($translated);

		}

		return $translated;

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

		$concordance = array (
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

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->getURN('.$base.', '.$id.')] Invalid chars in given parameters', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

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
	 * Load value from user's session.
	 *
	 * @access	public
	 *
	 * @param	string		$key: Session data key for retrieval
	 *
	 * @return	mixed		Session value for given key or NULL on failure
	 */
	public static function loadFromSession($key) {

		// Save parameter for logging purposes.
		$_key = $key;

		// Cast to string for security reasons.
		$key = (string) $key;

		if (!$key) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->loadFromSession('.$_key.')] Invalid key "'.$key.'" for session data retrieval', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		}

		// Get the session data.
		if (TYPO3_MODE === 'FE') {

			return $GLOBALS['TSFE']->fe_user->getKey('ses', $key);

		} elseif (TYPO3_MODE === 'BE') {

			return $GLOBALS['BE_USER']->getSessionData($key);

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->loadFromSession('.$_key.')] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return;

		}

	}

	/**
	 * Adds "t3jquery" extension's library to page header.
	 *
	 * @access	public
	 *
	 * @return	boolean		TRUE on success or FALSE on error
	 */
	public static function loadJQuery() {

		// Ensure extension "t3jquery" is available.
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('t3jquery')) {

			require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3jquery').'class.tx_t3jquery.php');

		}

		// Is "t3jquery" loaded?
		if (T3JQUERY === TRUE) {

			tx_t3jquery::addJqJS();

			return TRUE;

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->loadJQuery()] JQuery not available', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return FALSE;

		}

	}

	/**
	 * Process a data and/or command map with TYPO3 core engine.
	 *
	 * @access	public
	 *
	 * @param	array		$data: Data map
	 * @param	array		$cmd: Command map
	 * @param	boolean		$reverseOrder: Should the command map be processed first?
	 * @param	boolean		$be_user: Use current backend user's rights for processing?
	 *
	 * @return	array		Array of substituted "NEW..." identifiers and their actual UIDs.
	 */
	public static function processDB(array $data = array (), array $cmd = array (), $reverseOrder = FALSE, $be_user = FALSE) {

		// Instantiate TYPO3 core engine.
		$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');

		// Set some configuration variables.
		$tce->stripslashes_values = FALSE;

		// Get backend user for processing.
		if ($be_user && isset($GLOBALS['BE_USER'])) {

			$user = $GLOBALS['BE_USER'];

		} else {

			$user = self::getBeUser();

		}

		// Load data and command arrays.
		$tce->start($data, $cmd, $user);

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
	 * Process a data and/or command map with TYPO3 core engine as admin.
	 *
	 * @access	public
	 *
	 * @param	array		$data: Data map
	 * @param	array		$cmd: Command map
	 * @param	boolean		$reverseOrder: Should the command map be processed first?
	 *
	 * @return	array		Array of substituted "NEW..." identifiers and their actual UIDs.
	 */
	public static function processDBasAdmin(array $data = array (), array $cmd = array (), $reverseOrder = FALSE) {

		if (TYPO3_MODE === 'BE' && $GLOBALS['BE_USER']->isAdmin()) {

			return self::processDB($data, $cmd, $reverseOrder, TRUE);

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->processDBasAdmin([data->data], [data->cmd], ['.($reverseOrder ? 'TRUE' : 'FALSE').'])] Current backend user has no admin privileges', self::$extKey, SYSLOG_SEVERITY_ERROR, array ('data' => $data, 'cmd' => $cmd));

			}

			return array ();

		}

	}

	/**
	 * Save given value to user's session.
	 *
	 * @access	public
	 *
	 * @param	mixed		$value: Value to save
	 * @param	string		$key: Session data key for saving
	 *
	 * @return	boolean		TRUE on success, FALSE on failure
	 */
	public static function saveToSession($value, $key) {

		// Save parameter for logging purposes.
		$_key = $key;

		// Cast to string for security reasons.
		$key = (string) $key;

		if (!$key) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->saveToSession([data], '.$_key.')] Invalid key "'.$key.'" for session data saving', self::$extKey, SYSLOG_SEVERITY_WARNING, $value);

			}

			return FALSE;

		}

		// Save value in session data.
		if (TYPO3_MODE === 'FE') {

			$GLOBALS['TSFE']->fe_user->setKey('ses', $key, $value);

			$GLOBALS['TSFE']->fe_user->storeSessionData();

			return TRUE;

		} elseif (TYPO3_MODE === 'BE') {

			$GLOBALS['BE_USER']->setAndSaveSessionData($key, $value);

			return TRUE;

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->saveToSession([data], '.$_key.')] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR, $data);

			}

			return FALSE;

		}

	}

	/**
	 * This translates an internal "index_name"
	 *
	 * @access	public
	 *
	 * @param	string		$index_name: The internal "index_name" to translate
	 * @param	string		$table: Get the translation from this table
	 * @param	string		$pid: Get the translation from this page
	 *
	 * @return	string		Localized label for $index_name
	 */
	public static function translate($index_name, $table, $pid) {

		// Save parameters for logging purposes.
		$_index_name = $index_name;

		$_pid = $pid;

		// Load labels into static variable for future use.
		static $labels = array ();

		// Sanitize input.
		$pid = max(intval($pid), 0);

		if (!$pid) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->translate('.$_index_name.', '.$table.', '.$_pid.')] Invalid PID "'.$pid.'" for translation', self::$extKey, SYSLOG_SEVERITY_WARNING);

			}

			return $index_name;

		}

		// Check if "index_name" is an UID.
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($index_name)) {

			$index_name = self::getIndexName($index_name, $table, $pid);

		}

		/* The $labels already contain the translated content element, but with the index_name of the translated content element itself
		 * and not with the $index_name of the original that we receive here. So we have to determine the index_name of the
		 * associated translated content element. E.g. $labels['title0'] != $index_name = title. */

		// First fetch the uid of the received index_name
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				$table,
				'pid='.$pid.' AND index_name="'.$index_name.'"'.self::whereClause($table),
				'',
				'',
				''
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

			// Now we use the uid of the l18_parent to fetch the index_name of the translated content element.

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'index_name',
					$table,
					'pid='.$pid.' AND l18n_parent='.$resArray['uid'].' AND sys_language_uid='.intval($GLOBALS['TSFE']->sys_language_content).self::whereClause($table),
					'',
					'',
					''
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

				// If there is an translated content element, overwrite the received $index_name.

				$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

				$index_name = $resArray['index_name'];
			}
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
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$table,
					'pid='.$pid.$additionalWhere.self::whereClause($table),
					'',
					'',
					''
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

					while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

						// Overlay localized labels if available.
						if ($GLOBALS['TSFE']->sys_language_content > 0) {

							$resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay($table, $resArray, $GLOBALS['TSFE']->sys_language_content, ($GLOBALS['TSFE']->sys_language_mode == 'strict' ? 'hideNonTranslated' : ''));

						}

						if ($resArray) {

							$labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$resArray['index_name']] = $resArray['label'];

						}

					}

				} else {

					if (TYPO3_DLOG) {

						\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->translate('.$_index_name.', '.$table.', '.$_pid.')] No translation with PID "'.$pid.'" available in table "'.$table.'" or translation not accessible', self::extKey, SYSLOG_SEVERITY_NOTICE);

					}

				}

			} else {

				if (TYPO3_DLOG) {

					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->translate('.$_index_name.', '.$table.', '.$_pid.')] No translations available for table "'.$table.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

				}

			}

		}

		if (!empty($labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name])) {

			return $labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name];

		} else {

			return $index_name;

		}

	}

	/**
	 * This returns the additional WHERE clause of a table based on its TCA configuration
	 *
	 * @access	public
	 *
	 * @param	string		$table: Table name as defined in TCA
	 * @param	boolean		$showHidden: Ignore the hidden flag?
	 *
	 * @return	string		Additional WHERE clause
	 */
	public static function whereClause($table, $showHidden = FALSE) {

		if (TYPO3_MODE === 'FE') {

			// Table "tx_dlf_formats" always has PID 0.
			if ($table == 'tx_dlf_formats') {

				return \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);

			}

			// Should we ignore the record's hidden flag?
			$ignoreHide = -1;

			if ($showHidden) {

				$ignoreHide = 1;

			}

			// $GLOBALS['TSFE']->sys_page is not always available in frontend.
			if (is_object($GLOBALS['TSFE']->sys_page)) {

				return $GLOBALS['TSFE']->sys_page->enableFields($table, $ignoreHide);

			} else {

				$pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');

				$GLOBALS['TSFE']->includeTCA();

				return $pageRepository->enableFields($table, $ignoreHide);

			}

		} elseif (TYPO3_MODE === 'BE') {

			return \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_helper->whereClause('.$table.', ['.($showHidden ? 'TRUE' : 'FALSE').'])] Unexpected TYPO3_MODE "'.TYPO3_MODE.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

			}

			return ' AND 1=-1';

		}

	}

	/**
	 * @param FlashMessage $message
	 * @return void
	 */
	public static function addMessage($message) {
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		$flashMessageService->getMessageQueueByIdentifier()->enqueue($message);
	}

	/**
	 * Fetches and renders all available flash messages from the queue.
	 *
	 * @return string All flash messages in the queue rendered as HTML.
	 */
	public static function renderFlashMessages() {
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		if (version_compare(TYPO3_branch, '7.4', '<')) {
			// For TYPO3 6.2 - 7.3, we can use the existing method.
			$content = $flashMessageService->getMessageQueueByIdentifier()->renderFlashMessages();
		} else {
			// Since TYPO3 7.4.0, \TYPO3\CMS\Core\Messaging\FlashMessageQueue::renderFlashMessages
			// uses htmlspecialchars on all texts, but we have message text with HTML tags.
			// Therefore we copy the implementation from 7.4.0, but remove the htmlspecialchars call.
			$content = '';
			$flashMessages = $flashMessageService->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
			if (!empty($flashMessages)) {
				$content = '<ul class="typo3-messages">';
				foreach ($flashMessages as $flashMessage) {
					$severityClass = sprintf('alert %s', $flashMessage->getClass());
					//~ $messageContent = htmlspecialchars($flashMessage->getMessage());
					$messageContent = $flashMessage->getMessage();
					if ($flashMessage->getTitle() !== '') {
						$messageContent = sprintf('<h4>%s</h4>', htmlspecialchars($flashMessage->getTitle())) . $messageContent;
					}
					$content .= sprintf('<li class="%s">%s</li>', htmlspecialchars($severityClass), $messageContent);
				}
				$content .= '</ul>';
			}
		}
		return $content;
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
