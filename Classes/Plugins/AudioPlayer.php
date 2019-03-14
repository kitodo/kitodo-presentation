<?php
namespace Kitodo\Dlf\Plugins;

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
 * Plugin AudioPlayer for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	dlf
 * @access	public
 */
class AudioPlayer extends \Kitodo\Dlf\Common\AbstractPlugin {

    public $scriptRelPath = 'Classes/Plugins/AudioPlayer.php';

    /**
     * Holds the current audio file's URL, MIME type and optional label
     *
     * @var	array
     * @access protected
     */
    protected $audio = [];

    /**
     * Adds Player javascript
     *
     * @access	protected
     *
     * @return	string		Player script tags ready for output
     */
    protected function addPlayerJS() {

        $output = [];

        $output[] = '<link type="text/css" rel="stylesheet" href="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/jPlayer/blue.monday/css/jplayer.blue.monday.min.css">';

        $output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/jPlayer/jquery.jplayer.min.js"></script>';

        $output[] = '<script type="text/javascript" src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/AudioPlayer/AudioPlayer.js"></script>';

        // Add player configuration.
        $output[] = '
		<style>
			#tx-dlf-audio { width: 100px; height: 100px };
		</style>
		<script id="tx-dlf-audioplayer-initViewer" type="text/javascript">
			$(document).ready(function() {
				AudioPlayer = new dlfAudioPlayer({
					audio: {
						mimeType: "' . $this->audio['mimetype'].'",
						title: "' . $this->audio['label'].'",
						url:  "' . $this->audio['url'].'"
					},
					parentElId: "tx-dlf-audio",
					swfPath: "'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/jPlayer/jquery.jplayer.swf"
				});
			});
		</script>';

        return implode("\n", $output);

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
            // $this->piVars['page'] may be integer or string (physical structure @ID)
            if ((int) $this->piVars['page'] > 0 || empty($this->piVars['page'])) {

                $this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);

            } else {

                $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);

            }

            $this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);

        }

        // Check if there are any audio files available.
        if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpAudio']])) {

            // Get audio data.
            $this->audio['url'] = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpAudio']]);

            $this->audio['label'] = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['label'];

            $this->audio['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpAudio']]);

        } else {

            // Quit without doing anything if required variables are not set.
            return $content;

        }

        // Load template file.
        $this->getTemplate();

        // Fill in the template markers.
        $markerArray = [
            '###PLAYER_JS###' => $this->addPlayerJS()
        ];

        $content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

        return $this->pi_wrapInBaseClass($content);

    }

}
