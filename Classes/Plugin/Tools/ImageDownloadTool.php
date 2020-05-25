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

/**
 * Image Download tool for the plugin 'Toolbox' of the 'dlf' extension
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ImageDownloadTool extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Tools/ImageDownloadTool.php';

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
            || empty($this->conf['fileGrpsImageDownload'])
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
                $this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);
            } else {
                $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);
            }
            $this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);
        }
        // Load template file.
        $this->getTemplate();
        // Get left or single page download.
        $markerArray['###IMAGE_LEFT###'] = $this->piVars['double'] == 1 ? $this->getImage($this->piVars['page'], $this->pi_getLL('leftPage', '')) : $this->getImage($this->piVars['page'], $this->pi_getLL('singlePage', ''));
        // Get right page download.
        $markerArray['###IMAGE_RIGHT###'] = $this->piVars['double'] == 1 ? $this->getImage($this->piVars['page'] + 1, $this->pi_getLL('rightPage', '')) : '';
        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Get image's URL and MIME type
     *
     * @access protected
     *
     * @param int $page: Page number
     * @param string $label: Link title and label
     *
     * @return string Link to image file with given label
     */
    protected function getImage($page, $label)
    {
        $image = [];
        // Get @USE value of METS fileGrp.
        $fileGrps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['fileGrpsImageDownload']);
        while ($fileGrp = @array_pop($fileGrps)) {
            // Get image link.
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp])) {
                $image['url'] = $this->doc->getDownloadLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp]);
                $image['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp]);
                switch ($image['mimetype']) {
                    case 'image/jpeg':
                        $mimetypeLabel = ' (JPG)';
                        break;
                    case 'image/tiff':
                        $mimetypeLabel = ' (TIFF)';
                        break;
                    default:
                        $mimetypeLabel = '';
                }
                $linkConf = [
                    'parameter' => $image['url'],
                    'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                    'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                    'title' => $label . $mimetypeLabel,
                    'additionalParams' => '',
                ];
                $imageLink = $this->cObj->typoLink($label . $mimetypeLabel, $linkConf);
                break;
            } else {
                Helper::devLog('File not found in fileGrp "' . $fileGrp . '"', DEVLOG_SEVERITY_WARNING);
            }
        }
        return $imageLink;
    }
}
