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
	 * This holds the output ready to return
	 *
	 * @var	string
	 * @access protected
	 */
	protected $content = '';

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

		// Load localization file.
		$GLOBALS['LANG']->includeLLFile('EXT:dlf/locallang.xml');

		// Get Solr credentials.
		$conf = array_merge((array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']), (array) t3lib_div::_POST('data'));

		// Prepend username and password to hostname.
		if (!empty($conf['solrUser']) && !empty($conf['solrPass'])) {

			$host = $conf['solrUser'].':'.$conf['solrPass'].'@'.($conf['solrHost'] ? $conf['solrHost'] : 'localhost');

		} else {

			$host = (!empty($conf['solrHost']) ? $conf['solrHost'] : 'localhost');

		}

		// Set port if not set.
		$port = (!empty($conf['solrPort']) ? t3lib_div::intInRange($conf['solrPort'], 0, 65535, 8180) : 8180);

		// Trim path and append trailing slash.
		$path = (!empty($conf['solrPath']) ? trim($conf['solrPath'], '/').'/' : '');

		// Build request URI.
		$url = 'http://'.$host.':'.$port.'/'.$path.'admin/cores';

		$context = stream_context_create(array (
			'http' => array (
				'method' => 'GET',
				'user_agent' => (!empty($conf['useragent']) ? $conf['useragent'] : ini_get('user_agent'))
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
					sprintf($GLOBALS['LANG']->getLL('solr.status'), (string) $status[0]),
					$GLOBALS['LANG']->getLL('solr.connected'),
					($status[0] == 0 ? t3lib_FlashMessage::OK : t3lib_FlashMessage::WARNING),
					FALSE
				);

				$this->content .= $message->render();

				return $this->content;

			}

		}

		$message = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			sprintf($GLOBALS['LANG']->getLL('solr.error'), $url),
			$GLOBALS['LANG']->getLL('solr.notConnected'),
			t3lib_FlashMessage::WARNING,
			FALSE
		);

		$this->content .= $message->render();

		return $this->content;

	}

	/**
	 * Make sure a backend user exists and is configured properly.
	 *
	 * @access	protected
	 *
	 * @param	boolean		$checkOnly: Just check the user or change it, too?
	 * @param	integer		$groupUid: UID of the corresponding usergroup
	 *
	 * @return	integer		UID of user or 0 if something is wrong
	 */
	protected function checkCliUser($checkOnly, $groupUid) {

		// Set default return value.
		$usrUid = 0;

		// Check if user "_cli_dlf" exists, is no admin and is not disabled.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,admin,usergroup',
			'be_users',
			'username='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_users').t3lib_BEfunc::deleteClause('be_users')
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			// Explode comma-separated list.
			$resArray['usergroup'] = explode(',', $resArray['usergroup']);

			// Check if user is not disabled.
			$result2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'1',
				'be_users',
				'uid='.intval($resArray['uid']).t3lib_BEfunc::BEenableFields('be_users')
			);

			// Check if user is configured properly.
			if (count(array_diff(array ($groupUid), $resArray['usergroup'])) == 0
					&& !$resArray['admin']
					&& $GLOBALS['TYPO3_DB']->sql_num_rows($result2) > 0) {

				$usrUid = $resArray['uid'];

				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.usrOkayMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.usrOkay'),
					t3lib_FlashMessage::OK,
					FALSE
				);

			} else {

				if (!$checkOnly && $groupUid) {

					// Keep exisiting values and add the new ones.
					$usergroup = array_unique(array_merge(array ($groupUid), $resArray['usergroup']));

					// Try to configure user.
					$data['be_users'][$resArray['uid']] = array (
						'admin' => 0,
						'usergroup' => implode(',', $usergroup),
						$GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['disabled'] => 0,
						$GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['starttime'] => 0,
						$GLOBALS['TCA']['be_users']['ctrl']['enablecolumns']['endtime'] => 0
					);

					tx_dlf_helper::processDB($data);

					// Check if configuration was successful.
					if ($this->checkCliUser(TRUE, $groupUid)) {

						$usrUid = $resArray['uid'];

						$message = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.usrConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.usrConfigured'),
							t3lib_FlashMessage::INFO,
							FALSE
						);

					} else {

						$message = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfigured'),
							t3lib_FlashMessage::ERROR,
							FALSE
						);

					}

				} else {

					$message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfiguredMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfigured'),
						t3lib_FlashMessage::ERROR,
						FALSE
					);

				}

			}

		} else {

			if (!$checkOnly && $groupUid) {

				// Try to create user.
				$tempUid = uniqid('NEW');

				$data['be_users'][$tempUid] = array (
					'pid' => 0,
					'username' => '_cli_dlf',
					'password' => md5($tempUid),
					'realName' => $GLOBALS['LANG']->getLL('cliUserGroup.usrRealName'),
					'usergroup' => intval($groupUid)
				);

				$substUid = tx_dlf_helper::processDB($data);

				// Check if creation was successful.
				if (!empty($substUid[$tempUid])) {

					$usrUid = $substUid[$tempUid];

					$message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.usrCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.usrCreated'),
						t3lib_FlashMessage::INFO,
						FALSE
					);

				} else {

					$message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreated'),
						t3lib_FlashMessage::ERROR,
						FALSE
					);

				}

			} else {

				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreatedMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreated'),
					t3lib_FlashMessage::ERROR,
					FALSE
				);

			}

		}

		$this->content = $message->render();

		return $usrUid;

	}

	/**
	 * Make sure a backend usergroup exists and is configured properly.
	 *
	 * @access	protected
	 *
	 * @param	boolean		$checkOnly: Just check the usergroup or change it, too?
	 * @param	array		$settings: Array with default settings
	 *
	 * @return	integer		UID of usergroup or 0 if something is wrong
	 */
	protected function checkCliGroup($checkOnly, $settings = array ()) {

		// Set default return value.
		$grpUid = 0;

		// Set default configuration for usergroup.
		if (empty($settings)) {

			$settings = array (
				'non_exclude_fields' => array (),
				'tables_select' => array (
					'tx_dlf_documents',
					'tx_dlf_collections',
					'tx_dlf_libraries',
					'tx_dlf_structures',
					'tx_dlf_metadata',
					'tx_dlf_formats',
					'tx_dlf_solrcores'
				),
				'tables_modify' => array (
					'tx_dlf_documents',
					'tx_dlf_collections',
					'tx_dlf_libraries'
				)
			);

			// Set allowed exclude fields.
			foreach ($settings['tables_modify'] as $table) {

				t3lib_div::loadTCA($table);

				foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldConf) {

					if (!empty($fieldConf['exclude'])) {

						$settings['non_exclude_fields'][] = $table.':'.$field;

					}

				}

			}

		}

		// Check if group "_cli_dlf" exists and is not disabled.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,non_exclude_fields,tables_select,tables_modify,inc_access_lists,'.$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'],
			'be_groups',
			'title='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups').t3lib_BEfunc::deleteClause('be_groups')
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			// Explode comma-separated lists.
			$resArray['non_exclude_fields'] = explode(',', $resArray['non_exclude_fields']);

			$resArray['tables_select'] = explode(',', $resArray['tables_select']);

			$resArray['tables_modify'] = explode(',', $resArray['tables_modify']);

			// Check if usergroup is configured properly.
			if (count(array_diff($settings['non_exclude_fields'], $resArray['non_exclude_fields'])) == 0
					&& count(array_diff($settings['tables_select'], $resArray['tables_select'])) == 0
					&& count(array_diff($settings['tables_modify'], $resArray['tables_modify'])) == 0
					&& $resArray['inc_access_lists'] == 1
					&& $resArray[$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled']] == 0) {

				$grpUid = $resArray['uid'];

				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.grpOkayMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.grpOkay'),
					t3lib_FlashMessage::OK,
					FALSE
				);

			} else {

				if (!$checkOnly) {

					// Keep exisiting values and add the new ones.
					$non_exclude_fields = array_unique(array_merge($settings['non_exclude_fields'], $resArray['non_exclude_fields']));

					$tables_select = array_unique(array_merge($settings['tables_select'], $resArray['tables_select']));

					$tables_modify = array_unique(array_merge($settings['tables_modify'], $resArray['tables_modify']));

					// Try to configure usergroup.
					$data['be_groups'][$resArray['uid']] = array (
						'non_exclude_fields' => implode(',', $non_exclude_fields),
						'tables_select' => implode(',', $tables_select),
						'tables_modify' => implode(',', $tables_modify),
						'inc_access_lists' => 1,
						$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'] => 0
					);

					tx_dlf_helper::processDB($data);

					// Check if configuration was successful.
					if ($this->checkCliGroup(TRUE, $settings)) {

						$grpUid = $resArray['uid'];

						$message = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.grpConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.grpConfigured'),
							t3lib_FlashMessage::INFO,
							FALSE
						);

					} else {

						$message = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfigured'),
							t3lib_FlashMessage::ERROR,
							FALSE
						);

					}

				} else {

					$message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfiguredMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfigured'),
						t3lib_FlashMessage::ERROR,
						FALSE
					);

				}

			}

		} else {

			if (!$checkOnly) {

				// Try to create usergroup.
				$tempUid = uniqid('NEW');

				$data['be_groups'][$tempUid] = array (
					'pid' => 0,
					'title' => '_cli_dlf',
					'description' => $GLOBALS['LANG']->getLL('cliUserGroup.grpDescription'),
					'non_exclude_fields' => implode(',', $settings['non_exclude_fields']),
					'tables_select' => implode(',', $settings['tables_select']),
					'tables_modify' => implode(',', $settings['tables_modify']),
					'inc_access_lists' => 1
				);

				$substUid = tx_dlf_helper::processDB($data);

				// Check if creation was successful.
				if (!empty($substUid[$tempUid])) {

					$grpUid = $substUid[$tempUid];

					$message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.grpCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.grpCreated'),
						t3lib_FlashMessage::INFO,
						FALSE
					);

				} else {

					$message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreated'),
						t3lib_FlashMessage::ERROR,
						FALSE
					);

				}

			} else {

				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreatedMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreated'),
					t3lib_FlashMessage::ERROR,
					FALSE
				);

			}

		}

		$this->content = $message->render();

		return $grpUid;

	}

	/**
	 * Make sure a CLI user and group exist.
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	t3lib_tsStyleConfig		&$pObj: The parent object
	 *
	 * @return	string		Message informing the user of success or failure
	 */
	public function checkCliUserGroup(&$params, &$pObj) {

		// Load localization file.
		$GLOBALS['LANG']->includeLLFile('EXT:dlf/locallang.xml');

		// Get current configuration.
		$conf = array_merge((array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']), (array) t3lib_div::_POST('data'));

		// Check if usergroup "_cli_dlf" exists and is configured properly.
		$groupUid = $this->checkCliGroup(empty($conf['makeCliUserGroup']));

		// Save output because it will be overwritten by the user check method.
		$content = $this->content;

		// Check if user "_cli_dlf" exists and is configured properly.
		$userUid = $this->checkCliUser(empty($conf['makeCliUserGroup']), $groupUid);

		// Merge output from usergroup and user checks.
		$this->content .= $content;

		// Check if CLI dispatcher is executable.
		if (is_executable(PATH_typo3.'cli_dispatch.phpsh')) {

			$message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('cliUserGroup.cliOkayMsg'),
				$GLOBALS['LANG']->getLL('cliUserGroup.cliOkay'),
				t3lib_FlashMessage::OK,
				FALSE
			);

		} else {

			$message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('cliUserGroup.cliNotOkayMsg'),
				$GLOBALS['LANG']->getLL('cliUserGroup.cliNotOkay'),
				t3lib_FlashMessage::ERROR,
				FALSE
			);

		}

		$this->content .= $message->render();

		return $this->content;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_em.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_em.php']);
}

?>