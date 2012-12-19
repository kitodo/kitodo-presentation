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
	 * Holds the current images' URLs
	 *
	 * @var	array
	 * @access protected
	 */
	protected $images = array ();

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

			// Set default values if not set.
			$this->piVars['page'] = t3lib_div::intInRange($this->piVars['page'], 1, $this->doc->numPages, 1);

			$this->piVars['double'] = t3lib_div::intInRange($this->piVars['double'], 0, 1, 0);

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/pageview/template.tmpl'), '###TEMPLATE###');

		}

		// Get image data.
		$this->images[0] = $this->getImageUrl($this->piVars['page']);

		if ($this->piVars['double'] && $this->piVars['page'] < $this->doc->numPages) {

			$this->images[1] = $this->getImageUrl($this->piVars['page'] + 1);

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

		// Load required OpenLayers components.
		$components = array (
			// Map Feature.
			'OpenLayers/BaseTypes.js',
			'OpenLayers/BaseTypes/Class.js',
			'OpenLayers/BaseTypes/Bounds.js',
			'OpenLayers/BaseTypes/Element.js',
			'OpenLayers/BaseTypes/LonLat.js',
			'OpenLayers/BaseTypes/Pixel.js',
			'OpenLayers/BaseTypes/Size.js',
			'OpenLayers/Console.js',
			'OpenLayers/Lang.js',
			'OpenLayers/Util.js',
			'OpenLayers/Lang/'.$this->lang.'.js',
			'OpenLayers/Events.js',
			'OpenLayers/Events/buttonclick.js',
			'OpenLayers/Animation.js',
			'OpenLayers/Tween.js',
			'OpenLayers/Projection.js',
			'OpenLayers/Map.js',
			// Event handlers and controls.
			'OpenLayers/Handler.js',
			'OpenLayers/Handler/Click.js',
			'OpenLayers/Handler/Drag.js',
			'OpenLayers/Handler/Box.js',
			'OpenLayers/Handler/MouseWheel.js',
			'OpenLayers/Control.js',
			'OpenLayers/Control/DragPan.js',
			'OpenLayers/Control/ZoomBox.js',
			'OpenLayers/Control/Navigation.js',
			'OpenLayers/Control/Zoom.js',
			// Image layer.
			'OpenLayers/Tile.js',
			'OpenLayers/Tile/Image.js',
			'OpenLayers/Layer.js',
			'OpenLayers/Layer/Image.js',
		);

		$output[] = '
		<script type="text/javascript">
			window.OpenLayers = ["'.implode('", "', $components).'"];
		</script>';

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
			tx_dlf_viewer = new dlfViewer();
			tx_dlf_viewer.setLang("'.$this->lang.'");
			tx_dlf_viewer.addImage("'.$this->images[0].'");
			'.(!empty($this->images[1]) ? 'tx_dlf_viewer.addImage("'.$this->images[1].'");' : '').'
			tx_dlf_viewer.addControl("zoom");
			tx_dlf_viewer.init("'.$this->conf['elementId'].'");
		</script>';

		return implode("\n", $output);

	}

	/**
	 * Get image's URL
	 *
	 * @access	protected
	 *
	 * @param	integer		$page: Page number
	 *
	 * @return	string		URL of image file
	 */
	protected function getImageUrl($page) {

		$imageUrl = '';

		// Get @USE value of METS fileGrp.
		$fileGrps = t3lib_div::trimExplode(',', $this->conf['fileGrps']);

		$fileGrp = strtolower(array_pop($fileGrps));

		// Get image link and size.
		if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp])) {

			$imageUrl = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp]);

		} else {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_pageview->getImage('.$page.')] File not found in fileGrp "'.$fileGrp.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

		}

		return $imageUrl;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php']);
}

?>