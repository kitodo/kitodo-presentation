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

namespace Kitodo\Dlf\Controller;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Controller class for the plugin 'AudioPlayer'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class AudioplayerController extends AbstractController
{
    /**
     * Holds the current audio file's URL, MIME type and optional label
     *
     * @access protected
     * @var array Holds the current audio file's URL, MIME type and optional label
     */
    protected array $audio = [];

    /**
     * Adds Player javascript
     *
     * @access protected
     *
     * @return void
     */
    protected function addPlayerJS(): void
    {
        // Inline CSS.
        $inlineCSS = '#tx-dlf-audio { width: 100px; height: 100px; }';

        // AudioPlayer configuration.
        $audioPlayerConfiguration = '
            $(document).ready(function() {
                AudioPlayer = new dlfAudioPlayer({
                    audio: {
                        mimeType: "' . $this->audio['mimetype'] . '",
                        title: "' . $this->audio['label'] . '",
                        url:  "' . $this->audio['url'] . '"
                    },
                    parentElId: "tx-dlf-audio",
                    swfPath: "' . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('dlf')) . 'Resources/Public/JavaScript/jPlayer/jquery.jplayer.swf"
                });
            });
        ';

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssInlineBlock('kitodo-audioplayer-configuration', $inlineCSS);
        $pageRenderer->addJsFooterInlineCode('kitodo-audioplayer-configuration', $audioPlayerConfiguration);
    }

    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return void
     */
    public function mainAction(): void
    {
        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissingOrEmpty()) {
            // Quit without doing anything if required variables are not set.
            return;
        }

        $this->setDefaultPage();

        // Check if there are any audio files available.
        $fileGrpsAudio = GeneralUtility::trimExplode(',', $this->extConf['files']['fileGrpAudio']);
        while ($fileGrpAudio = array_shift($fileGrpsAudio)) {
            $physicalStructureInfo = $this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$this->requestData['page']]];
            $fileId = $physicalStructureInfo['files'][$fileGrpAudio];
            if (!empty($fileId)) {
                // Get audio data.
                $file = $this->document->getCurrentDocument()->getFileInfo($fileId);
                $this->audio['url'] = $file['location'];
                $this->audio['label'] = $physicalStructureInfo['label'];
                $this->audio['mimetype'] = $file['mimeType'];
                break;
            }
        }

        if (!empty($this->audio)) {
            // Add jPlayer javascript.
            $this->addPlayerJS();
        }
    }
}
