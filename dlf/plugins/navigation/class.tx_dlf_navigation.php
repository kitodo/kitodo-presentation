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
 * Plugin 'DLF: Navigation' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_navigation extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/navigation/class.tx_dlf_navigation.php';

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

		// Quit without doing anything if required variables are not set.
		if ($this->doc === NULL || $this->doc->numPages < 1) {

			return $content;

		} else {

			$this->piVars['page'] = min($this->piVars['page'], $this->doc->numPages);

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/navigation/template.tmpl'), '###TEMPLATE###');

		}

		// Set class prefix.
		$prefix = str_replace('_', '-', get_class($this));

		// Link to first page.
		if ($this->piVars['page'] > 1) {

			$markerArray['###FIRST###'] = $this->makeLink($this->pi_getLL('firstPage', '', TRUE), $prefix.'-first', array ('page' => 1));

		} else {

			$markerArray['###FIRST###'] = $this->makeLink($this->pi_getLL('firstPage', '', TRUE), $prefix.'-first');

		}

		// Link back X pages.
		if ($this->piVars['page'] > $this->conf['pageStep']) {

			$markerArray['###BACK###'] = $this->makeLink(sprintf($this->pi_getLL('backXPages', '', TRUE), $this->conf['pageStep']), $prefix.'-back', array ('page' => $this->piVars['page'] - $this->conf['pageStep']));

		} else {

			$markerArray['###BACK###'] = $this->makeLink(sprintf($this->pi_getLL('backXPages', '', TRUE), $this->conf['pageStep']), $prefix.'-back');

		}

		// Link to previous page.
		if ($this->piVars['page'] > 1) {

			$markerArray['###PREVIOUS###'] = $this->makeLink($this->pi_getLL('prevPage', '', TRUE), $prefix.'-previous', array ('page' => $this->piVars['page'] - 1));

		} else {

			$markerArray['###PREVIOUS###'] = $this->makeLink($this->pi_getLL('prevPage', '', TRUE), $prefix.'-previous');

		}

		// Build page selector.
		$_uniqId = uniqid($prefix.'-');

		$markerArray['###PAGESELECT###'] = '<form action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id).'" class="'.$prefix.'-pageselect" method="get"><div><input type="hidden" name="id" value="'.$GLOBALS['TSFE']->id.'" />';

		foreach ($this->piVars as $piVar => $value) {

			if ($piVar != 'page') {

				$markerArray['###PAGESELECT###'] .= '<input type="hidden" name="'.$this->prefixId.'['.$piVar.']" value="'.$value.'" />';

			}

		}

		$markerArray['###PAGESELECT###'] .= '<label for="'.$_uniqId.'">'.$this->pi_getLL('selectPage', '', TRUE).'</label><select id="'.$_uniqId.'" name="'.$this->prefixId.'[page]" onchange="javascript:this.form.submit();">';

		for ($i = 1; $i <= $this->doc->numPages; $i++) {

			$markerArray['###PAGESELECT###'] .= '<option value="'.$i.'"'.($this->piVars['page'] == $i ? ' selected="selected"' : '').'>['.$i.']'.($this->doc->physicalPages[$i]['label'] ? ' - '.htmlspecialchars($this->doc->physicalPages[$i]['label']) : '').'</option>';

		}

		$markerArray['###PAGESELECT###'] .= '</select></div></form>';

		// Link to next page.
		if ($this->piVars['page'] < $this->doc->numPages) {

			$markerArray['###NEXT###'] = $this->makeLink($this->pi_getLL('nextPage', '', TRUE), $prefix.'-next', array ('page' => $this->piVars['page'] + 1));

		} else {

			$markerArray['###NEXT###'] = $this->makeLink($this->pi_getLL('nextPage', '', TRUE), $prefix.'-next');

		}

		// Link forward X pages.
		if ($this->piVars['page'] <= ($this->doc->numPages - $this->conf['pageStep'])) {

			$markerArray['###FORWARD###'] = $this->makeLink(sprintf($this->pi_getLL('forwardXPages', '', TRUE), $this->conf['pageStep']), $prefix.'-forward', array ('page' => $this->piVars['page'] + $this->conf['pageStep']));

		} else {

			$markerArray['###FORWARD###'] = $this->makeLink(sprintf($this->pi_getLL('forwardXPages', '', TRUE), $this->conf['pageStep']), $prefix.'-forward');

		}

		// Link to last page.
		if ($this->piVars['page'] < $this->doc->numPages) {

			$markerArray['###LAST###'] = $this->makeLink($this->pi_getLL('lastPage', '', TRUE), $prefix.'-last', array ('page' => $this->doc->numPages));

		} else {

			$markerArray['###LAST###'] = $this->makeLink($this->pi_getLL('lastPage', '', TRUE), $prefix.'-last');

		}

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Generates a navigation link
	 *
	 * @access	protected
	 *
	 * @param	string		$label: The link's text
	 * @param	string		$class: The link's class
	 * @param	array		$overrulePIvars: The new set of plugin variables
	 * 						If this is empty no link is generated.
	 *
	 * @return	string		Typolink ready to output
	 */
	protected function makeLink($label, $class = '', array $overrulePIvars = array ()) {

		if ($overrulePIvars) {

			// Merge plugin variables with new set of values.
			if (is_array($this->piVars)) {

				$piVars = $this->piVars;

				unset($piVars['DATA']);

				$overrulePIvars = t3lib_div::array_merge_recursive_overrule($piVars, $overrulePIvars);

			}

			// Build typolink configuration array.
			$conf = array ();

			$conf['useCacheHash'] = 1;

			$conf['parameter'] = $GLOBALS['TSFE']->id.' - '.($class != '' ? $class : '-').' '.$label;

			$conf['additionalParams'] = t3lib_div::implodeArrayForUrl($this->prefixId, $overrulePIvars, '', TRUE, FALSE);

			return $this->cObj->typoLink($label, $conf);

		} else {

			if ($class != '') {

				$class = ' class="'.$class.'"';

			}

			return '<span'.$class.'>'.$label.'</span>';

		}

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/navigation/class.tx_dlf_navigation.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/navigation/class.tx_dlf_navigation.php']);
}

?>