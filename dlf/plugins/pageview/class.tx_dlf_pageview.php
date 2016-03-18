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
 * Plugin 'DLF: Pageview' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_pageview extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/pageview/class.tx_dlf_pageview.php';

	/**
	 * Holds the controls to add to the map
	 *
	 * @var	array
	 * @access protected
	 */
	protected $controls = array ();

	/**
	 * Flag if fulltexts are present
	 *
	 * @var	boolean
	 * @access protected
	 */
	protected $hasFulltexts = false;

	/**
	 * Holds the current images' URLs
	 *
	 * @var	array
	 * @access protected
	 */
	protected $images = array ();

	/**
	 * Holds the current fulltexts' URLs
	 *
	 * @var	array
	 * @access protected
	 */
	protected $fulltexts = array ();

	/**
	 * Holds the language code for OpenLayers
	 *
	 * @var	string
	 * @access protected
	 */
	protected $lang = 'en';

	/**
	 * Adds OpenLayers javascript
	 *
	 * @access	protected
	 *
	 * @return	string		OpenLayers script tags ready for output.
	 */
	protected function addOpenLayersJS() {

		$output = array ();

		// Add OpenLayers library.
		$output[] = '
		<link type="text/css" rel="stylesheet" href="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/OpenLayers/ol3.css">
		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/OL3/ol-debug.js"></script>';
//		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/OpenLayers/ol3-dlf.js"></script>';

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

		// Add jQuery library.
		tx_dlf_helper::loadJQuery();

		// Add OpenLayers library.
		$output[] = $this->addOpenLayersJS();

		// Add viewer library.
		$output[] = '
		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_utils.js"></script>
		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_ol3.js"></script>
		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_altoparser.js"></script>
		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_pageview_imagemanipulation_control.js"></script>
		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_pageview_fulltext_control.js"></script>
		<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_pageview.js"></script>';

		// Add viewer configuration.
		$output[] = '
		<script id="tx-dlf-pageview-initViewer" type="text/javascript">
			window.onload = function() {
				if (dlfUtils.exists(dlfViewer)) {
					tx_dlf_viewer = new dlfViewer({
						controls: ["' . implode('", "', $this->controls) . '"],
						div: "' . $this->conf['elementId'] . '",
						fulltexts: ["' . implode('", "', $this->fulltexts) . '"],
						images: ["' . implode('", "', $this->images) . '"]
					})
				}
			}
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
		$fileGrps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['fileGrps']);

		while ($fileGrp = @array_pop($fileGrps)) {

			// Get image link.
			if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp])) {

				$imageUrl = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp]);

				break;

			} else {

				if (TYPO3_DLOG) {

					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_pageview->getImageUrl('.$page.')] File not found in fileGrp "'.$fileGrp.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

				}

			}

		}

		return $imageUrl;

	}

	/**
	 * Get ALTO XML URL
	 *
	 * @access	protected
	 *
	 * @param	integer		$page: Page number
	 *
	 * @return	string		URL of image file
	 */
	protected function getAltoUrl($page) {

		// Get @USE value of METS fileGrp.

		// we need USE="FULLTEXT"
		$fileGrpFulltext = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['fileGrpFulltext']);

		while ($fileGrpFulltext = @array_pop($fileGrpFulltext)) {

			// Get fulltext link.
			if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrpFulltext])) {

				$fulltextUrl = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrpFulltext]);

				// Build typolink configuration array.
				$fulltextUrl =  '/index.php?eID=tx_dlf_fulltext_eid&url='. $fulltextUrl;

				$this->hasFulltexts = true;

				break;

			} else {

				if (TYPO3_DLOG) {

					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_pageview->getImageUrl('.$page.')] File not found in fileGrp "'.$fileGrp.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

				}

			}

		}

		return $fulltextUrl;

	}

	/**
	 * Get map controls
	 *
	 * @access	protected
	 *
	 * @return	array		Array of control keywords
	 */
	protected function getMapControls() {

		$controls = explode(',', $this->conf['features']);

		// Sanitize input.
		foreach ($controls as $key => $control) {

			if (empty($this->controlDependency[$control])) {

				unset ($controls[$key]);

			}

		}

		return $controls;

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

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL || $this->doc->numPages < 1) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values if not set.
			// page may be integer or string (physical page attribute)
			if ( (int)$this->piVars['page'] > 0 || empty($this->piVars['page'])) {

				$this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int)$this->piVars['page'], 1, $this->doc->numPages, 1);

			} else {

				$this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalPages);

			}

			$this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/pageview/template.tmpl'), '###TEMPLATE###');

		}

		// Get image data.
		$this->images[0] = $this->getImageUrl($this->piVars['page']);
		$this->fulltexts[0] = $this->getAltoUrl($this->piVars['page']);

		if ($this->piVars['double'] && $this->piVars['page'] < $this->doc->numPages) {

			$this->images[1] = $this->getImageUrl($this->piVars['page'] + 1);
			$this->fulltexts[1] = $this->getAltoUrl($this->piVars['page'] + 1);

		}

		// Get the controls for the map.
		$this->controls = $this->getMapControls();

		// Fill in the template markers.
		$markerArray = array (
			'###VIEWER_JS###' => $this->addViewerJS()
		);

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php']);
}
