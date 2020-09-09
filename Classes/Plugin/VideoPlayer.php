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

namespace Kitodo\Dlf\Plugin;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Plugin VideoPlayer for the 'dlf' extension
 *
 * @author Erik Konrad <erik.konrad@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class VideoPlayer extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/VideoPlayer.php';

    /**
     * Holds the current audio file's URL, MIME type and optional label
     *
     * @var array
     * @access protected
     */
    protected $video = [];

    /**
     * Adds Player javascript
     *
     * @access protected
     *
     * @return void
     */
    protected function addPlayerJS()
    {
        //Javascript files.
        $jsFiles = [
            // jPlayer
            'Resources/Public/Javascript/jPlayer/jquery.jplayer.min.js',
            'Resources/Public/Javascript/VideoPlayer/VideoPlayer.min.js'
        ];
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        foreach ($jsFiles as $jsFile) {
            $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->extKey)) . $jsFile);
        }

    }

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Load current document.
        $this->loadDocument();

        if (
            $this->doc === null
            || $this->doc->numPages < 1
        ) {
            // Quit without doing anything if required variables are not set.
            return $content;
        } else {
            // Set default values if not set.
            // $this->piVars['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->piVars['page'] > 0
                || empty($this->piVars['page'])
            ) {
                $this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);
            } else {
                $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);
            }
            $this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);
        }

        // Check if there are any audio files available.
        if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpVideo']])) {
            // Get audio data.
            $this->video['url'] = urldecode($this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpVideo']]));
            $this->video['label'] = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['label'];
            $this->video['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpVideo']]);
            $this->video['metadata'] = $this->doc->getMetadata('LOG_0000');
            // Add jPlayer javascript.

            $this->addPlayerJS();
        } else {
            // Quit without doing anything if required variables are not set.
            return $content;
        }

        $data = [
            'video' => $this->video,
            'config' => [
                'speed' => $conf['config.']['speedoptions.'] ? $conf['config.']['speedoptions.'] : false,
                'screenshotFields' => $conf['config.']['screenshotFields']
            ]
        ];

        return $this->pi_wrapInBaseClass($this->generateContentWithFluidStandaloneView($data));
    }
}
