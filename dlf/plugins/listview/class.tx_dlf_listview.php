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
 * Plugin 'DLF: List View' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_listview extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/listview/class.tx_dlf_listview.php';

	/**
	 * This holds the list
	 *
	 * @var	tx_dlf_list
	 * @access	protected
	 */
	protected $list;

	/**
	 * Array of sorted metadata
	 *
	 * @var	array
	 * @access	protected
	 */
	protected $metadata = array ();

	/**
	 * Array of sortable metadata
	 *
	 * @var	array
	 * @access	protected
	 */
	protected $sortables = array ();

	/**
	 * Renders the page browser
	 *
	 * @access	protected
	 *
	 * @return	string		The rendered page browser ready for output
	 */
	protected function getPageBrowser() {

		// Get overall number of pages.
		$maxPages = intval(ceil(count($this->list) / $this->conf['limit']));

		// Return empty pagebrowser if there is just one page.
		if ($maxPages < 2) {

			return '';

		}

		// Get separator.
		$separator = $this->pi_getLL('separator', ' - ', TRUE);

		// Add link to previous page.
		if ($this->piVars['pointer'] > 0) {

			$output = $this->pi_linkTP_keepPIvars($this->pi_getLL('prevPage', '&lt;', TRUE), array ('pointer' => $this->piVars['pointer'] - 1), TRUE).$separator;

		} else {

			$output = $this->pi_getLL('prevPage', '&lt;', TRUE).$separator;

		}

		$i = 0;

		$skip = NULL;

		// Add links to pages.
		while ($i < $maxPages) {

			if ($i < 3 || ($i > $this->piVars['pointer'] - 3 && $i < $this->piVars['pointer'] + 3) || $i > $maxPages - 4) {

				if ($this->piVars['pointer'] != $i) {

					$output .= $this->pi_linkTP_keepPIvars(sprintf($this->pi_getLL('page', '%d', TRUE), $i + 1), array ('pointer' => $i), TRUE).$separator;

				} else {

					$output .= sprintf($this->pi_getLL('page', '%d', TRUE), $i + 1).$separator;

				}

				$skip = TRUE;

			} elseif ($skip === TRUE) {

				$output .= $this->pi_getLL('skip', '...', TRUE).$separator;

				$skip = FALSE;

			}

			$i++;

		}

		// Add link to next page.
		if ($this->piVars['pointer'] < $maxPages - 1) {

			$output .= $this->pi_linkTP_keepPIvars($this->pi_getLL('nextPage', '&gt;', TRUE), array ('pointer' => $this->piVars['pointer'] + 1), TRUE);

		} else {

			$output .= $this->pi_getLL('nextPage', '&gt;', TRUE);

		}

		return $output;

	}

	/**
	 * Renders one entry of the list
	 *
	 * @access	protected
	 *
	 * @param	integer		$number: The number of the entry
	 * @param	string		$template: Parsed template subpart
	 *
	 * @return	string		The rendered entry ready for output
	 */
	protected function getEntry($number, $template) {

		$markerArray['###NUMBER###'] = $number + 1;

		$markerArray['###METADATA###'] = '';

		$markerArray['###THUMBNAIL###'] = '';

		$markerArray['###PREVIEW###'] = '';

		$subpart = '';

		$imgAlt = '';

		$noTitle = $this->pi_getLL('noTitle');

		$metadata = $this->list[$number]['metadata'];

		foreach ($this->metadata as $index_name => $metaConf) {

			$parsedValue = '';

			$fieldwrap = $this->parseTS($metaConf['wrap']);

			do {

				$value = @array_shift($metadata[$index_name]);

				// Link title to pageview.
				if ($index_name == 'title') {

					// Get title of parent document if needed.
					if (empty($value) && $this->conf['getTitle']) {

						$superiorTitle = tx_dlf_document::getTitle($this->list[$number]['uid'], TRUE);

						if (!empty($superiorTitle)) {

							$value = '['.$superiorTitle.']';

						}

					}

					// Set fake title if still not present.
					if (empty($value)) {

						$value = $noTitle;

					}

					$imgAlt = htmlspecialchars($value);

					$additionalParams = array (
						'id' => $this->list[$number]['uid'],
						'page' => $this->list[$number]['page']
					);

					if(!empty($this->piVars['logicalPage'])) {

						$additionalParams['logicalPage'] = $this->piVars['logicalPage'];

					}

					$conf = array (
						'useCacheHash' => 1,
						'parameter' => $this->conf['targetPid'],
						'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE)
					);

					$value = $this->cObj->typoLink(htmlspecialchars($value), $conf);

				// Translate name of holding library.
				} elseif ($index_name == 'owner' && !empty($value)) {

					$value = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));

				// Translate document type.
				} elseif ($index_name == 'type' && !empty($value)) {

					$value = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));

				// Translate ISO 639 language code.
				} elseif ($index_name == 'language' && !empty($value)) {

					$value = htmlspecialchars(tx_dlf_helper::getLanguageName($value));

				} elseif (!empty($value)) {

					$value = htmlspecialchars($value);

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

		// Add thumbnail.
		if (!empty($this->list[$number]['thumbnail'])) {

			$markerArray['###THUMBNAIL###'] = '<img alt="'.$imgAlt.'" src="'.$this->list[$number]['thumbnail'].'" />';

		}

		// Add preview.
		if (!empty($this->list[$number]['preview'])) {

			$markerArray['###PREVIEW###'] = $this->list[$number]['preview'];

		}

		if (!empty($this->list[$number]['subparts'])) {

			$subpart = $this->getSubEntries($number, $template);

		}

		// basket button
		$markerArray['###BASKETBUTTON###'] = '';

		if (!empty($this->conf['basketButton']) && !empty($this->conf['targetBasket'])) {

			$additionalParams = array ('id' => $this->list[$number]['uid'], 'startpage' => $this->list[$number]['page'], 'addToBasket' => 'list');

			$conf = array (
				'useCacheHash' => 1,
				'parameter' => $this->conf['targetBasket'],
				'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE)
			);

			$link = $this->cObj->typoLink($this->pi_getLL('addBasket', '', TRUE), $conf);

			$markerArray['###BASKETBUTTON###'] = $link;

		}

		return $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($template['entry'], '###SUBTEMPLATE###', $subpart, TRUE), $markerArray);

	}

	/**
	 * Renders sorting dialog
	 *
	 * @access	protected
	 *
	 * @return	string		The rendered sorting dialog ready for output
	 */
	protected function getSortingForm() {

		// Return nothing if there are no sortable metadata fields.
		if (!count($this->sortables)) {

			return '';

		}

		// Set class prefix.
		$prefix = str_replace('_', '-', get_class($this));

		// Configure @action URL for form.
		$linkConf = array (
			'parameter' => $GLOBALS['TSFE']->id,
			'forceAbsoluteUrl' => 1
		);

		if(!empty($this->piVars['logicalPage'])) {

			$linkConf['additionalParams'] = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId,array('logicalPage' => $this->piVars['logicalPage']), '', TRUE, FALSE);

		}

		// Build HTML form.
		$sorting = '<form action="'.$this->cObj->typoLink_URL($linkConf).'" method="get"><div><input type="hidden" name="id" value="'.$GLOBALS['TSFE']->id.'" />';

		foreach ($this->piVars as $piVar => $value) {

			if ($piVar != 'order' && $piVar != 'DATA' && !empty($value)) {

				$sorting .= '<input type="hidden" name="'.$this->prefixId.'['.$piVar.']" value="'.$value.'" />';

			}

		}

		// Select sort field.
		$uniqId = uniqid($prefix.'-');

		$sorting .= '<label for="'.$uniqId.'">'.$this->pi_getLL('orderBy', '', TRUE).'</label><select id="'.$uniqId.'" name="'.$this->prefixId.'[order]" onchange="javascript:this.form.submit();">';

		// Add relevance sorting if this is a search result list.
		if ($this->list->metadata['options']['source'] == 'search') {

			$sorting .= '<option value="relevance"'.(($this->list->metadata['options']['order'] == 'relevance') ? ' selected="selected"' : '').'>'.$this->pi_getLL('relevance', '', TRUE).'</option>';

		}

		foreach ($this->sortables as $index_name => $label) {

			$sorting .= '<option value="'.$index_name.'"'.(($this->list->metadata['options']['order'] == $index_name) ? ' selected="selected"' : '').'>'.htmlspecialchars($label).'</option>';

		}

		$sorting .= '</select>';

		// Select sort direction.
		$uniqId = uniqid($prefix.'-');

		$sorting .= '<label for="'.$uniqId.'">'.$this->pi_getLL('direction', '', TRUE).'</label><select id="'.$uniqId.'" name="'.$this->prefixId.'[asc]" onchange="javascript:this.form.submit();">';

		$sorting .= '<option value="1" '.($this->list->metadata['options']['order.asc'] ? ' selected="selected"' : '').'>'.$this->pi_getLL('direction.asc', '', TRUE).'</option>';

		$sorting .= '<option value="0" '.(!$this->list->metadata['options']['order.asc'] ? ' selected="selected"' : '').'>'.$this->pi_getLL('direction.desc', '', TRUE).'</option>';

		$sorting .= '</select></div></form>';

		return $sorting;

	}

	/**
	 * Renders all sub-entries of one entry
	 *
	 * @access	protected
	 *
	 * @param	integer		$number: The number of the entry
	 * @param	string		$template: Parsed template subpart
	 *
	 * @return	string		The rendered entries ready for output
	 */
	protected function getSubEntries($number, $template) {

		$content = '';

		$noTitle = $this->pi_getLL('noTitle');

		$highlight_word = preg_replace('/\s\s+/', ';', $this->list->metadata['searchString']);

		foreach ($this->list[$number]['subparts'] as $subpart) {

			$markerArray['###SUBMETADATA###'] = '';

			$markerArray['###SUBTHUMBNAIL###'] = '';

			$markerArray['###SUBPREVIEW###'] = '';

			$imgAlt = '';

			foreach ($this->metadata as $index_name => $metaConf) {

				$parsedValue = '';

				$fieldwrap = $this->parseTS($metaConf['wrap']);

				do {

					$value = @array_shift($subpart['metadata'][$index_name]);

					// Link title to pageview.
					if ($index_name == 'title') {

						// Get title of parent document if needed.
						if (empty($value) && $this->conf['getTitle']) {

							$superiorTitle = tx_dlf_document::getTitle($subpart['uid'], TRUE);

							if (!empty($superiorTitle)) {

								$value = '['.$superiorTitle.']';

							}

						}

						// Set fake title if still not present.
						if (empty($value)) {

							$value = $noTitle;

						}

						$imgAlt = htmlspecialchars($value);

						$additionalParams = array (
							'id' => $subpart['uid'],
							'page' => $subpart['page'],
							'highlight_word' => $highlight_word
						);

						if(!empty($this->piVars['logicalPage'])) {

							$additionalParams['logicalPage'] = $this->piVars['logicalPage'];

						}

						$conf = array (
							// we don't want cHash in case of search parameters
							'useCacheHash' => empty($this->list->metadata['searchString']) ? 1 : 0,
							'parameter' => $this->conf['targetPid'],
							'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE)
						);

						$value = $this->cObj->typoLink(htmlspecialchars($value), $conf);

					// Translate name of holding library.
					} elseif ($index_name == 'owner' && !empty($value)) {

						$value = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));

					// Translate document type.
					} elseif ($index_name == 'type' && !empty($value)) {

						$_value = $value;

						$value = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));

						// Add page number for single pages.
						if ($_value == 'page') {

							$value .= ' '.intval($subpart['page']);

						}

					// Translate ISO 639 language code.
					} elseif ($index_name == 'language' && !empty($value)) {

						$value = htmlspecialchars(tx_dlf_helper::getLanguageName($value));

					} elseif (!empty($value)) {

						$value = htmlspecialchars($value);

					}

					$value = $this->cObj->stdWrap($value, $fieldwrap['value.']);

					if (!empty($value)) {

						$parsedValue .= $value;

					}

				} while (count($subpart['metadata'][$index_name]));

				if (!empty($parsedValue)) {

					$field = $this->cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.']);

					$field .= $parsedValue;

					$markerArray['###SUBMETADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);

				}

			}

			// Add thumbnail.
			if (!empty($subpart['thumbnail'])) {

				$markerArray['###SUBTHUMBNAIL###'] = '<img alt="'.$imgAlt.'" src="'.$subpart['thumbnail'].'" />';

			}

			// Add preview.
			if (!empty($subpart['preview'])) {

				$markerArray['###SUBPREVIEW###'] = $subpart['preview'];

			}

			// basket button
			$markerArray['###SUBBASKETBUTTON###'] = '';

			if (!empty($this->conf['basketButton']) && !empty($this->conf['targetBasket'])) {

				$additionalParams = array ('id' => $this->list[$number]['uid'], 'startpage' => $subpart['page'], 'endpage' => $subpart['page'], 'logId' => $subpart['sid'], 'addToBasket' => 'subentry');

				$conf = array (
					'useCacheHash' => 1,
					'parameter' => $this->conf['targetBasket'],
					'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE)
				);

				$link = $this->cObj->typoLink($this->pi_getLL('addBasket', '', TRUE), $conf);

				$markerArray['###SUBBASKETBUTTON###'] = $link;

			}

			$content .= $this->cObj->substituteMarkerArray($template['subentry'], $markerArray);

		}

		return $this->cObj->substituteSubpart($this->cObj->getSubpart($this->template, '###SUBTEMPLATE###'), '###SUBENTRY###', $content, TRUE);

	}

	/**
	 * Get metadata configuration from database
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function loadConfig() {

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.wrap AS wrap,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.is_sortable AS is_sortable',
			'tx_dlf_metadata',
			'(tx_dlf_metadata.is_listed=1 OR tx_dlf_metadata.is_sortable=1) AND tx_dlf_metadata.pid='.intval($this->conf['pages']).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'tx_dlf_metadata.sorting ASC',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			if ($resArray['is_listed']) {

				$this->metadata[$resArray['index_name']] = array (
					'wrap' => $resArray['wrap'],
					'label' => tx_dlf_helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages'])
				);

			}

			if ($resArray['is_sortable']) {

				$this->sortables[$resArray['index_name']] = tx_dlf_helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages']);

			}

		}

	}

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

		// Don't cache the output.
		$this->setCache(FALSE);

		// Load the list.
		$this->list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

		// Sort the list if applicable.
		if ((!empty($this->piVars['order']) && $this->piVars['order'] != $this->list->metadata['options']['order'])
			|| (isset($this->piVars['asc']) && $this->piVars['asc'] != $this->list->metadata['options']['order.asc'])) {

			// Order list by given field.
			$this->list->sort($this->piVars['order'], (boolean) $this->piVars['asc']);

			// Update list's metadata.
			$listMetadata = $this->list->metadata;

			$listMetadata['options']['order'] = $this->piVars['order'];

			$listMetadata['options']['order.asc'] = (boolean) $this->piVars['asc'];

			$this->list->metadata = $listMetadata;

			// Save updated list.
			$this->list->save();

			// Reset pointer.
			$this->piVars['pointer'] = 0;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/listview/template.tmpl'), '###TEMPLATE###');

		}

		$subpartArray['entry'] = $this->cObj->getSubpart($this->template, '###ENTRY###');

		$subpartArray['subentry'] = $this->cObj->getSubpart($this->template, '###SUBENTRY###');

		// Set some variable defaults.
		if (!empty($this->piVars['pointer']) && (($this->piVars['pointer'] * $this->conf['limit']) + 1) <= count($this->list)) {

			$this->piVars['pointer'] = max(intval($this->piVars['pointer']), 0);

		} else {

			$this->piVars['pointer'] = 0;

		}

		// Load metadata configuration.
		$this->loadConfig();

		$i = $this->piVars['pointer'] * $this->conf['limit'];
		$j = ($this->piVars['pointer'] + 1) * $this->conf['limit'];

		for ($i, $j; $i < $j; $i++) {

			if (empty($this->list[$i])) {

				break;

			} else {

				$content .= $this->getEntry($i, $subpartArray);

			}

		}

		$markerArray['###LISTTITLE###'] = $this->list->metadata['label'];

		$markerArray['###LISTDESCRIPTION###'] = $this->list->metadata['description'];

		if (!empty($this->list->metadata['thumbnail'])) {

			$markerArray['###LISTTHUMBNAIL###'] = '<img alt="" src="'.$this->list->metadata['thumbnail'].'" />';

		} else {

			$markerArray['###LISTTHUMBNAIL###'] = '';

		}

		if ($i) {

			$markerArray['###COUNT###'] = htmlspecialchars(sprintf($this->pi_getLL('count'), ($this->piVars['pointer'] * $this->conf['limit']) + 1, $i, count($this->list)));

		} else {

			$markerArray['###COUNT###'] = $this->pi_getLL('nohits', '', TRUE);

		}

		$markerArray['###PAGEBROWSER###'] = $this->getPageBrowser();

		$markerArray['###SORTING###'] = $this->getSortingForm();

		$content = $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($this->template, '###ENTRY###', $content, TRUE), $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}
