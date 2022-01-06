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

class PageGridController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        $this->loadDocument($this->requestData);
        if (
            $this->document === null
            || $this->document->getDoc()->numPages < 1
            || empty($extConf['fileGrpThumbs'])
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        }

        $entryArray = [];

        $numPages = $this->document->getDoc()->numPages;
        // Iterate through visible page set and display thumbnails.
        for ($i = 1; $i < $numPages; $i++) {
            $foundEntry = $this->getEntry($i, $extConf['fileGrpThumbs']);
            $foundEntry['state'] = ($i == $this->requestData['page']) ? 'cur' : 'no';
            $entryArray[] = $foundEntry;
        }

        $this->view->assign('pageGridEntries', $entryArray);
        $this->view->assign('docUid', $this->requestData['id']);
    }

    /**
     * Renders entry for one page of the current document.
     *
     * @access protected
     *
     * @param int $number: The page to render
     * @param string $fileGrpThumbs: the file group(s) of thumbs
     *
     * @return array The rendered entry ready for fluid
     */
    protected function getEntry($number, $fileGrpThumbs)
    {
        // Set pagination.
        $entry['pagination'] = htmlspecialchars($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$number]]['orderlabel']);
        $entry['page'] = $number;
        $entry['thumbnail'] = '';

        // Get thumbnail or placeholder.
        $fileGrpsThumb = GeneralUtility::trimExplode(',', $fileGrpThumbs);
        if (is_array($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$number]]['files'])) {
            if (array_intersect($fileGrpsThumb, array_keys($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$number]]['files'])) !== []) {
                while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                    if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$number]]['files'][$fileGrpThumb])) {
                        $entry['thumbnail'] = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$number]]['files'][$fileGrpThumb]);
                        break;
                    }
                }
            }
        }
        return $entry;
    }
}
