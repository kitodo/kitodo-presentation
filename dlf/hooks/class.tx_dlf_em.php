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
 * Hooks and helper for the extension manager.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_em {

	/**
	 * Check if a connection to a Solr server could be established with the given credentials.
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	t3lib_tsStyleConfig		&$pObj: The parent object
	 *
	 * @return	string		Message informing the user of success or failure
	 */
	public function checkSolrConnection(&$params, &$pObj) {

		// Get Solr credentials.
		$conf = t3lib_div::_POST('data');

		if (empty($conf)) {

			$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

		}

		// Prepend username and password to hostname.
		if ($conf['solrUser'] && $conf['solrPass']) {

			$host = $conf['solrUser'].':'.$conf['solrPass'].'@'.($conf['solrHost'] ? $conf['solrHost'] : 'localhost');

		} else {

			$host = ($conf['solrHost'] ? $conf['solrHost'] : 'localhost');

		}

		// Set port if not set.
		$port = (intval($conf['solrPort']) > 0 ? intval($conf['solrPort']) : 8180);

		// Trim path and append trailing slash.
		$path = (trim($conf['solrPath'], '/') ? trim($conf['solrPath'], '/').'/' : '');

		// Build request URI.
		$url = 'http://'.$host.':'.$port.'/'.$path.'admin/cores';

		$context = stream_context_create(array (
			'http' => array (
				'method' => 'GET',
				'user_agent' => ($conf['useragent'] ? $conf['useragent'] : ini_get('user_agent'))
			)
		));

		// Try to connect to Solr server.
		$response = @simplexml_load_string(file_get_contents($url, FALSE, $context));

		// Check status code.
		if ($response) {

			$status = $response->xpath('//lst[@name="responseHeader"]/int[@name="status"]');

			if (is_array($status)) {

				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					'The status code returned by Apache Solr is <strong>'.htmlspecialchars((string) $status[0]).'</strong>.',
					'Connection established!',
					($status[0] == 0 ? t3lib_FlashMessage::OK : t3lib_FlashMessage::WARNING),
					FALSE
				);

				return $message->render();

			}

		}

		$message = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			'Apache Solr was not reachable with the given details.',
			'Connection failed!',
			t3lib_FlashMessage::ERROR,
			FALSE
		);

		return $message->render();

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_em.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_em.php']);
}

?>