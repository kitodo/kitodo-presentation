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
 * Plugin 'DLF: Audioplayer' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_audioplayer extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/audioplayer/class.tx_dlf_audioplayer.php';

	/**
	 * Holds the current audio file's URL and MIME type
	 *
	 * @var	array
	 * @access protected
	 */
	protected $audio = array ();

	/**
	 * Adds Player javascript
	 *
	 * @access	protected
	 *
	 * @return	string		Player script tags ready for output
	 */
	protected function addPlayerJS() {

		$output = array ();

		// Add jQuery library.
		tx_dlf_helper::loadJQuery();

		$output[] = '<link type="text/css" rel="stylesheet" href="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/jPlayer/blue.monday/css/jplayer.blue.monday.min.css">';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/jPlayer/jquery.jplayer.min.js"></script>';

		$output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/audioplayer/js/tx_dlf_audioplayer.js"></script>';

		// Add player configuration.
		$output[] = '
		<style>
			#tx-dlf-audio { width: 100px; height: 100px };
		</style>
		<script id="tx-dlf-pageview-initViewer" type="text/javascript">
			window.onload = function() {
				tx_dlf_audioplayer = new dlfAudioPlayer({
					audio: {
						mimeType: "' . $this->audio['mimetype'] . '",
						title: "",
						url:  "' . $this->audio['url'] . '"
					},
					parentElId: "tx-dlf-audio",
					swfPath: "'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'lib/jPlayer/jquery.jplayer.swf"
				});
			}
		</script>';

		return implode("\n", $output);

	}

	/**
	 * Get audio's URL and MIME type
	 *
	 * @access	protected
	 *
	 * @param	integer		$page: Page number
	 *
	 * @return	array		URL and MIME type of audio file
	 */
	protected function getAudio($page) {

		// Get audio link.
		if (!empty($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$this->conf['fileGrpAudio']])) {

			$this->audio['url'] = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$this->conf['fileGrpAudio']]);

			$this->audio['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalPagesInfo[$this->doc->physicalPages[$page]]['files'][$this->conf['fileGrpAudio']]);

		} else {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_audioplayer->getAudio('.$page.')] File not found in fileGrp "'.$this->conf['fileGrpAudio'].'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

		}

		return $this->audio;

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

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/audioplayer/template.tmpl'), '###TEMPLATE###');

		}

		// Get audio data.
		$this->audio = $this->getAudio($this->piVars['page']);

		// Fill in the template markers.
		$markerArray = array (
			'###PLAYER_JS###' => $this->addPlayerJS()
		);

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/audioplayer/class.tx_dlf_audioplayer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/audioplayer/class.tx_dlf_audioplayer.php']);
}
