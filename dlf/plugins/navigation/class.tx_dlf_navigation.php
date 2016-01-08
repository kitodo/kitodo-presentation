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
 * Plugin 'DLF: Navigation' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_navigation extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/navigation/class.tx_dlf_navigation.php';

	/**
	 * Display a link to the list view
	 *
	 * @access	protected
	 *
	 * @return	string		Link to the list view ready to output
	 */
	protected function getLinkToListview() {

		if (!empty($this->conf['targetPid'])) {

			// Load the list.
			$list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

			if (count($list) > 0) {

				// Build typolink configuration array.
				$conf = array (
					'useCacheHash' => 1,
					'parameter' => $this->conf['targetPid'],
					'title' => $this->pi_getLL('linkToList', '', TRUE)
				);

				return $this->cObj->typoLink($this->pi_getLL('linkToList', '', TRUE), $conf);

			}

		}

		return '';

	}

	/**
	 * Display the page selector for the page view
	 *
	 * @access	protected
	 *
	 * @return	string		Page selector ready to output
	 */
	protected function getPageSelector() {

		// Configure @action URL for form.
		$linkConf = array (
			'parameter' => $GLOBALS['TSFE']->id,
			'forceAbsoluteUrl' => 1
		);

		$output = '<form action="'.$this->cObj->typoLink_URL($linkConf).'" method="get"><div><input type="hidden" name="id" value="'.$GLOBALS['TSFE']->id.'" />';

		// Add plugin variables.
		foreach ($this->piVars as $piVar => $value) {

			if ($piVar != 'page' && $piVars != 'DATA' && !empty($value)) {

				$output .= '<input type="hidden" name="'.$this->prefixId.'['.$piVar.']" value="'.$value.'" />';

			}

		}

		// Add page selector.
		$uniqId = uniqid(str_replace('_', '-', get_class($this)).'-');

		$output .= '<label for="'.$uniqId.'">'.$this->pi_getLL('selectPage', '', TRUE).'</label><select id="'.$uniqId.'" name="'.$this->prefixId.'[page]" onchange="javascript:this.form.submit();"'.($this->doc->numPages < 1 ? ' disabled="disabled"' : '').'>';

		for ($i = 1; $i <= $this->doc->numPages; $i++) {

			$output .= '<option value="'.$i.'"'.($this->piVars['page'] == $i ? ' selected="selected"' : '').'>['.$i.']'.($this->doc->physicalPagesInfo[$this->doc->physicalPages[$i]]['label'] ? ' - '.htmlspecialchars($this->doc->physicalPagesInfo[$this->doc->physicalPages[$i]]['label']) : '').'</option>';

		}

		$output .= '</select></div></form>';

		return $output;

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

		// Turn cache on.
		$this->setCache(TRUE);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values if not set.
			if ($this->doc->numPages > 0) {

				// Set default values if not set.
				// page may be integer or string (physical page attribute)
				if ( (int)$this->piVars['page'] > 0 || empty($this->piVars['page'])) {

					$this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int)$this->piVars['page'], 1, $this->doc->numPages, 1);

				} else {

					$this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalPages);

				}

				$this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);

			} else {

				$this->piVars['page'] = 0;

				$this->piVars['double'] = 0;

			}

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/navigation/template.tmpl'), '###TEMPLATE###');

		}

		// Link to first page.
		if ($this->piVars['page'] > 1) {

			$markerArray['###FIRST###'] = $this->makeLink($this->pi_getLL('firstPage', '', TRUE), array ('page' => 1));

		} else {

			$markerArray['###FIRST###'] = '<span>'.$this->pi_getLL('firstPage', '', TRUE).'</span>';

		}

		// Link back X pages.
		if ($this->piVars['page'] > ($this->conf['pageStep'] * ($this->piVars['double'] + 1))) {

			$markerArray['###BACK###'] = $this->makeLink(sprintf($this->pi_getLL('backXPages', '', TRUE), $this->conf['pageStep']), array ('page' => $this->piVars['page'] - ($this->conf['pageStep'] * ($this->piVars['double'] + 1))));

		} else {

			$markerArray['###BACK###'] = '<span>'.sprintf($this->pi_getLL('backXPages', '', TRUE), $this->conf['pageStep']).'</span>';

		}

		// Link to previous page.
		if ($this->piVars['page'] > (1 + $this->piVars['double'])) {

			$markerArray['###PREVIOUS###'] = $this->makeLink($this->pi_getLL('prevPage', '', TRUE), array ('page' => $this->piVars['page'] - (1 + $this->piVars['double'])));

		} else {

			$markerArray['###PREVIOUS###'] = '<span>'.$this->pi_getLL('prevPage', '', TRUE).'</span>';

		}

		// Link to next page.
		if ($this->piVars['page'] < ($this->doc->numPages - $this->piVars['double'])) {

			$markerArray['###NEXT###'] = $this->makeLink($this->pi_getLL('nextPage', '', TRUE), array ('page' => $this->piVars['page'] + (1 + $this->piVars['double'])));

		} else {

			$markerArray['###NEXT###'] = '<span>'.$this->pi_getLL('nextPage', '', TRUE).'</span>';

		}

		// Link forward X pages.
		if ($this->piVars['page'] <= ($this->doc->numPages - ($this->conf['pageStep'] * ($this->piVars['double'] + 1)))) {

			$markerArray['###FORWARD###'] = $this->makeLink(sprintf($this->pi_getLL('forwardXPages', '', TRUE), $this->conf['pageStep']), array ('page' => $this->piVars['page'] + ($this->conf['pageStep'] * ($this->piVars['double'] + 1))));

		} else {

			$markerArray['###FORWARD###'] = '<span>'.sprintf($this->pi_getLL('forwardXPages', '', TRUE), $this->conf['pageStep']).'</span>';

		}

		// Link to last page.
		if ($this->piVars['page'] < $this->doc->numPages) {

			$markerArray['###LAST###'] = $this->makeLink($this->pi_getLL('lastPage', '', TRUE), array ('page' => $this->doc->numPages));

		} else {

			$markerArray['###LAST###'] = '<span>'.$this->pi_getLL('lastPage', '', TRUE).'</span>';

		}

		// Add double page switcher.
		if ($this->doc->numPages > 0) {

			if (!$this->piVars['double']) {

				$markerArray['###DOUBLEPAGE###'] = $this->makeLink($this->pi_getLL('doublePageOn', '', TRUE), array ('double' => 1), 'class="tx-dlf-navigation-doubleOn"');

			} else {

				$markerArray['###DOUBLEPAGE###'] = $this->makeLink($this->pi_getLL('doublePageOff', '', TRUE), array ('double' => 0), 'class="tx-dlf-navigation-doubleOff"');

			}

			if ($this->piVars['double'] && $this->piVars['page'] < $this->doc->numPages) {

				$markerArray['###DOUBLEPAGE+1###'] = $this->makeLink($this->pi_getLL('doublePage+1', '', TRUE), array ('page' => $this->piVars['page'] + 1));

			} else {

				$markerArray['###DOUBLEPAGE+1###'] = '<span>'.$this->pi_getLL('doublePage+1', '', TRUE).'</span>';

			}

		} else {

			$markerArray['###DOUBLEPAGE###'] = '<span>'.$this->pi_getLL('doublePageOn', '', TRUE).'</span>';

			$markerArray['###DOUBLEPAGE+1###'] = '<span>'.$this->pi_getLL('doublePage+1', '', TRUE).'</span>';

		}

		// Add page selector.
		$markerArray['###PAGESELECT###'] = $this->getPageSelector();

		// Add link to listview if applicable.
		$markerArray['###LINKLISTVIEW###'] = $this->getLinkToListview();

		// fill some language labels if available
		$markerArray['###ZOOM_IN###'] =  $this->pi_getLL('zoom-in', '', TRUE);
		$markerArray['###ZOOM_OUT###'] = $this->pi_getLL('zoom-out', '', TRUE);
		$markerArray['###ZOOM_FULLSCREEN###'] = $this->pi_getLL('zoom-fullscreen', '', TRUE);

 		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Generates a navigation link
	 *
	 * @access	protected
	 *
	 * @param	string		$label: The link's text
	 * @param	array		$overrulePIvars: The new set of plugin variables
	 * @param	string		$aTagParams: Additional HTML attributes for link tag
	 *
	 * @return	string		Typolink ready to output
	 */
	protected function makeLink($label, array $overrulePIvars = array (), $aTagParams = '') {

		// Merge plugin variables with new set of values.
		if (is_array($this->piVars)) {

			$piVars = $this->piVars;

			unset($piVars['DATA']);

			$overrulePIvars = tx_dlf_helper::array_merge_recursive_overrule($piVars, $overrulePIvars);

		}

		// Build typolink configuration array.
		$conf = array (
			'useCacheHash' => 1,
			'parameter' => $GLOBALS['TSFE']->id,
			'ATagParams' => $aTagParams,
			'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $overrulePIvars, '', TRUE, FALSE),
			'title' => $label
		);

		return $this->cObj->typoLink($label, $conf);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/navigation/class.tx_dlf_navigation.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/navigation/class.tx_dlf_navigation.php']);
}
