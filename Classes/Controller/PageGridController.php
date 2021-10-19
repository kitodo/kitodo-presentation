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
        }

        $entryArray = [];

        $numPages = $this->doc->numPages;
        // Iterate through visible page set and display thumbnails.
        for ($i = 0; $i < $numPages; $i++) {
            $foundEntry = $this->getEntry($i, $extConf['fileGrpThumbs']);
            $foundEntry['state'] = ($i == $requestData['page']) ? 'cur' : 'no';
            $entryArray[] = $foundEntry;
        }

        $this->view->assign('pageGridEntries', $entryArray);
        $this->view->assign('docUid', $requestData['id']);
    }

    /**
     * Renders entry for one page of the current document.
     *
     * @access protected
     *
     * @param int $number: The page to render
     * @param string $fileGrpThumbs: the file group of thumbs
     *
     * @return array The rendered entry ready for fluid
     */
    protected function getEntry($number, $fileGrpThumbs)
    {
        // Set pagination.
        $entry['pagination'] = htmlspecialchars($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['orderlabel']);
        $entry['page'] = $number;
        $entry['thumbnail'] = '';

        // Get thumbnail or placeholder.
        $fileGrpsThumb = GeneralUtility::trimExplode(',', $fileGrpThumbs);
        if (array_intersect($fileGrpsThumb, array_keys($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'])) !== []) {
            while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'][$fileGrpThumb])) {
                    $entry['thumbnail'] = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$number]]['files'][$fileGrpThumb]);
                    break;
                }
            }
        }

        return $entry;
    }

}
