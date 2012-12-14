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
	 * Holds the current image's information
	 *
	 * @var	array
	 * @access protected
	 */
	protected $image = array (
		'url' => '',
		'width' => 0,
		'height' => 0
	);

	/**
	 * Holds the language code for OpenLayers
	 *
	 * @var	string
	 * @access protected
	 */
	protected $lang = 'en';

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

			// Get image link.
			$fileGrps = t3lib_div::trimExplode(',', $this->conf['fileGrps']);

			$fileGrp = strtolower(array_pop($fileGrps));

			if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$this->piVars['page']]]['files'][$fileGrp])) {

				$this->image['url'] = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$this->piVars['page']]]['files'][$fileGrp]);

				$imageSize = @getimagesize($this->image['url']);

				$this->image['width'] = $imageSize[0];

				$this->image['height'] = $imageSize[1];

			} else {

				if (TYPO3_DLOG) {

					t3lib_div::devLog('[tx_dlf_pageview->main('.$_page.')] File not found: "'.$fileGrpUrl.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

				}

			}

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/pageview/template.tmpl'), '###TEMPLATE###');

		}

		// Fill in the template markers.
		$markerArray = array (
			'###VIEWER_JS###' => $this->addViewerJS()
		);

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Adds OpenLayers javascript
	 *
	 * @access	protected
	 *
	 * @return	string		OpenLayers script tags ready for output.
	 */
	protected function addOpenLayersJS() {

		$output = array ();

		// Get localization for OpenLayers.
		if ($GLOBALS['TSFE']->lang) {

			$langFile = t3lib_extMgm::extPath($this->extKey, 'lib/OpenLayers/lib/OpenLayers/Lang/'.strtolower($GLOBALS['TSFE']->lang).'.js');

			if (file_exists($langFile)) {

				$this->lang = strtolower($GLOBALS['TSFE']->lang);

			}

		}
/*
		// Add OpenLayers configuration.
		$output[] = '
		<script type="text/javascript">
			window.OpenLayers = [
			// Set all required Openlayers files here like this:
			// "OpenLayers/Animation.js",
			// "OpenLayers/BaseTypes/Bounds.js"
			];
		</script>';
*/
		// Add OpenLayers library.
		$output[] = '
		<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'lib/OpenLayers/lib/OpenLayers.js"></script>';

		return implode("\n", $output);

	}

	/**
	 * Adds Viewer javascript
	 *
	 * @access	protected
	 *
	 * @return	string		Viewer script tags ready for output
	 */
	protected function addViewerJS() {

		$output = array ();

		// Add OpenLayers library.
		$output[] = $this->addOpenLayersJS();

		// Add viewer library.
		$output[] = '
		<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_pageview.js"></script>';

		// Add viewer configuration.
		$output[] = '
		<script id="tx-dlf-pageview-initViewer" type="text/javascript">
			dlfViewer = new Viewer();
			dlfViewer.setLang("'.$this->lang.'");
			dlfViewer.setImage("'.$this->image['url'].'", '.$this->image['width'].', '.$this->image['height'].');
			dlfViewer.init("'.$this->conf['elementId'].'");
		</script>';

		return implode("\n", $output);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php']);
}

?>