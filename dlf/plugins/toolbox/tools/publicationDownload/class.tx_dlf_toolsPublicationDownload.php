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
class tx_dlf_toolsPublicationDownload extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toolbox/tools/pdf/class.tx_dlf_toolsPublicationDownload.php';

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

		if ($this->doc === NULL || empty($this->conf['fileGrpDownload'])) {

			// Quit without doing anything if required variables are not set.
			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toolbox/tools/publicationDownload/template.tmpl'), '###TEMPLATE###');

		}

		$subpartArray['downloads'] = $this->cObj->getSubpart($this->template, '###DOWNLOADS###');

		// Show all PDF Documents
		$attachments = $this->getAttachments();

		$content = '';

		if (is_array($attachments)) {

			foreach ($attachments as $id => $file) {

				$conf = array(
					'useCacheHash' => 0,
					'parameter' => $this->conf['apiPid'],
					'additionalParams' => '&tx_dpf[qid]=' . $this->doc->recordId . '&tx_dpf[action]=attachment' . '&tx_dpf[attachment]=' . $file['ID'],
					'forceAbsoluteUrl' => TRUE
				);

				$title = $file['TITLE'] ? $file['TITLE'] : $file['ID'];

				// replace uid with URI to dpf API
				$markerArray['###FILE###'] = $this->cObj->typoLink($title, $conf);


				$content .= $this->cObj->substituteMarkerArray($subpartArray['downloads'], $markerArray);

			}

		}

		return $this->cObj->substituteSubpart($this->template, '###DOWNLOADS###', $content, TRUE);

	}

	/**
	 * Get PDF document list
	 * @return html List of attachments
	 */
	protected function getAttachments() {

		// Get pdf documents
		//
		$xPath = 'mets:fileSec/mets:fileGrp[@USE="'.$this->conf['fileGrpDownload'].'"]';

		$files = $this->doc->mets->xpath($xPath);

		foreach ($files as $key => $value) {

			$file = $value->xpath('mets:file')[0];

			$singleFile = array();

			foreach ($file->attributes() as $attribute => $value) {

				$singleFile[$attribute] = $value;

			}

			$attachments[] = $singleFile;
		}

			return $attachments;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/tools/pdf/class.tx_dlf_toolsPublicationDownload.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/toolbox/tools/pdf/class.tx_dlf_toolsPublicationDownload.php']);
}
