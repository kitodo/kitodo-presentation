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
 * Tool 'PDF Download' for the plugin 'DLF: Toolbox' of the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_toolsPdf extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php';

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

		// Merge configuration with conf array of toolbox.
		$this->conf = tx_dlf_helper::array_merge_recursive_overrule($this->cObj->data['conf'], $this->conf);

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL || $this->doc->numPages < 1 || empty($this->conf['fileGrpDownload'])) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			if (!empty($this->piVars['logicalPage'])) {

				$this->piVars['page'] = $this->doc->getPhysicalPage($this->piVars['logicalPage']);
				unset($this->piVars['logicalPage']);

			}

			// Set default values if not set.
			// $this->piVars['page'] may be integer or string (physical structure @ID)
			if ( (int)$this->piVars['page'] > 0 || empty($this->piVars['page'])) {

				$this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int)$this->piVars['page'], 1, $this->doc->numPages, 1);

			} else {

				$this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);

			}

			$this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);

		}

		// Load template file.
		if (!empty($this->conf['toolTemplateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['toolTemplateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toolbox/tools/pdf/template.tmpl'), '###TEMPLATE###');

		}

		// Get single page downloads.
		$markerArray['###PAGE###'] = $this->getPageLink();

		// Get work download.
		$markerArray['###WORK###'] = $this->getWorkLink();

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Get page's download link
	 *
	 * @access	protected
	 *
	 * @return	string		Link to downloadable page
	 */
	protected function getPageLink() {

		$page1Link = '';
		$page2Link = '';
		$pageNumber = $this->piVars['page'];

		// Get image link.
		$details = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber]];
		$file = $details['files'][$this->conf['fileGrpDownload']];
		if (!empty($file)) {
			$page1Link = $this->doc->getFileLocation($file);
		}

		// Get second page, too, if double page view is activated.
		if ($this->piVars['double'] && $pageNumber < $this->doc->numPages) {
			$details = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber + 1]];
			$file = $details['files'][$this->conf['fileGrpDownload']];
			if (!empty($file)) {
				$page2Link = $this->doc->getFileLocation($file);
			}
		}

		if (TYPO3_DLOG && empty($page1Link) && empty($page2Link)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_toolsPdf->getPageLink()] ' .
					  'File not found in fileGrp "' .
					  $this->conf['fileGrpDownload'] . '"',
					  $this->extKey,
					  SYSLOG_SEVERITY_WARNING);
		}

		// Wrap URLs with HTML.
		if (!empty($page1Link)) {
			if ($this->piVars['double']) {
				$page1Link = $this->cObj->typoLink($this->pi_getLL('leftPage', ''),
					array('parameter' => $page1Link, 'title' => $this->pi_getLL('leftPage', '')));
			} else {
				$page1Link = $this->cObj->typoLink($this->pi_getLL('singlePage', ''),
					array('parameter' => $page1Link, 'title' => $this->pi_getLL('singlePage', '')));
			}
		}
		if (!empty($page2Link)) {
			$page2Link = $this->cObj->typoLink($this->pi_getLL('rightPage', ''),
				array('parameter' => $page2Link, 'title' => $this->pi_getLL('rightPage', '')));
		}

		return $page1Link . $page2Link;
	}

	/**
	 * Get work's download link
	 *
	 * @access	protected
	 *
	 * @return	string		Link to downloadable work
	 */
	protected function getWorkLink() {

		$workLink = '';

		// Get work link.
		if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[0]]['files'][$this->conf['fileGrpDownload']])) {

			$workLink = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[0]]['files'][$this->conf['fileGrpDownload']]);

		} else {

			$details = $this->doc->getLogicalStructure($this->doc->toplevelId);

			if (!empty($details['files'][$this->conf['fileGrpDownload']])) {

				$workLink = $this->doc->getFileLocation($details['files'][$this->conf['fileGrpDownload']]);

			}

		}

		// Wrap URLs with HTML.
		if (!empty($workLink)) {

			$workLink = $this->cObj->typoLink($this->pi_getLL('work', ''), array ('parameter' => $workLink, 'title' => $this->pi_getLL('work', '')));

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_toolsPdf->getWorkLink()] File not found in fileGrp "'.$this->conf['fileGrpDownload'].'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

		}

		return $workLink;

	}

}
