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

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Plugin 'Page Grid' for the 'dlf' extension
 *
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class PageGrid extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/PageGrid.php';

    /**
     * Renders entry for one page of the current document.
     *
     * @access protected
     *
     * @param int $number: The page to render
     * @param string $template: Parsed template subpart
     *
     * @return string The rendered entry ready for output
     */
    protected function getEntry($number, $template)
    {
        // Set current page if applicable.
        if (!empty($this->piVars['page']) && $this->piVars['page'] == $number) {
            $markerArray['###STATE###'] = 'cur';
        } else {
            $markerArray['###STATE###'] = 'no';
        }
        // Set page number.
        $markerArray['###NUMBER###'] = $number;
        // Set pagination.
        $markerArray['###PAGINATION###'] = htmlspecialchars($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['orderlabel']);
        // Get thumbnail or placeholder.
        $fileGrpsThumb = GeneralUtility::trimExplode(',', $this->conf['fileGrpThumbs']);
        if (array_intersect($fileGrpsThumb, array_keys($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'])) !== [] ) {
            while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'][$fileGrpThumb])) {
                    $thumbnailFile = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'][$fileGrpThumb]);
                    break;
                }
            }
        } elseif (!empty($this->conf['placeholder'])) {
            $thumbnailFile = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['placeholder']);
        } else {
            $thumbnailFile = PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($this->extKey)) . 'Resources/Public/Images/PageGridPlaceholder.jpg';
        }
        $thumbnail = '<img alt="' . $markerArray['###PAGINATION###'] . '" src="' . $thumbnailFile . '" />';
        // Get new plugin variables for typolink.
        $piVars = $this->piVars;
        // Unset no longer needed plugin variables.
        // unset($piVars['pagegrid']) is for DFG Viewer compatibility!
        unset($piVars['pointer'], $piVars['DATA'], $piVars['pagegrid']);
        $piVars['page'] = $number;
        $linkConf = [
            'useCacheHash' => 1,
            'parameter' => $this->conf['targetPid'],
            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
            'additionalParams' => GeneralUtility::implodeArrayForUrl($this->prefixId, $piVars, '', true, false),
            'title' => $markerArray['###PAGINATION###']
        ];
        $markerArray['###THUMBNAIL###'] = $this->cObj->typoLink($thumbnail, $linkConf);
        return $this->templateService->substituteMarkerArray($template, $markerArray);
    }

    /**
     * Renders the page browser
     *
     * @access protected
     *
     * @return string The rendered page browser ready for output
     */
    protected function getPageBrowser()
    {
        // Get overall number of pages.
        $maxPages = intval(ceil($this->doc->numPages / $this->conf['limit']));
        // Return empty pagebrowser if there is just one page.
        if ($maxPages < 2) {
            return '';
        }
        // Get separator.
        $separator = htmlspecialchars($this->pi_getLL('separator', ' - '));
        // Add link to previous page.
        if ($this->piVars['pointer'] > 0) {
            $output = $this->pi_linkTP_keepPIvars(htmlspecialchars($this->pi_getLL('prevPage', '<')), ['pointer' => $this->piVars['pointer'] - 1, 'page' => (($this->piVars['pointer'] - 1) * $this->conf['limit']) + 1], true) . $separator;
        } else {
            $output = '<span class="prev-page not-active">' . htmlspecialchars($this->pi_getLL('prevPage', '<')) . '</span>' . $separator;
        }
        $i = 0;
        // Add links to pages.
        while ($i < $maxPages) {
            if ($i < 3 || ($i > $this->piVars['pointer'] - 3 && $i < $this->piVars['pointer'] + 3) || $i > $maxPages - 4) {
                if ($this->piVars['pointer'] != $i) {
                    $output .= $this->pi_linkTP_keepPIvars(htmlspecialchars(sprintf($this->pi_getLL('page', '%d'), $i + 1)), ['pointer' => $i, 'page' => ($i * $this->conf['limit']) + 1], true) . $separator;
                } else {
                    $output .= '<span class="active">' . htmlspecialchars(sprintf($this->pi_getLL('page', '%d'), $i + 1)) . '</span>' . $separator;
                }
                $skip = true;
            } elseif ($skip == true) {
                $output .= '<span class="skipped">' . htmlspecialchars($this->pi_getLL('skip', '...')) . '</span>' . $separator;
                $skip = false;
            }
            $i++;
        }
        // Add link to next page.
        if ($this->piVars['pointer'] < $maxPages - 1) {
            $output .= $this->pi_linkTP_keepPIvars(htmlspecialchars($this->pi_getLL('nextPage', '>')), ['pointer' => $this->piVars['pointer'] + 1, 'page' => ($this->piVars['pointer'] + 1) * $this->conf['limit'] + 1], true);
        } else {
            $output .= '<span class="next-page not-active">' . htmlspecialchars($this->pi_getLL('nextPage', '>')) . '</span>';
        }
        return $output;
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
        $this->loadDocument();
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->conf['fileGrpThumbs'])
        ) {
            // Quit without doing anything if required variables are not set.
            return $content;
        } else {
            // Set default values for page if not set.
            $this->piVars['pointer'] = MathUtility::forceIntegerInRange($this->piVars['pointer'], 0, $this->doc->numPages, 0);
        }
        // Load template file.
        $this->getTemplate();
        $entryTemplate = $this->templateService->getSubpart($this->template, '###ENTRY###');
        if (empty($entryTemplate)) {
            Helper::devLog('No template subpart for list entry found', DEVLOG_SEVERITY_WARNING);
            // Quit without doing anything if required variables are not set.
            return $content;
        }
        if (!empty($this->piVars['logicalPage'])) {
            $this->piVars['page'] = $this->doc->getPhysicalPage($this->piVars['logicalPage']);
            // The logical page parameter should not appear
            unset($this->piVars['logicalPage']);
        }
        // Set some variable defaults.
        // $this->piVars['page'] may be integer or string (physical structure @ID)
        if (
            (int) $this->piVars['page'] > 0
            || empty($this->piVars['page'])
        ) {
            $this->piVars['page'] = MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);
        } else {
            $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);
        }
        if (!empty($this->piVars['page'])) {
            $this->piVars['pointer'] = intval(floor(($this->piVars['page'] - 1) / $this->conf['limit']));
        }
        if (
            !empty($this->piVars['pointer'])
            && (($this->piVars['pointer'] * $this->conf['limit']) + 1) <= $this->doc->numPages
        ) {
            $this->piVars['pointer'] = max(intval($this->piVars['pointer']), 0);
        } else {
            $this->piVars['pointer'] = 0;
        }
        // Iterate through visible page set and display thumbnails.
        for ($i = $this->piVars['pointer'] * $this->conf['limit'], $j = ($this->piVars['pointer'] + 1) * $this->conf['limit']; $i < $j; $i++) {
            // +1 because page counting starts at 1.
            $number = $i + 1;
            if ($number > $this->doc->numPages) {
                break;
            } else {
                $content .= $this->getEntry($number, $entryTemplate);
            }
        }
        // Render page browser.
        $markerArray['###PAGEBROWSER###'] = $this->getPageBrowser();
        // Merge everything with template.
        $content = $this->templateService->substituteMarkerArray($this->templateService->substituteSubpart($this->template, '###ENTRY###', $content, true), $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }
}
