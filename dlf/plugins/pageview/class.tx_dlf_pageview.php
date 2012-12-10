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
 * Plugin 'DLF: Pageview' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_pageview extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/pageview/class.tx_dlf_pageview.php';

	/**
	 * Adds JavaScript for the viewer
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function addJS() {

		// Add JavaScript files to header.
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_olJS'] = '	<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'plugins/pageview/dlfOL.js"></script>';

		// Add localization file for OpenLayers.
		if ($GLOBALS['TSFE']->lang) {

			$langFile = t3lib_extMgm::siteRelPath($this->extKey).'lib/OpenLayers/lib/OpenLayers/Lang/'.strtolower($GLOBALS['TSFE']->lang).'.js';

			if (file_exists($langFile)) {

				$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_olJS_lang'] = '	<script type="text/javascript" src="'.$langFile.'"></script>';

			}

		}

		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_olJS_viewer'] = '	<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'plugins/pageview/viewer.js"></script>';

		// Set "onbeforeunload" handler on body tag.
//		$GLOBALS['TSFE']->pSetup['bodyTagAdd'] = 'onbeforeunload="javascript:dlfViewer.saveData();"';

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

		// Disable caching for this plugin.
		$this->setCache(FALSE);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL || $this->doc->numPages < 1) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values for page if not set.
			$this->piVars['page'] = t3lib_div::intInRange($this->piVars['page'], 1, $this->doc->numPages, 1);

		}

		$content .= $this->initViewer();

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Initializes the viewer
	 *
	 * @access	protected
	 *
	 * @return	string		Viewer code ready for output
	 */
	protected function initViewer() {

		$this->addJS();

		// Build HTML code.
		$viewer = '<div id="tx-dlf-map"><div id="tx-dlf-lefttarget"></div><div id="tx-dlf-righttarget"></div></div>';

		// !!!ATTENTION PLEASE: THE ID WITHIN THE SCRIPT ELEMENT IS IMPORTANT AND SHOULD STAY IN FUTURE RELEASES!!!
		$viewer .= '
		<script id="tx-dlf-pageview-initViewer" type="text/javascript">
		///* <![CDATA[ */
		dlfViewer = new Viewer();
		dlfViewer.run();
		/* ]]> */
		</script>';

		return $viewer;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php']);
}

?>