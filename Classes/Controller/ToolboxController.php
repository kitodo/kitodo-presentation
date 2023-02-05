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

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller class for plugin 'Toolbox'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ToolboxController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        // Load current document.
        $this->loadDocument($this->requestData);

        $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
        $this->view->assign('double', $this->requestData['double']);

        $tools = explode(',', $this->settings['tools']);
        // Add the tools to the toolbox.
        foreach ($tools as $tool) {
            $tool = trim(str_replace('tx_dlf_', '', $tool));
            $this->$tool();
            $this->view->assign($tool, true);
        }
    }

    /**
     * Renders the annotation tool
     *
     * @return void
     */
    public function annotationtool()
    {
        if ($this->isDocMissingOrEmpty()) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            // Set default values if not set.
            // $this->requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->requestData['page'] > 0
                || empty($this->requestData['page'])
            ) {
                $this->requestData['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            } else {
                $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
            }
        }

        $annotationContainers = $this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$this->requestData['page']]]['annotationContainers'];
        if (
            $annotationContainers != null
            && sizeof($annotationContainers) > 0
        ) {
            $this->view->assign('annotationTool', true);
        } else {
            $this->view->assign('annotationTool', false);
        }
    }

    /**
     * Renders the fulltext download tool
     *
     * @return void
     */
    public function fulltextdownloadtool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpFulltext'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            // Set default values if not set.
            // $this->requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->requestData['page'] > 0
                || empty($this->requestData['page'])
            ) {
                $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            } else {
                $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
            }
        }
        // Get text download.
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext];
                break;
            }
        }
        if (!empty($fullTextFile)) {
            $this->view->assign('fulltextDownload', true);
        } else {
            $this->view->assign('fulltextDownload', false);
        }
    }

    /**
     * Renders the fulltext tool
     *
     * @return void
     */
    public function fulltexttool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpFulltext'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            // Set default values if not set.
            // $this->requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->requestData['page'] > 0
                || empty($this->requestData['page'])
            ) {
                $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            } else {
                $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
            }
        }
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext];
                break;
            }
        }
        if (!empty($fullTextFile)) {
            $this->view->assign('fulltext', true);
            $this->view->assign('activateFullTextInitially', MathUtility::forceIntegerInRange($this->settings['activateFullTextInitially'], 0, 1, 0));
        } else {
            $this->view->assign('fulltext', false);
        }
    }

    /**
     * Renders the image download tool
     *
     * @return void
     */
    public function imagedownloadtool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->settings['fileGrpsImageDownload'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            // Set default values if not set.
            // $this->requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->requestData['page'] > 0
                || empty($this->requestData['page'])
            ) {
                $this->requestData['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            } else {
                $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
            }
        }
        $imageArray = [];
        // Get left or single page download.
        $imageArray[0] = $this->getImage($this->requestData['page']);
        if ($this->requestData['double'] == 1) {
            $imageArray[1] = $this->getImage($this->requestData['page'] + 1);
        }
        $this->view->assign('imageDownload', $imageArray);
    }

    /**
     * Get image's URL and MIME type
     *
     * @access protected
     *
     * @param int $page: Page number
     *
     * @return array Array of image links and image format information
     */
    protected function getImage($page)
    {
        $image = [];
        // Get @USE value of METS fileGrp.
        $fileGrps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->settings['fileGrpsImageDownload']);
        while ($fileGrp = @array_pop($fileGrps)) {
            // Get image link.
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrp])) {
                $image['url'] = $this->document->getDoc()->getDownloadLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrp]);
                $image['mimetype'] = $this->document->getDoc()->getFileMimeType($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrp]);
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
                $image['mimetypeLabel'] = $mimetypeLabel;
                break;
            } else {
                $this->logger->warning('File not found in fileGrp "' . $fileGrp . '"');
            }
        }
        return $image;
    }

    /**
     * Renders the image manipulation tool
     *
     * @return void
     */
    public function imagemanipulationtool()
    {
        // Set parent element for initialization.
        $parentContainer = !empty($this->settings['parentContainer']) ? $this->settings['parentContainer'] : '.tx-dlf-imagemanipulationtool';

        $this->view->assign('imageManipulation', true);
        $this->view->assign('parentContainer', $parentContainer);
    }

    /**
     * Renders the PDF download tool
     *
     * @return void
     */
    public function pdfdownloadtool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpDownload'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            // Set default values if not set.
            // $this->requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->requestData['page'] > 0
                || empty($this->requestData['page'])
            ) {
                $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            } else {
                $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
            }
        }
        // Get single page downloads.
        $this->view->assign('pageLinks', $this->getPageLink());
        // Get work download.
        $this->view->assign('workLink', $this->getWorkLink());
    }

    /**
     * Get page's download link
     *
     * @access protected
     *
     * @return array Link to downloadable page
     */
    protected function getPageLink()
    {
        $page1Link = '';
        $page2Link = '';
        $pageLinkArray = [];
        $pageNumber = $this->requestData['page'];
        $fileGrpsDownload = GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']);
        // Get image link.
        while ($fileGrpDownload = array_shift($fileGrpsDownload)) {
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$pageNumber]]['files'][$fileGrpDownload])) {
                $page1Link = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$pageNumber]]['files'][$fileGrpDownload]);
                // Get second page, too, if double page view is activated.
                if (
                    $this->requestData['double']
                    && $pageNumber < $this->document->getDoc()->numPages
                    && !empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$pageNumber + 1]]['files'][$fileGrpDownload])
                ) {
                    $page2Link = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$pageNumber + 1]]['files'][$fileGrpDownload]);
                }
                break;
            }
        }
        if (
            empty($page1Link)
            && empty($page2Link)
        ) {
            $this->logger->warning('File not found in fileGrps "' . $this->extConf['fileGrpDownload'] . '"');
        }

        if (!empty($page1Link)) {
            $pageLinkArray[0] = $page1Link;
        }
        if (!empty($page2Link)) {
            $pageLinkArray[1] = $page2Link;
        }
        return $pageLinkArray;
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
        $fileGrpsDownload = GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']);
        // Get work link.
        while ($fileGrpDownload = array_shift($fileGrpsDownload)) {
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[0]]['files'][$fileGrpDownload])) {
                $workLink = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[0]]['files'][$fileGrpDownload]);
                break;
            } else {
                $details = $this->document->getDoc()->getLogicalStructure($this->document->getDoc()->toplevelId);
                if (!empty($details['files'][$fileGrpDownload])) {
                    $workLink = $this->document->getDoc()->getFileLocation($details['files'][$fileGrpDownload]);
                    break;
                }
            }
        }
        if (!empty($workLink)) {
            $workLink = $workLink;
        } else {
            $this->logger->warning('File not found in fileGrps "' . $this->extConf['fileGrpDownload'] . '"');
        }
        return $workLink;
    }

    /**
     * Renders the searchInDocument tool
     *
     * @return void
     */
    public function searchindocumenttool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpFulltext'])
            || empty($this->settings['solrcore'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            // Set default values if not set.
            // $this->requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $this->requestData['page'] > 0
                || empty($this->requestData['page'])
            ) {
                $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            } else {
                $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
            }
        }

        // Quit if no fulltext file is present
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext];
                break;
            }
        }
        if (empty($fullTextFile)) {
            return;
        }

        // Fill markers.
        $viewArray = [
            'LABEL_QUERY_URL' => $this->settings['queryInputName'],
            'LABEL_START' => $this->settings['startInputName'],
            'LABEL_ID' => $this->settings['idInputName'],
            'LABEL_PAGE_URL' => $this->settings['pageInputName'],
            'LABEL_HIGHLIGHT_WORD' => $this->settings['highlightWordInputName'],
            'LABEL_ENCRYPTED' => $this->settings['encryptedInputName'],
            'CURRENT_DOCUMENT' => $this->getCurrentDocumentId(),
            'SOLR_ENCRYPTED' => $this->getEncryptedCoreName() ? : ''
        ];

        $this->view->assign('searchInDocument', $viewArray);
    }

    /**
     * Get current document id. As default the uid will be used.
     * In case there is defined documentIdUrlSchema then the id will
     * extracted from this URL.
     *
     * @access protected
     *
     * @return string with current document id
     */
    protected function getCurrentDocumentId()
    {
        $id = $this->document->getUid();

        if ($id !== null && $id > 0) {
            // we found the document uid
            return (string) $id;
        } else {
            $id = $this->requestData['id'];
            if (!GeneralUtility::isValidUrl($id)) {
                // we found no valid URI --> something unexpected we cannot search within.
                return '';
            }
        }

        // example: https://host.de/items/*id*/record
        if (!empty($this->settings['documentIdUrlSchema'])) {
            $arr = explode('*', $this->settings['documentIdUrlSchema']);

            if (count($arr) == 2) {
                $id = explode($arr[0], $id)[0];
            } else if (count($arr) == 3) {
                $sub = substr($id, strpos($id, $arr[0]) + strlen($arr[0]), strlen($id));
                $id = substr($sub, 0, strpos($sub, $arr[2]));
            }
        }
        return $id;
    }

    /**
     * Get the encrypted Solr core name
     *
     * @access protected
     *
     * @return string with encrypted core name
     */
    protected function getEncryptedCoreName()
    {
        // Get core name.
        $name = Helper::getIndexNameFromUid($this->settings['solrcore'], 'tx_dlf_solrcores');
        // Encrypt core name.
        if (!empty($name)) {
            $name = Helper::encrypt($name);
        }
        return $name;
    }
}
