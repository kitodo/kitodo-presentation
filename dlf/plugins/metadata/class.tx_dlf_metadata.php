<?php
/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Plugin 'DLF: Metadata' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Siegfried Schweizer <siegfried.schweizer@sbb.spk-berlin.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_metadata extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/metadata/class.tx_dlf_metadata.php';

	/**
	 * This holds the hook objects
	 *
	 * @var	array
	 * @access protected
	 */
	protected $hookObjects = array ();

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

		// Turn cache on.
		$this->setCache(TRUE);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values if not set.
			if (!isset($this->conf['rootline'])) {

				$this->conf['rootline'] = 0;

			}

		}

		$metadata = array ();

		if ($this->conf['rootline'] < 2) {

			// Get current structure's @ID.
			$ids = array ();

			if (!empty($this->doc->physicalStructure[$this->piVars['page']]) && !empty($this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page']]])) {

				foreach ($this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page']]] as $logId) {

					$count = count($this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$logId.'"]/ancestor::*'));

					$ids[$count][] = $logId;

				}

			}

			ksort($ids);

			reset($ids);

			// Check if we should display all metadata up to the root.
			if ($this->conf['rootline'] == 1) {

				foreach ($ids as $id) {

					foreach ($id as $sid) {

						$data = $this->doc->getMetadata($sid, $this->conf['pages']);

						if (!empty($data)) {

							$data['_id'] = $sid;

							$metadata[] = $data;

						}

					}

				}

			} else {

				$id = array_pop($ids);

				if (is_array($id)) {

					foreach ($id as $sid) {

						$data = $this->doc->getMetadata($sid, $this->conf['pages']);

						if (!empty($data)) {

							$data['_id'] = $sid;

							$metadata[] = $data;

						}

					}

				}

			}

		}

		// Get titledata?
		if (empty($metadata) || ($this->conf['rootline'] == 1 && $metadata[0]['_id'] != $this->doc->toplevelId)) {

			$data = $this->doc->getTitleData($this->conf['pages']);

			$data['_id'] = $this->doc->toplevelId;

			array_unshift($metadata, $data);

		}

		if (empty($metadata)) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_metadata->main('.$content.', [data])] No metadata found for document with UID "'.$this->doc->uid.'"', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);

			}

			return $content;

		}

		ksort($metadata);

		// Get hook objects.
		$this->hookObjects = tx_dlf_helper::getHookObjects($this->scriptRelPath);

		// Hook for getting a customized title bar (requested by SBB).
		foreach ($this->hookObjects as $hookObj) {

			if (method_exists($hookObj, 'main_customizeTitleBarGetCustomTemplate')) {

				$hookObj->main_customizeTitleBarGetCustomTemplate($this, $metadata);

			}

		}

		$content .= $this->printMetadata($metadata);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Prepares the metadata array for output
	 *
	 * @access	protected
	 *
	 * @param	array		$metadataArray: The metadata array
	 *
	 * @return	string		The metadata array ready for output
	 */
	protected function printMetadata(array $metadataArray) {

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

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.wrap AS wrap',
			'tx_dlf_metadata',
			'tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata').' AND (sys_language_uid IN (-1,0) OR (sys_language_uid = ' .$GLOBALS['TSFE']->sys_language_uid. ' AND l18n_parent = 0))',
			'',
			'tx_dlf_metadata.sorting',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			if (is_array($resArray) && $resArray['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content && $GLOBALS['TSFE']->sys_language_contentOL) {

					$resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_dlf_metadata', $resArray, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);

				}

				if ($resArray) {
					// get correct language uid for translated realurl link
					$link_uid = ($resArray['_LOCALIZED_UID']) ? $resArray['_LOCALIZED_UID'] : $resArray['uid'];

					// do stuff with the row entry data	like built HTML or prepare further usage
					if ($this->conf['showFull'] || $resArray['is_listed']) {

						$metaList[$resArray['index_name']] = array (
							'wrap' => $resArray['wrap'],
							'label' => tx_dlf_helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages'])
						);

					}

				}

		}

		// Get list of collections to show.
		$collList = array ();

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_collections.index_name AS index_name',
			'tx_dlf_collections',
			'tx_dlf_collections.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_collections'),
			'',
			'',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			$collList[] = $resArray['index_name'];

		}

		// Save original data array.
		$cObjData = $this->cObj->data;

		// Parse the metadata arrays.
		foreach ($metadataArray as $metadata) {

			$markerArray['###METADATA###'] = '';

			// Reset content object's data array.
			$this->cObj->data = $cObjData;

			// Load all the metadata values into the content object's data array.
			foreach ($metadata as $index_name => $value) {

				if (is_array($value)) {

					$this->cObj->data[$index_name] = implode($this->conf['separator'], $value);

				} else {

					$this->cObj->data[$index_name] = $value;

				}

			}

			// Process each metadate.
			foreach ($metaList as $index_name => $metaConf) {

				$parsedValue = '';

				$fieldwrap = $this->parseTS($metaConf['wrap']);

				do {

					$value = @array_shift($metadata[$index_name]);

					if ($index_name == 'title') {

						// Get title of parent document if needed.
						if (empty($value) && $this->conf['getTitle'] && $this->doc->parentId) {

							$superiorTitle = tx_dlf_document::getTitle($this->doc->parentId, TRUE);

							if (!empty($superiorTitle)) {

								$value = '['.$superiorTitle.']';

							}

						}

						if (!empty($value)) {

							$value = htmlspecialchars($value);

							// Link title to pageview.
							if ($this->conf['linkTitle'] && $metadata['_id']) {

								$details = $this->doc->getLogicalStructure($metadata['_id']);

								$value = $this->pi_linkTP($value, array ($this->prefixId => array ('id' => $this->doc->uid, 'page' => (!empty($details['points']) ? intval($details['points']) : 1))), TRUE, $this->conf['targetPid']);

							}

						}

					} elseif ($index_name == 'owner' && !empty($value)) {

						// Translate name of holding library.
						$value = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));

					} elseif ($index_name == 'type' && !empty($value)) {

						// Translate document type.
						$value = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));

					} elseif ($index_name == 'collection' && !empty($value)) {

						// Check if collections isn't hidden.
						if (in_array($value, $collList)) {

							// Translate collection.
							$value = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_collections', $this->conf['pages']));

						} else {

							$value = '';

						}

					} elseif ($index_name == 'language' && !empty($value)) {

						// Translate ISO 639 language code.
						$value = htmlspecialchars(tx_dlf_helper::getLanguageName($value));

					} elseif (!empty($value)) {

						// Sanitize value for output.
						$value = htmlspecialchars($value);

					}

					// Hook for getting a customized value (requested by SBB).
					foreach ($this->hookObjects as $hookObj) {

						if (method_exists($hookObj, 'printMetadata_customizeMetadata')) {

							$hookObj->printMetadata_customizeMetadata($value);

						}

					}

					$value = $this->cObj->stdWrap($value, $fieldwrap['value.']);

					if (!empty($value)) {

						$parsedValue .= $value;

					}

				} while (count($metadata[$index_name]));

				if (!empty($parsedValue)) {

					$field = $this->cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.']);

					$field .= $parsedValue;

					$markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);

				}

			}

			$output .= $this->cObj->substituteMarkerArray($subpart['block'], $markerArray);

		}

		return $this->cObj->substituteSubpart($this->template, '###BLOCK###', $output, TRUE);

	}

}
