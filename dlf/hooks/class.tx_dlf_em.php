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
 * Hooks and helper for the extension manager.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_em {

	/**
	 * This holds the current configuration
	 *
	 * @var	array
	 * @access protected
	 */
	protected $conf = array ();

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
	 * @param	\TYPO3\CMS\Core\TypoScript\ConfigurationForm &$pObj: The parent object
	 *
	 * @return	string		Message informing the user of success or failure
	 */
	public function checkSolrConnection(&$params, &$pObj) {

		// Prepend username and password to hostname.
		if (!empty($this->conf['solrUser']) && !empty($this->conf['solrPass'])) {

			$host = $this->conf['solrUser'].':'.$this->conf['solrPass'].'@'.(!empty($this->conf['solrHost']) ? $this->conf['solrHost'] : 'localhost');

		} else {

			$host = (!empty($this->conf['solrHost']) ? $this->conf['solrHost'] : 'localhost');

		}

		// Set port if not set.
		$port = (!empty($this->conf['solrPort']) ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->conf['solrPort'], 0, 65535, 8180) : 8180);

		// Trim path and append trailing slash.
		$path = (!empty($this->conf['solrPath']) ? trim($this->conf['solrPath'], '/').'/' : '');

		// Build request URI.
		$url = 'http://'.$host.':'.$port.'/'.$path.'admin/cores';

		$context = stream_context_create(array (
			'http' => array (
				'method' => 'GET',
				'user_agent' => (!empty($this->conf['useragent']) ? $this->conf['useragent'] : ini_get('user_agent'))
			)
		));

		// Try to connect to Solr server.
		$response = @simplexml_load_string(file_get_contents($url, FALSE, $context));

		// Check status code.
		if ($response) {

			$status = $response->xpath('//lst[@name="responseHeader"]/int[@name="status"]');

			if (is_array($status)) {

				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					sprintf($GLOBALS['LANG']->getLL('solr.status'), (string) $status[0]),
					$GLOBALS['LANG']->getLL('solr.connected'),
					($status[0] == 0 ? \TYPO3\CMS\Core\Messaging\FlashMessage::OK : \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING),
					FALSE
				);

				$this->content .= $message->render();

				return $this->content;

			}

		}

		$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			sprintf($GLOBALS['LANG']->getLL('solr.error'), $url),
			$GLOBALS['LANG']->getLL('solr.notConnected'),
			\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
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
			'username='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_users').\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_users')
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			// Explode comma-separated list.
			$resArray['usergroup'] = explode(',', $resArray['usergroup']);

			// Check if user is not disabled.
			$result2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'1',
				'be_users',
				'uid='.intval($resArray['uid']).\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('be_users')
			);

			// Check if user is configured properly.
			if (count(array_diff(array ($groupUid), $resArray['usergroup'])) == 0
					&& !$resArray['admin']
					&& $GLOBALS['TYPO3_DB']->sql_num_rows($result2) > 0) {

				$usrUid = $resArray['uid'];

				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.usrOkayMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.usrOkay'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::OK,
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

					tx_dlf_helper::processDBasAdmin($data);

					// Check if configuration was successful.
					if ($this->checkCliUser(TRUE, $groupUid)) {

						$usrUid = $resArray['uid'];

						$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
							'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.usrConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.usrConfigured'),
							\TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
							FALSE
						);

					} else {

						$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
							'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfigured'),
							\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
							FALSE
						);

					}

				} else {

					$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfiguredMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotConfigured'),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
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

				$substUid = tx_dlf_helper::processDBasAdmin($data);

				// Check if creation was successful.
				if (!empty($substUid[$tempUid])) {

					$usrUid = $substUid[$tempUid];

					$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.usrCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.usrCreated'),
						\TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
						FALSE
					);

				} else {

					$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreated'),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
						FALSE
					);

				}

			} else {

				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreatedMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.usrNotCreated'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
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
					'tx_dlf_metadataformat',
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

				foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldConf) {

					if (!empty($fieldConf['exclude'])) {

						$settings['non_exclude_fields'][] = $table.':'.$field;

					}

				}

			}

		}

		// Check if group "_cli_dlf" exists and is not disabled.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,non_exclude_fields,tables_select,tables_modify,' .
				$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'],
			'be_groups',
			'title=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups') .
				\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_groups')
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
					&& $resArray[$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled']] == 0) {

				$grpUid = $resArray['uid'];

				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.grpOkayMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.grpOkay'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::OK,
					FALSE
				);

			} else {

				if (!$checkOnly) {

					// Keep exisiting values and add the new ones.
					$non_exclude_fields = array_unique(array_merge($settings['non_exclude_fields'], $resArray['non_exclude_fields']));

					$tables_select = array_unique(array_merge($settings['tables_select'], $resArray['tables_select']));

					$tables_modify = array_unique(array_merge($settings['tables_modify'], $resArray['tables_modify']));

					// Try to configure usergroup.
					$data['be_groups'][$resArray['uid']] = array(
						'non_exclude_fields' => implode(',', $non_exclude_fields),
						'tables_select' => implode(',', $tables_select),
						'tables_modify' => implode(',', $tables_modify),
						$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'] => 0
					);

					tx_dlf_helper::processDBasAdmin($data);

					// Check if configuration was successful.
					if ($this->checkCliGroup(TRUE, $settings)) {

						$grpUid = $resArray['uid'];

						$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
							'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.grpConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.grpConfigured'),
							\TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
							FALSE
						);

					} else {

						$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
							'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
							$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfiguredMsg'),
							$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfigured'),
							\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
							FALSE
						);

					}

				} else {

					$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfiguredMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotConfigured'),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
						FALSE
					);

				}

			}

		} else {

			if (!$checkOnly) {

				// Try to create usergroup.
				$tempUid = uniqid('NEW');

				$data['be_groups'][$tempUid] = array(
					'pid' => 0,
					'title' => '_cli_dlf',
					'description' => $GLOBALS['LANG']->getLL('cliUserGroup.grpDescription'),
					'non_exclude_fields' => implode(',', $settings['non_exclude_fields']),
					'tables_select' => implode(',', $settings['tables_select']),
					'tables_modify' => implode(',', $settings['tables_modify'])
				);

				$substUid = tx_dlf_helper::processDBasAdmin($data);

				// Check if creation was successful.
				if (!empty($substUid[$tempUid])) {

					$grpUid = $substUid[$tempUid];

					$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.grpCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.grpCreated'),
						\TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
						FALSE
					);

				} else {

					$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreatedMsg'),
						$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreated'),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
						FALSE
					);

				}

			} else {

				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreatedMsg'),
					$GLOBALS['LANG']->getLL('cliUserGroup.grpNotCreated'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
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
	 * @param	\TYPO3\CMS\Core\TypoScript\ConfigurationForm &$pObj: The parent object
	 *
	 * @return	string		Message informing the user of success or failure
	 */
	public function checkCliUserGroup(&$params, &$pObj) {

		// Check if usergroup "_cli_dlf" exists and is configured properly.
		$groupUid = $this->checkCliGroup(empty($this->conf['makeCliUserGroup']));

		// Save output because it will be overwritten by the user check method.
		$content = $this->content;

		// Check if user "_cli_dlf" exists and is configured properly.
		$userUid = $this->checkCliUser(empty($this->conf['makeCliUserGroup']), $groupUid);

		// Merge output from usergroup and user checks.
		$this->content .= $content;

		// Check if CLI dispatcher is executable.
		if (is_executable(PATH_typo3.'cli_dispatch.phpsh')) {

			$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('cliUserGroup.cliOkayMsg'),
				$GLOBALS['LANG']->getLL('cliUserGroup.cliOkay'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::OK,
				FALSE
			);

		} else {

			$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('cliUserGroup.cliNotOkayMsg'),
				$GLOBALS['LANG']->getLL('cliUserGroup.cliNotOkay'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
				FALSE
			);

		}

		$this->content .= $message->render();

		return $this->content;

	}

	/**
	 * Make sure the essential namespaces are defined.
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Core\TypoScript\ConfigurationForm &$pObj: The parent object
	 *
	 * @return	string		Message informing the user of success or failure
	 */
	public function checkMetadataFormats(&$params, &$pObj) {

		$nsDefined = array (
			'MODS' => FALSE,
			'TEIHDR' => FALSE
		);

		// Check if formats "MODS" and "TEIHDR" exist.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'type',
			'tx_dlf_formats',
			'(type='.$GLOBALS['TYPO3_DB']->fullQuoteStr('MODS', 'tx_dlf_formats').' OR type='.$GLOBALS['TYPO3_DB']->fullQuoteStr('TEIHDR', 'tx_dlf_formats').')'.tx_dlf_helper::whereClause('tx_dlf_formats')
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			$nsDefined[$resArray['type']] = TRUE;

		}

		// Build data array.
		$data = array ();

		// Add MODS namespace.
		if (!$nsDefined['MODS']) {

			$data['tx_dlf_formats'][uniqid('NEW')] = array (
				'pid' => 0,
				'type' => 'MODS',
				'root' => 'mods',
				'namespace' => 'http://www.loc.gov/mods/v3',
				'class' => 'tx_dlf_mods'
			);

		}

		// Add TEIHDR namespace.
		if (!$nsDefined['TEIHDR']) {

			$data['tx_dlf_formats'][uniqid('NEW')] = array (
				'pid' => 0,
				'type' => 'TEIHDR',
				'root' => 'teiHeader',
				'namespace' => 'http://www.tei-c.org/ns/1.0',
				'class' => 'tx_dlf_teihdr'
			);

		}

		if (!empty($data)) {

			// Process changes.
			$substUid = tx_dlf_helper::processDBasAdmin($data);

			if (!empty($substUid)) {

				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					$GLOBALS['LANG']->getLL('metadataFormats.nsCreatedMsg'),
					$GLOBALS['LANG']->getLL('metadataFormats.nsCreated'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
					FALSE
				);

			} else {

				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					$GLOBALS['LANG']->getLL('metadataFormats.nsNotCreatedMsg'),
					$GLOBALS['LANG']->getLL('metadataFormats.nsNotCreated'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
					FALSE
				);

			}

		} else {

			$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('metadataFormats.nsOkayMsg'),
				$GLOBALS['LANG']->getLL('metadataFormats.nsOkay'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::OK,
				FALSE
			);

		}

		$this->content .= $message->render();

		return $this->content;

	}

	/**
	 * This is the constructor.
	 *
	 * @access	public
	 *
	 * @return	void
	 */
	public function __construct() {

		// Load localization file.
		$GLOBALS['LANG']->includeLLFile('EXT:dlf/locallang.xml');

		// Get current configuration.
		$this->conf = array_merge((array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']), (array) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('data'));

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_em.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_em.php']);
}
