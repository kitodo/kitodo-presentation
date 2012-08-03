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

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if required variables are not set.
			return $content;

		}

		$metadata = array ();

		if ($this->conf['rootline'] < 2) {

			// Get current structure's @ID.
			$_ids = array ();

			if (!empty($this->doc->physicalPages[$this->piVars['page']]) && !empty($this->doc->smLinks['p2l'][$this->doc->physicalPages[$this->piVars['page']]])) {

				foreach ($this->doc->smLinks['p2l'][$this->doc->physicalPages[$this->piVars['page']]] as $_logId) {

					$_count = count($this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$_logId.'"]/ancestor::*'));

					$_ids[$_count][] = $_logId;

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

						$metadata[] = $_data;

					}

				}

			} else {

				$_id = array_pop($_ids);

				if (is_array($_id)) {

					foreach ($_id as $id) {

						$_data = $this->doc->getMetadata($id, $this->conf['pages']);

						$_data['_id'] = $id;

						$metadata[] = $_data;

					}

				}

			}

		}

		// Get titledata?
		if (empty($metadata)) {

			$_data = $this->doc->getTitleData($this->conf['pages']);

			$_data['_id'] = '';

			$metadata[] = $_data;

		}

		if (empty($metadata)) {

			trigger_error('No metadata found', E_USER_WARNING);

			return $content;

		}

		ksort($metadata);

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

			if ($this->conf['showFull'] || $resArray['is_listed']) {

				$metaList[$resArray['index_name']] = array (
					'wrap' => $resArray['wrap'],
					'label' => tx_dlf_helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages'])
				);

			}

		}

		// Save original data array.
		$cObjData = $this->cObj->data;

		// Parse the metadata arrays.
		foreach ($metadata as $_metadata) {

			$markerArray['###METADATA###'] = '';

			// Reset content object's data array.
			$this->cObj->data = $cObjData;

			// Load all the metadata values into the content object's data array.
			foreach ($_metadata as $_index_name => $_value) {

				if (is_array($_value)) {

					$this->cObj->data[$_index_name] = implode($this->conf['separator'], $_value);

				} else {

					$this->cObj->data[$_index_name] = $_value;

				}

			}

			// Process each metadate.
			foreach ($metaList as $_index_name => $_metaConf) {

				$value = '';

				$fieldwrap = $this->parseTS($_metaConf['wrap']);

				do {

					$_value = array_shift($_metadata[$_index_name]);

					if ($_index_name == 'title') {

						// Get title of parent document if needed.
						if (empty($_value) && $this->conf['getTitle'] && $this->doc->parentid) {

							$_value = '['.tx_dlf_document::getTitle($this->doc->parentid, TRUE).']';

						}

						if (!empty($_value)) {

							$_value = htmlspecialchars($_value);

							// Link title to pageview.
							if ($this->conf['linkTitle'] && $_metadata['_id']) {

								$details = $this->doc->getLogicalStructure($_metadata['_id']);

								$_value = $this->pi_linkTP(htmlspecialchars($_value), array ($this->prefixId => array ('id' => $this->doc->uid, 'page' => (!empty($details['points']) ? intval($details['points']) : 1))), TRUE, $this->conf['targetPid']);

							}

						}

					} elseif ($_index_name == 'owner' && !empty($_value)) {

						// Translate name of holding library.
						$_value = htmlspecialchars(tx_dlf_helper::translate($_value, 'tx_dlf_libraries', $this->conf['pages']));

					} elseif ($_index_name == 'type' && !empty($_value)) {

						// Translate document type.
						$_value = htmlspecialchars(tx_dlf_helper::translate($_value, 'tx_dlf_structures', $this->conf['pages']));

					} elseif ($_index_name == 'language' && !empty($_value)) {

						// Translate ISO 639 language code.
						$_value = htmlspecialchars(tx_dlf_helper::getLanguageName($_value));

					} elseif (!empty($_value)) {

						// Sanitize value for output.
						$_value = htmlspecialchars($_value);

					}

					$_value = $this->cObj->stdWrap($_value, $fieldwrap['value.']);

					if (!empty($_value)) {

						$value .= $_value;

					}

				} while (count($_metadata[$_index_name]));

				if (!empty($value)) {

					$field = $this->cObj->stdWrap(htmlspecialchars($_metaConf['label']), $fieldwrap['key.']);

					$field .= $value;

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