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

namespace Kitodo\Dlf\Plugin\Tools;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * PDF Download tool for the plugin 'Toolbox' of the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class PdfDownloadTool extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Tools/PdfDownloadTool.php';

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
        // Merge configuration with conf array of toolbox.
        if (!empty($this->cObj->data['conf'])) {
            $this->conf = Helper::mergeRecursiveWithOverrule($this->cObj->data['conf'], $this->conf);
        }
        // Load current document.
        $this->loadDocument();
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->conf['fileGrpDownload'])
        ) {
            // Quit without doing anything if required variables are not set.
            return $content;
        } else {
            if (!empty($this->piVars['logicalPage'])) {
                $this->piVars['page'] = $this->doc->getPhysicalPage($this->piVars['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->piVars['logicalPage']);
            }
            // Set default values if not set.
            // $this->piVars['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->piVars['page'] > 0
                || empty($this->piVars['page'])
            ) {
                $this->piVars['page'] = MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);
            } else {
                $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);
            }
            $this->piVars['double'] = MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);
        }
        // Load template file.
        $this->getTemplate();
        // Get single page downloads.
        $markerArray['###PAGE###'] = $this->getPageLink();
        // Get work download.
        $markerArray['###WORK###'] = $this->getWorkLink();
        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Get page's download link
     *
     * @access protected
     *
     * @return string Link to downloadable page
     */
    protected function getPageLink()
    {
        $page1Link = '';
        $page2Link = '';
        $pageNumber = $this->piVars['page'];
        $fileGrpDownloads = GeneralUtility::trimExplode(',', $this->conf['fileGrpDownload']);
        // Get image link.
        while ($fileGrpDownload = array_shift($fileGrpDownloads)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber]]['files'][$fileGrpDownload])) {
                $page1Link = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber]]['files'][$fileGrpDownload]);
                // Get second page, too, if double page view is activated.
                if (
                    $this->piVars['double']
                    && $pageNumber < $this->doc->numPages
                    && !empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber + 1]]['files'][$fileGrpDownload])
                ) {
                    $page2Link = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber + 1]]['files'][$fileGrpDownload]);
                }
                break;
            }
        }
        if (
            empty($page1Link)
            && empty($page2Link)
        ) {
            Helper::devLog('File not found in fileGrps "' . $this->conf['fileGrpDownload'] . '"', DEVLOG_SEVERITY_WARNING);
        }
        // Wrap URLs with HTML.
        $linkConf = [
            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http']
        ];
        if (!empty($page1Link)) {
            $linkConf['parameter'] = $page1Link;
            if ($this->piVars['double']) {
                $linkConf['title'] = $this->pi_getLL('leftPage', '');
                $page1Link = $this->cObj->typoLink($this->pi_getLL('leftPage', ''), $linkConf);
            } else {
                $linkConf['title'] = $this->pi_getLL('singlePage', '');
                $page1Link = $this->cObj->typoLink($this->pi_getLL('singlePage', ''), $linkConf);
            }
        }
        if (!empty($page2Link)) {
            $linkConf['parameter'] = $page2Link;
            $linkConf['title'] = $this->pi_getLL('rightPage', '');
            $page2Link = $this->cObj->typoLink($this->pi_getLL('rightPage', ''), $linkConf);
        }
        return $page1Link . $page2Link;
    }

    /**
     * Get work's download link
     *
     * @access protected
     *
     * @return string Link to downloadable work
     */
    protected function getWorkLink()
    {
        $workLink = '';
        $fileGrpDownloads = GeneralUtility::trimExplode(',', $this->conf['fileGrpDownload']);
        // Get work link.
        while ($fileGrpDownload = array_shift($fileGrpDownloads)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[0]]['files'][$fileGrpDownload])) {
                $workLink = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[0]]['files'][$fileGrpDownload]);
                break;
            } else {
                $details = $this->doc->getLogicalStructure($this->doc->toplevelId);
                if (!empty($details['files'][$fileGrpDownload])) {
                    $workLink = $this->doc->getFileLocation($details['files'][$fileGrpDownload]);
                    break;
                }
            }
        }
        // Wrap URLs with HTML.
        if (!empty($workLink)) {
            $linkConf = [
                'parameter' => $workLink,
                'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                'title' => $this->pi_getLL('work', '')
            ];
            $workLink = $this->cObj->typoLink($this->pi_getLL('work', ''), $linkConf);
        } else {
            Helper::devLog('File not found in fileGrp "' . $this->conf['fileGrpDownload'] . '"', DEVLOG_SEVERITY_WARNING);
        }
        return $workLink;
    }
}
