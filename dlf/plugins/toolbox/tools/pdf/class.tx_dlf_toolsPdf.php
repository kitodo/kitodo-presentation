<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
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

		$this->doc->numPages = 1;

		if ($this->conf['pdf'] == '0' && ($this->doc === NULL || $this->doc->numPages < 1 || empty($this->conf['fileGrpDownload']))) {

			// Quit without doing anything if required variables are not set.
			return $content;

		} else {

			// Set default values if not set.
			// page may be integer or string (physical page attribute)
			if ( (int)$this->piVars['page'] > 0 || empty($this->piVars['page'])) {

				$this->piVars['page'] = tx_dlf_helper::intInRange((int)$this->piVars['page'], 1, $this->doc->numPages, 1);

			} else {

				$this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalPages);

			}

			$this->piVars['double'] = tx_dlf_helper::intInRange($this->piVars['double'], 0, 1, 0);

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toolbox/tools/pdf/template.tmpl'), '###TEMPLATE###');

		}

		if($this->conf['pdf']) {
			// Show all PDF Documents

			$markerArray['###PAGE###'] = $this->getAttachments();

			$markerArray['###WORK###'] = '';

			$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

			return $this->pi_wrapInBaseClass($content);

		} else {

			// Get single page downloads.
			$markerArray['###PAGE###'] = $this->getPageLink();

			// Get work download.
			$markerArray['###WORK###'] = $this->getWorkLink();

			$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

			return $this->pi_wrapInBaseClass($content);
		}

	}

	/**
	 * Get PDF document list 
	 * @return html List of attachments
	 */
	protected function getAttachments()
	{

		// Get pdf documents
		// 
		if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[1]]['files'][$this->conf['fileGrpDownload']])) {

			#$pageLink[] = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[2]]['files'][$this->conf['fileGrpDownload']]);

			$documents = '<ul>';

			$i = 1;
			while($this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$i]]['files'][$this->conf['fileGrpDownload']])) {
				// get all pdf documents related to this document
				$link = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$i]]['files'][$this->conf['fileGrpDownload']]);

				$title = $this->doc->physicalPagesInfo[$this->doc->physicalPages[$i]]['files'][$this->conf['fileGrpDownload']];

				$documents .= '<li>'.$this->cObj->typoLink($title, array ('parameter' => $link, 'title' => $title)).'</li>';
				$i++;
			}
			$documents .= '</ul>';

			// $documents[] = $this->cObj->typoLink($this->pi_getLL('leftPage', ''), array ('parameter' => $pageLink[1], 'title' => $this->pi_getLL('leftPage', '')));
			// $documents[] = 'test2';
 
			return $documents;
		} else {
			$xPath = 'mets:fileSec/mets:fileGrp[@USE="'.$this->conf['fileGrpDownload'].'"]/mets:file/mets:FLocat';
			$files = $this->doc->mets->xpath($xPath);

			$pdfHtml = '<ul>';
			foreach ($files as $key => $value) {
				$url = (string) $value->attributes('xlin', true)['href'];
				$regex = '/\/(\w*:\d*)\/datastreams\/(\w*-\d*)/';
				preg_match($regex, $url, $treffer);

				$qucosa = explode(":", $treffer[1]);
				$namespace = $qucosa[0];
				$qid = $qucosa[1];
				$fid = $treffer[2];

				$title = (string) $value->attributes('xlin', true)['title'];
				$pdfHtml .= '<li><a href="/get/file/'.$namespace.'/'.$qid.'/'.$fid.'/">'.$title.'</a></li>';
			}
			$pdfHtml .= '</ul>';


			return $pdfHtml;
		}
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
		$details = $this->doc->physicalPagesInfo[$this->doc->physicalPages[$pageNumber]];
		$file = $details['files'][$this->conf['fileGrpDownload']];
		if (!empty($file)) {
			$page1Link = $this->doc->getFileLocation($file);
		}

		// Get second page, too, if double page view is activated.
		if ($this->piVars['double'] && $pageNumber < $this->doc->numPages) {
			$details = $this->doc->physicalPagesInfo[$this->doc->physicalPages[$pageNumber + 1]];
			$file = $details['files'][$this->conf['fileGrpDownload']];
			if (!empty($file)) {
				$page2Link = $this->doc->getFileLocation($file);
			}
		}

		if (TYPO3_DLOG && empty($page1Link) && empty($page2Link)) {
			t3lib_div::devLog('[tx_dlf_toolsPdf->getPageLink()] ' .
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
		if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[0]]['files'][$this->conf['fileGrpDownload']])) {

			$workLink = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[0]]['files'][$this->conf['fileGrpDownload']]);

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

				t3lib_div::devLog('[tx_dlf_toolsPdf->getWorkLink()] File not found in fileGrp "'.$this->conf['fileGrpDownload'].'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

		}

		return $workLink;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php']);
}

?>
