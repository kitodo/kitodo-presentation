<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kitodo. Key to digital objects e.V. <contact@kitodo.org>
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
	 * Holds the current images' URLs and MIME types
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
		$output[] = '<link type="text/css" rel="stylesheet" href="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/OpenLayers/ol3.css">';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/OpenLayers/glif.min.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/OpenLayers/ol3-dlf.js"></script>';

		// Add viewer library.
		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_utils.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_ol3.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_ol3_styles.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_ol3_source.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_altoparser.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_pageview_imagemanipulation_control.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_pageview_fulltext_control.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/pageview/tx_dlf_pageview.js"></script>';

		// Add viewer configuration.
		$output[] = '
		<script id="tx-dlf-pageview-initViewer" type="text/javascript">
			window.onload = function() {
				if (dlfUtils.exists(dlfViewer)) {
					tx_dlf_viewer = new dlfViewer({
						controls: ["' . implode('", "', $this->controls) . '"],
						div: "' . $this->conf['elementId'] . '",
						images: ' . json_encode($this->images) . ',
						fulltexts: '. json_encode($this->fulltexts) . '
					})
				}
			}
		</script>';

		return implode("\n", $output);

	}

	protected function addInteraction()
	{
        $markerArray = array();
		if ($this->piVars['id']) {
            $label = $this->pi_getLL('addBasket', '', TRUE);

            if (empty($this->piVars['page'])) {
                $params['page'] = 1;
            }

            if ($this->conf['crop']) {
            	$markerArray['###EDITBUTTON###'] = '<a href="javascript: tx_dlf_viewer.activateSelection();">'.$this->pi_getLL('editMode', '', TRUE).'</a>';
            	$markerArray['###EDITREMOVE###'] = '<a href="javascript: tx_dlf_viewer.resetCropSelection();">'.$this->pi_getLL('editRemove', '', TRUE).'</a>';
            } else {
            	$markerArray['###EDITBUTTON###'] = '';
            	$markerArray['###EDITREMOVE###'] = '';
            }

            if ($this->conf['magnifier']) {
            	$markerArray['###MAGNIFIER###'] = '<a href="javascript: tx_dlf_viewer.activateMagnifier();">'.$this->pi_getLL('magnifier', '', TRUE).'</a>';
            } else {
            	$markerArray['###MAGNIFIER###'] = '';
            }

            if ($this->conf['rotation']) {
            	$markerArray['###ROTATELEFT###'] = '<a href="javascript: tx_dlf_viewer.map.rotate(-90);">'.$this->pi_getLL('rotateleft', '', TRUE).'</a>';
            	$markerArray['###ROTATERIGHT###'] = '<a href="javascript: tx_dlf_viewer.map.rotate(90);">'.$this->pi_getLL('rotateright', '', TRUE).'</a>';
            } else {
            	$markerArray['###ROTATELEFT###'] = '';
            	$markerArray['###ROTATERIGHT###'] = '';
            }

        }

        return $markerArray;
	}

	/**
	 * Get image's URL and MIME type
	 *
	 * @access	protected
	 *
	 * @param	integer		$page: Page number
	 *
	 * @return	array		URL and MIME type of image file
	 */
	protected function getImage($page) {

		$image = array ();

		// Get @USE value of METS fileGrp.
		$fileGrps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['fileGrps']);

		while ($fileGrp = @array_pop($fileGrps)) {

			// Get image link.
			if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp])) {

				$image['url'] = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp]);

				$image['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$fileGrp]);

				break;

			} else {

				if (TYPO3_DLOG) {

					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_pageview->getImage('.$page.')] File not found in fileGrp "'.$fileGrp.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

				}

			}

		}

		return $image;

	}

	/**
	 * Get fulltext URL and MIME type
	 *
	 * @access	protected
	 *
	 * @param	integer		$page: Page number
	 *
	 * @return	array		URL and MIME type of fulltext file
	 */
	protected function getFulltext($page) {

		$fulltext = array ();

		// Get fulltext link.
		if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$this->conf['fileGrpFulltext']])) {

			$fulltext['url'] = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$this->conf['fileGrpFulltext']]);

			// Build typolink configuration array.
			// @TODO change hardcoded path to real typolink configuration
			$fulltext['url'] = '/index.php?eID=tx_dlf_fulltext_eid&url='.$fulltext['url'];

			$fulltext['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$this->conf['fileGrpFulltext']]);

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_pageview->getFulltext('.$page.')] File not found in fileGrp "'.$this->conf['fileGrpFulltext'].'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

		}

		return $fulltext;

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
		$this->images[0] = $this->getImage($this->piVars['page']);
		$this->fulltexts[0] = $this->getFulltext($this->piVars['page']);

		if ($this->piVars['double'] && $this->piVars['page'] < $this->doc->numPages) {

			$this->images[1] = $this->getImage($this->piVars['page'] + 1);
			$this->fulltexts[1] = $this->getFulltext($this->piVars['page'] + 1);

		}

		// Get the controls for the map.
		$this->controls = explode(',', $this->conf['features']);

		// Fill in the template markers.
		$markerArray = array (
			'###VIEWER_JS###' => $this->addViewerJS()
		);

		$markerArray = array_merge($markerArray, $this->addInteraction());

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_pageview.php']);
}
