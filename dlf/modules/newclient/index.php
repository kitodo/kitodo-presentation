<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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
 * Module 'newclient' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2012, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_modNewclient extends tx_dlf_module {

	protected $modPath = 'newclient/';

	protected $buttonArray = array (
		'SHORTCUT' => '',
	);

	protected $markerArray = array (
		'CSH' => '',
		'MOD_MENU' => '',
		'CONTENT' => '',
	);

	/**
	 * Add access rights
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function cmdAddAccessRights() {

		// Get command line indexer's usergroup.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,db_mountpoints',
				'be_groups',
				'title='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups').' AND '.$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'].'=0'.t3lib_BEfunc::deleteClause('be_groups')
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			// Add current page to mountpoints.
			if (!t3lib_div::inList($resArray['db_mountpoints'], $this->id)) {

				$data['be_groups'][$resArray['uid']]['db_mountpoints'] = $resArray['db_mountpoints'].','.$this->id;

				tx_dlf_helper::processDB($data);

				// Fine.
				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('flash.usergroupAddedMsg'),
					$GLOBALS['LANG']->getLL('flash.usergroupAdded', TRUE),
					t3lib_FlashMessage::OK,
					FALSE
				);

				t3lib_FlashMessageQueue::addMessage($_message);

			}

		}

	}

	/**
	 * Add metadata configuration
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function cmdAddMetadata() {

		// Include metadata definition file.
		include_once(t3lib_extMgm::extPath($this->extKey).'modules/'.$this->modPath.'metadata.inc.php');

		// Load table configuration array to get default field values.
		t3lib_div::loadTCA('tx_dlf_metadata');

		// Build data array.
		foreach ($metadata as $index_name => $values) {

			$data['tx_dlf_metadata'][uniqid('NEW')] = array (
				'pid' => intval($this->id),
				'label' => $GLOBALS['LANG']->getLL($index_name),
				'index_name' => $index_name,
				'encoded' => $values['encoded'],
				'xpath' => $values['xpath'],
				'xpath_sorting' => $values['xpath_sorting'],
				'default_value' => $values['default_value'],
				'wrap' => (!empty($values['wrap']) ? $values['wrap'] : $GLOBALS['TCA']['tx_dlf_metadata']['columns']['wrap']['config']['default']),
				'tokenized' => $values['tokenized'],
				'stored' => $values['stored'],
				'indexed' => $values['indexed'],
				'boost' => $values['boost'],
				'is_sortable' => $values['is_sortable'],
				'is_facet' => $values['is_facet'],
				'is_listed' => $values['is_listed'],
			);

		}

		$_ids = tx_dlf_helper::processDB($data);

		// Check for failed inserts.
		if (count($_ids) == count($metadata)) {

			// Fine.
			$_message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('flash.metadataAddedMsg'),
				$GLOBALS['LANG']->getLL('flash.metadataAdded', TRUE),
				t3lib_FlashMessage::OK,
				FALSE
			);

		} else {

			// Something went wrong.
			$_message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('flash.metadataNotAddedMsg'),
				$GLOBALS['LANG']->getLL('flash.metadataNotAdded', TRUE),
				t3lib_FlashMessage::ERROR,
				FALSE
			);

		}

		t3lib_FlashMessageQueue::addMessage($_message);

	}

	/**
	 * Add Solr core
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function cmdAddSolrCore() {

		// Build data array.
		$data['tx_dlf_solrcores'][uniqid('NEW')] = array (
			'pid' => intval($this->id),
			'label' => $GLOBALS['LANG']->getLL('solrcore').' (PID '.$this->id.')',
			'index_name' => '',
		);

		$_ids = tx_dlf_helper::processDB($data);

		// Check for failed inserts.
		if (count($_ids) == 1) {

			// Fine.
			$_message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('flash.solrcoreAddedMsg'),
				$GLOBALS['LANG']->getLL('flash.solrcoreAdded', TRUE),
				t3lib_FlashMessage::OK,
				FALSE
			);

		} else {

			// Something went wrong.
			$_message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('flash.solrcoreNotAddedMsg'),
				$GLOBALS['LANG']->getLL('flash.solrcoreNotAdded', TRUE),
				t3lib_FlashMessage::ERROR,
				FALSE
			);

		}

		t3lib_FlashMessageQueue::addMessage($_message);

	}

	/**
	 * Add structure configuration
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function cmdAddStructure() {

		// Include structure definition file.
		include_once(t3lib_extMgm::extPath($this->extKey).'modules/'.$this->modPath.'structures.inc.php');

		// Build data array.
		foreach ($structures as $index_name => $values) {

			$data['tx_dlf_structures'][uniqid('NEW')] = array (
				'pid' => intval($this->id),
				'toplevel' => $values['toplevel'],
				'label' => $GLOBALS['LANG']->getLL($index_name),
				'index_name' => $index_name,
				'oai_name' => $values['oai_name']
			);

		}

		$_ids = tx_dlf_helper::processDB($data);

		// Check for failed inserts.
		if (count($_ids) == count($structures)) {

			// Fine.
			$_message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('flash.structureAddedMsg'),
				$GLOBALS['LANG']->getLL('flash.structureAdded', TRUE),
				t3lib_FlashMessage::OK,
				FALSE
			);

		} else {

			// Something went wrong.
			$_message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('flash.structureNotAddedMsg'),
				$GLOBALS['LANG']->getLL('flash.structureNotAdded', TRUE),
				t3lib_FlashMessage::ERROR,
				FALSE
			);

		}

		t3lib_FlashMessageQueue::addMessage($_message);

	}

	/**
	 * Main function of the module
	 *
	 * @access	public
	 *
	 * @return	void
	 */
	public function main() {

		// Is the user allowed to access this page?
		$access = is_array($this->pageInfo) && $GLOBALS['BE_USER']->isAdmin();

		if ($this->id && $access) {

			// Check if page is sysfolder.
			if ($this->pageInfo['doktype'] != 254) {

				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('flash.wrongPageTypeMsg'),
					$GLOBALS['LANG']->getLL('flash.wrongPageType', TRUE),
					t3lib_FlashMessage::ERROR,
					FALSE
				);

				t3lib_FlashMessageQueue::addMessage($_message);

				$this->markerArray['CONTENT'] .= t3lib_FlashMessageQueue::renderFlashMessages();

				$this->printContent();

				return;

			}

			// Should we do something?
			if (!empty($this->CMD)) {

				// Sanitize input...
				$_method = 'cmd'.ucfirst($this->CMD);

				// ...and unset to prevent infinite looping.
				unset ($this->CMD);

				if (method_exists($this, $_method)) {

					$this->$_method();

				}

			}

			// Check for existing structure configuration.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tx_dlf_structures',
				'pid='.intval($this->id).tx_dlf_helper::whereClause('tx_dlf_structures')
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				// Fine.
				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('flash.structureOkayMsg'),
					$GLOBALS['LANG']->getLL('flash.structureOkay', TRUE),
					t3lib_FlashMessage::OK,
					FALSE
				);

			} else {

				// Configuration missing.
				$_url = t3lib_div::locationHeaderUrl(t3lib_div::linkThisScript(array ('id' => $this->id, 'CMD' => 'addStructure')));

				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					sprintf($GLOBALS['LANG']->getLL('flash.structureNotOkayMsg'), $_url),
					$GLOBALS['LANG']->getLL('flash.structureNotOkay', TRUE),
					t3lib_FlashMessage::ERROR,
					FALSE
				);

			}

			t3lib_FlashMessageQueue::addMessage($_message);

			// Check for existing metadata configuration.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'tx_dlf_metadata',
				'pid='.intval($this->id).tx_dlf_helper::whereClause('tx_dlf_metadata')
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				// Fine.
				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('flash.metadataOkayMsg'),
					$GLOBALS['LANG']->getLL('flash.metadataOkay', TRUE),
					t3lib_FlashMessage::OK,
					FALSE
				);

			} else {

				// Configuration missing.
				$_url = t3lib_div::locationHeaderUrl(t3lib_div::linkThisScript(array ('id' => $this->id, 'CMD' => 'addMetadata')));

				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					sprintf($GLOBALS['LANG']->getLL('flash.metadataNotOkayMsg'), $_url),
					$GLOBALS['LANG']->getLL('flash.metadataNotOkay', TRUE),
					t3lib_FlashMessage::ERROR,
					FALSE
				);

			}

			t3lib_FlashMessageQueue::addMessage($_message);

			// Check the access conditions for the command line indexer's user.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,db_mountpoints',
				'be_groups',
				'title='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups').' AND '.$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'].'=0'.t3lib_BEfunc::deleteClause('be_groups')
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

				if (t3lib_div::inList($resArray['db_mountpoints'], $this->id)) {

					// Fine.
					$_message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('flash.usergroupOkayMsg'),
						$GLOBALS['LANG']->getLL('flash.usergroupOkay', TRUE),
						t3lib_FlashMessage::OK,
						FALSE
					);

				} else {

					// Configuration missing.
					$_url = t3lib_div::locationHeaderUrl(t3lib_div::linkThisScript(array ('id' => $this->id, 'CMD' => 'addAccessRights')));

					$_message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						sprintf($GLOBALS['LANG']->getLL('flash.usergroupNotOkayMsg'), $_url),
						$GLOBALS['LANG']->getLL('flash.usergroupNotOkay', TRUE),
						t3lib_FlashMessage::ERROR,
						FALSE
					);

				}

			} else {

				// Usergoup missing.
				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('flash.usergroupMissingMsg'),
					$GLOBALS['LANG']->getLL('flash.usergroupMissing', TRUE),
					t3lib_FlashMessage::ERROR,
					FALSE
				);

			}

			t3lib_FlashMessageQueue::addMessage($_message);

			// Check for existing Solr core.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,pid',
				'tx_dlf_solrcores',
				'pid IN ('.intval($this->id).',0)'.tx_dlf_helper::whereClause('tx_dlf_solrcores')
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

				if ($resArray['pid']) {

					// Fine.
					$_message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('flash.solrcoreOkayMsg'),
						$GLOBALS['LANG']->getLL('flash.solrcoreOkay', TRUE),
						t3lib_FlashMessage::OK,
						FALSE
					);

				} else {

					// Default core available, but this is deprecated.
					$_url = t3lib_div::locationHeaderUrl(t3lib_div::linkThisScript(array ('id' => $this->id, 'CMD' => 'addSolrcore')));

					$_message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						sprintf($GLOBALS['LANG']->getLL('flash.solrcoreDeprecatedMsg'), $_url),
						$GLOBALS['LANG']->getLL('flash.solrcoreDeprecatedOkay', TRUE),
						t3lib_FlashMessage::NOTICE,
						FALSE
					);

				}

			} else {

				// Solr core missing.
				$_url = t3lib_div::locationHeaderUrl(t3lib_div::linkThisScript(array ('id' => $this->id, 'CMD' => 'addSolrcore')));

				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					sprintf($GLOBALS['LANG']->getLL('flash.solrcoreMissingMsg'), $_url),
					$GLOBALS['LANG']->getLL('flash.solrcoreMissing', TRUE),
					t3lib_FlashMessage::WARNING,
					FALSE
				);

			}

			t3lib_FlashMessageQueue::addMessage($_message);

			$this->markerArray['CONTENT'] .= t3lib_FlashMessageQueue::renderFlashMessages();

		} else {

			// TODO: Ändern!
			$this->markerArray['CONTENT'] .= 'You are not allowed to access this page or have not selected a page, yet.';

		}

		$this->printContent();

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/modules/newclient/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/modules/newclient/index.php']);
}

$SOBE = t3lib_div::makeInstance('tx_dlf_modNewclient');

$SOBE->main();

?>