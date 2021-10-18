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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class PageGridController extends AbstractController
{
    public $prefixId = 'tx_dlf';
    public $extKey = 'dlf';

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);

        $this->loadDocument($requestData);
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($extConf['fileGrpThumbs'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            // Set default values for page if not set.
            $requestData['pointer'] = MathUtility::forceIntegerInRange($requestData['pointer'], 0, $this->doc->numPages, 0);
        }

        if (!empty($requestData['logicalPage'])) {
            $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
            // The logical page parameter should not appear
            unset($requestData['logicalPage']);
        }
        // Set some variable defaults.
        // $requestData['page'] may be integer or string (physical structure @ID)
        if (
            (int) $requestData['page'] > 0
            || empty($requestData['page'])
        ) {
            $requestData['page'] = MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
        } else {
            $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
        }
        if (!empty($requestData['page'])) {
            $requestData['pointer'] = (int)(floor(($requestData['page'] - 1) / $this->settings['limit']));
        }
        if (
            !empty($requestData['pointer'])
            && (($requestData['pointer'] * $this->settings['limit']) + 1) <= $this->doc->numPages
        ) {
            $requestData['pointer'] = max($requestData['pointer'], 0);
        } else {
            $requestData['pointer'] = 0;
        }
        $entryArray = [];

        $additionalParams = [
            $this->prefixId . '[pointer]' => $requestData['pointer']
        ];

        // Iterate through visible page set and display thumbnails.
        for ($i = $requestData['pointer'] * $this->settings['limit'], $j = ($requestData['pointer'] + 1) * $this->settings['limit']; $i < $j; $i++) {
            // +1 because page counting starts at 1.
            $number = $i + 1;
            if ($number > $this->doc->numPages) {
                break;
            } else {
                $additionalParams[$this->prefixId . '[page]'] = $number;
                $entryArray[$number] = array_merge($this->getEntry($number, $requestData, $extConf), ['additionalParams' => $additionalParams]);
            }
        }

        $this->view->assign('requestData', $requestData);
        $this->view->assign('pageGridEntries', $entryArray);
        $this->view->assign('pageBrowser', $this->getPageBrowser($requestData));
    }

    protected function pi_getLL($label)
    {
        return $GLOBALS['TSFE']->sL('LLL:EXT:dlf/Resources/Private/Language/PageGrid.xml:' . $label);
    }

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
    protected function getEntry($number, $requestData, $extConf)
    {
        // Set current page if applicable.
        if (!empty($requestData['page']) && $requestData['page'] == $number) {
            $markerArray['STATE'] = 'cur';
        } else {
            $markerArray['STATE'] = 'no';
        }
        // Set page number.
        $markerArray['NUMBER'] = $number;
        // Set pagination.
        $markerArray['PAGINATION'] = htmlspecialchars($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['orderlabel']);
        // Get thumbnail or placeholder.
        $fileGrpsThumb = GeneralUtility::trimExplode(',', $extConf['fileGrpThumbs']);
        if (array_intersect($fileGrpsThumb, array_keys($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'])) !== []) {
            while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'][$fileGrpThumb])) {
                    $thumbnailFile = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'][$fileGrpThumb]);
                    break;
                }
            }
        } elseif (!empty($this->settings['placeholder'])) {
            $thumbnailFile = $GLOBALS['TSFE']->tmpl->getFileName($this->settings['placeholder']);
        } else {
            $thumbnailFile = PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($this->extKey)) . 'Resources/Public/Images/PageGridPlaceholder.jpg';
        }

        $markerArray['THUMBNAIL'] = $thumbnailFile;
        // Get new plugin variables for typolink.
        $piVars = $requestData;
        // Unset no longer needed plugin variables.
        // unset($piVars['pagegrid']) is for DFG Viewer compatibility!
        unset($piVars['pointer'], $piVars['DATA'], $piVars['pagegrid']);
        $piVars['page'] = $number;
        return $markerArray;
    }

    /**
     * Renders the page browser
     *
     * @access protected
     *
     * @return string The rendered page browser ready for output
     */
    protected function getPageBrowser($requestData)
    {
        $outputArray = [];
        // Get overall number of pages.
        $maxPages = (int) ceil($this->doc->numPages / $this->settings['limit']);
        // Return empty pagebrowser if there is just one page.
        if ($maxPages < 2) {
            return '';
        }
        // Get separator.
        $separator = $this->pi_getLL('separator', ' - ');
        // Add link to previous page.
        if ($requestData['pointer'] > 0) {
            $outputArray[0] = [
                'text' => $this->pi_getLL('prevPage', '<') . $separator,
                'additionalParams' => [
                    $this->prefixId.'[pointer]' => $requestData['pointer'] - 1,
                    $this->prefixId.'[page]' => (($requestData['pointer'] - 1) * $this->settings['limit']) + 1
                ],
            ];
        } else {
            $outputArray[0] = [
                'text' => $this->pi_getLL('prevPage', '<'),
                'class' => 'prev-page not-active'
            ];
        }
        $i = 0;
        // Add links to pages.
        while ($i < $maxPages) {
            if ($i < 3 || ($i > $requestData['pointer'] - 3 && $i < $requestData['pointer'] + 3) || $i > $maxPages - 4) {
                if ($requestData['pointer'] != $i) {
                    $outputArray[$i+1] = [
                        'text' => sprintf($this->pi_getLL('page', '%d'), $i + 1) . $separator,
                        'additionalParams' => [
                            $this->prefixId.'[pointer]' => $i,
                            $this->prefixId.'[page]' => ($i * $this->settings['limit']) + 1
                        ],
                    ];
                } else {
                    $outputArray[$i+1] = [
                        'text' => sprintf($this->pi_getLL('page', '%d'), $i + 1) . $separator,
                        'class' => 'active'
                    ];
                }
                $skip = true;
            } elseif ($skip == true) {
                $outputArray[$i+1] = [
                    'text' => $this->pi_getLL('skip', '...') . $separator,
                    'class' => 'skipped'
                ];
                $skip = false;
            }
            $i++;
        }
        // Add link to next page.
        if ($requestData['pointer'] < $maxPages - 1) {
            $outputArray[$i+1] = [
                'text' => $this->pi_getLL('nextPage', '>'),
                'additionalParams' => [
                    $this->prefixId.'[pointer]' => $requestData['pointer'] + 1,
                    $this->prefixId.'[page]' => ($requestData['pointer'] + 1) * $this->settings['limit'] + 1
                ],
            ];
        } else {
            $outputArray[$i+1] = [
                'text' => $this->pi_getLL('nextPage', '>') . $separator,
                'class' => 'next-page not-active'
            ];
        }
        return $outputArray;
    }

}
