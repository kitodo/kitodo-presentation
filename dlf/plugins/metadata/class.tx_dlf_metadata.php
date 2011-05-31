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
 * Plugin 'DLF: Metadata' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_metadata extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/metadata/class.tx_dlf_metadata.php';

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// Quit without doing anything if required piVars are not set.
		if (!$this->checkPIvars()) {

			return $content;

		}

		// Load current document.
		$this->loadDocument();

		$metadata = array ();

		if ($this->conf['rootline'] < 2) {

			// Get current structure's @ID.
			$_ids = array ();

			foreach ($this->doc->smLinks as $_id => $_values) {

				if (!empty($this->doc->physicalPages[$this->piVars['page']]) && in_array($this->doc->physicalPages[$this->piVars['page']]['id'], $_values)) {

					$_count = count($this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$_id.'"]/ancestor::*'));

					$_ids[$_count][] = $_id;

				}

			}

			ksort($_ids);

			reset($_ids);

			// Check if we should display all metadata up to the root.
			if ($this->conf['rootline'] == 1) {

				foreach ($_ids as $_id) {

					foreach ($_id as $id) {

						$_data = $this->doc->getMetadata($id, $this->conf['pages']);

						$_data['_id'] = $id;

						$_data['type'][0] = $this->pi_getLL($_data['type'][0], tx_dlf_helper::translate($_data['type'][0], 'tx_dlf_structures', $this->conf['pages']), FALSE);

						$metadata[] = $_data;

					}

				}

			} else {

				$_id = array_pop($_ids);

				foreach ($_id as $id) {

					$_data = $this->doc->getMetadata($id, $this->conf['pages']);

					$_data['_id'] = $id;

					$_data['type'][0] = $this->pi_getLL($_data['type'][0], tx_dlf_helper::translate($_data['type'][0], 'tx_dlf_structures', $this->conf['pages']), FALSE);

					$metadata[] = $_data;

				}

			}

		}

		// Prepend metadata output with titledata?
		if (empty($metadata)) {

			$_data = $this->doc->getTitleData($this->conf['pages']);

			$_data['_id'] = '';

			$_data['type'][0] = $this->pi_getLL($_data['type'][0], tx_dlf_helper::translate($_data['type'][0], 'tx_dlf_structures', $this->conf['pages']), FALSE);

			array_unshift($metadata, $_data);

		}

		if (empty($metadata)) {

			trigger_error('No metadata found', E_USER_WARNING);

			return $content;

		}

		$content .= $this->printMetadata($metadata);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Prepares the metadata array for output
	 *
	 * @access	protected
	 *
	 * @param	array		$metadata: The metadata array
	 *
	 * @return	string		The metadata array ready for output
	 */
	protected function printMetadata(array $metadata) {

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/metadata/template.tmpl'), '###TEMPLATE###');

		}

		$output = '';

		$subpart['block'] = $this->cObj->getSubpart($this->template, '###BLOCK###');

		ksort($metadata);

		// Get list of metadata to show.
		$metaList = array ();

		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.wrap AS wrap',
			'tx_dlf_metadata',
			'tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'tx_dlf_metadata.sorting',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			if ($this->conf['showFull'] || (!$this->conf['showFull'] && $resArray['is_listed'])) {

				$metaList[$resArray['index_name']] = $resArray['wrap'];

			}

		}

		// Parse the metadata arrays.
		foreach ($metadata as $_metadata) {

			$markerArray['###METADATA###'] = '';

			foreach ($metaList as $_index_name => $_wrap) {

				if (!empty($_metadata[$_index_name]) && tx_dlf_helper::isTranslatable($_index_name, 'tx_dlf_metadata', $this->conf['pages'])) {

					$fieldwrap = $this->parseTS($_wrap);

					$field = $this->cObj->stdWrap(htmlspecialchars($this->pi_getLL($_index_name, tx_dlf_helper::translate($_index_name, 'tx_dlf_metadata', $this->conf['pages']), FALSE)), $fieldwrap['key.']);

					foreach ($_metadata[$_index_name] as $_value) {

						// Link title to pageview.
						if ($_index_name == 'title') {

							if ($_metadata['_id']) {

								$details = $this->doc->getLogicalStructure($_metadata['_id']);

							}

							$_value = $this->pi_linkTP(htmlspecialchars($_value), array ($this->prefixId => array ('id' => $this->doc->uid, 'page' => (!empty($details['points']) ? intval($details['points']) : 1))), TRUE, $this->conf['targetPid']);

						// Translate name of holding library.
						} elseif ($_index_name == 'owner') {

							$_value = htmlspecialchars(tx_dlf_helper::translate($_value, 'tx_dlf_libraries', $this->conf['pages']));

						} else {

							$_value = htmlspecialchars($_value);

						}

						$field .= $this->cObj->stdWrap($_value, $fieldwrap['value.']);

					}

					$markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);

				}

			}

			$output .= $this->cObj->substituteMarkerArray($subpart['block'], $markerArray);

		}

		return $this->cObj->substituteSubpart($this->template, '###BLOCK###', $output, TRUE);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/metadata/class.tx_dlf_metadata.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/metadata/class.tx_dlf_metadata.php']);
}

?>