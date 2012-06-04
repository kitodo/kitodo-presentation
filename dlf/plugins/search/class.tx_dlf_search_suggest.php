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
 * Search suggestion Ajax backend for the Plugin 'DLF: Search' of the
 * 'dlf' extension. This class is invoked using the eID bypass.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
require_once (PATH_tslib . 'class.tslib_pibase.php');
class tx_dlf_search_suggest extends tslib_pibase {

	private $content;

	private static $ENCRYPTION_KEY = 'b8b311560d3e6f8dea0aa445995b1b2b';
	
	private static $ENCRYPTION_IV = '381416de30a5c970f8f486aa6d5cc932';

	public $scriptRelPath = 'plugins/search/class.tx_dlf_search_suggest.php';

	public static function encrypt($original_value) {
		
		if (!extension_loaded('mcrypt')) {
		
			trigger_error('Mycrpt PHP extension not installed, falling back to TSFE instantiation for '.get_class(), E_USER_NOTICE);
			
			return NULL;
		
		}
		
		$iv = substr( self::$ENCRYPTION_IV, 0, mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CFB) );
		
		$encryptedValue = base64_encode( mcrypt_encrypt(MCRYPT_BLOWFISH, self::$ENCRYPTION_KEY, $original_value, MCRYPT_MODE_CFB, $iv) );
		
		$salt = substr( md5(uniqid(rand(), true)), 0, 10 );
		
		$hash = $salt . substr(sha1($salt . $original_value),-10);
		
		return array( 'value' => $encryptedValue, 'hash' => $hash);

	}

	public static function decrypt($encrypted, $hash) {
		
		if (!extension_loaded('mcrypt')) {
			
			trigger_error('Mycrpt PHP extension not installed, falling back to TSFE instantiation for '.get_class(), E_USER_NOTICE);
			
			return NULL;
		
		}
		
		if (empty($encrypted) || empty($hash)) {

			return NULL;

		}
		
		$result = NULL;

		$iv = substr(self::$ENCRYPTION_IV, 0, mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CFB));
		
		$decrypted_value = mcrypt_decrypt(MCRYPT_BLOWFISH, self::$ENCRYPTION_KEY, base64_decode($encrypted), MCRYPT_MODE_CFB, $iv);

		$salt = substr($hash, 0, 10);

		$new_hashed_value = $salt . substr(sha1($salt . $decrypted_value), -10);

		if ($new_hashed_value == $hash) {

			$result = $decrypted_value;

		}

		return $result;

	}

	public function main() {
		
		$core = self::decrypt(t3lib_div::_POST('encrypted'), t3lib_div::_POST('hashed'));
		
		// Mcrypt is not available, solr core did not arrive or was manipulated: give uo here.
		if (empty($core)) {

			return;

		}

		$url = trim(tx_dlf_solr::getSolrUrl($core), '/') . '/suggest/?q=' . t3lib_div::_POST('q');

		if ($stream = fopen($url, 'r')) {
			
			$this -> content .= stream_get_contents($stream);
			
			fclose($stream);
		
		} else {

			$this -> content .= "Could not connect to index server.";

		}
	}

	public function printContent() {
		echo $this -> content;
	}

}

$suggest = t3lib_div::makeInstance('tx_dlf_search_suggest');
$suggest->main();
$suggest->printContent();

?>