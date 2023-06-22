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
     * This holds the current document
     *
     * @var \Kitodo\Dlf\Common\Doc
     * @access private
     */
    private $doc;

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        // Load current document.
        $this->loadDocument();

        $this->view->assign('double', $this->requestData['double']);

        if (!$this->isDocMissingOrEmpty()) {
            $this->doc = $this->document->getDoc();
        }

        $this->renderTool();
    }

    /**
     * Renders tool in the toolbox.
     *
     * @access private
     *
     * @return void
     */
    private function renderTool() {
        if (!empty($this->settings['tool'])) {
            switch ($this->settings['tool']) {
                case 'tx_dlf_annotationtool':
                case 'annotationtool':
                    $this->renderToolByName('renderAnnotationTool');
                    break;
                case 'tx_dlf_fulltextdownloadtool':
                case 'fulltextdownloadtool':
                    $this->renderToolByName('renderFulltextDownloadTool');
                    break;
                case 'tx_dlf_fulltexttool':
                case 'fulltexttool':
                    $this->renderToolByName('renderFulltextTool');
                    break;
                case 'tx_dlf_imagedownloadtool':
                case 'imagedownloadtool':
                    $this->renderToolByName('renderImageDownloadTool');
                    break;
                case 'tx_dlf_imagemanipulationtool':
                case 'imagemanipulationtool':
                    $this->renderToolByName('renderImageManipulationTool');
                    break;
                case 'tx_dlf_pdfdownloadtool':
                case 'pdfdownloadtool':
                    $this->renderToolByName('renderPdfDownloadTool');
                    break;
                case 'tx_dlf_searchindocumenttool':
                case 'searchindocumenttool':
                    $this->renderToolByName('renderSearchInDocumentTool');
                    break;
                default:
                    $this->logger->warning('Incorrect tool configuration: "' . $this->settings['tool'] . '". This tool does not exist.');
            }
        }
    }

    /**
     * Renders tool by the name in the toolbox.
     *
     * @access private
     *
     * @return void
     */
    private function renderToolByName(string $tool) {
        $this->$tool();
        $this->view->assign($tool, true);
    }

    /**
     * Renders the annotation tool
     *
     * @access private
     *
     * @return void
     */
    private function renderAnnotationTool()
    {
        if ($this->isDocMissingOrEmpty()) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $this->setPage();

        $annotationContainers = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->requestData['page']]]['annotationContainers'];
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
     * @access private
     *
     * @return void
     */
    private function renderFulltextDownloadTool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpFulltext'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $this->setPage();

        // Get text download.
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext];
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
     * @access private
     *
     * @return void
     */
    private function renderFulltextTool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpFulltext'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $this->setPage();

        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext];
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
     * @access private
     *
     * @return void
     */
    private function renderImageDownloadTool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->settings['fileGrpsImageDownload'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $this->setPage();

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
     * @access private
     *
     * @param int $page: Page number
     *
     * @return array Array of image links and image format information
     */
    private function getImage($page)
    {
        $image = [];
        // Get @USE value of METS fileGrp.
        $fileGrps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->settings['fileGrpsImageDownload']);
        while ($fileGrp = @array_pop($fileGrps)) {
            // Get image link.
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp])) {
                $image['url'] = $this->doc->getDownloadLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp]);
                $image['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp]);
                switch ($image['mimetype']) {
                    case 'image/jpeg':
                        $image['mimetypeLabel']  = ' (JPG)';
                        break;
                    case 'image/tiff':
                        $image['mimetypeLabel']  = ' (TIFF)';
                        break;
                    default:
                        $image['mimetypeLabel']  = '';
                }
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
     * @access private
     *
     * @return void
     */
    private function renderImageManipulationTool()
    {
        // Set parent element for initialization.
        $parentContainer = !empty($this->settings['parentContainer']) ? $this->settings['parentContainer'] : '.tx-dlf-imagemanipulationtool';

        $this->view->assign('imageManipulation', true);
        $this->view->assign('parentContainer', $parentContainer);
    }

    /**
     * Renders the PDF download tool
     *
     * @access private
     *
     * @return void
     */
    private function renderPdfDownloadTool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpDownload'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $this->setPage();

        // Get single page downloads.
        $this->view->assign('pageLinks', $this->getPageLink());
        // Get work download.
        $this->view->assign('workLink', $this->getWorkLink());
    }

    /**
     * Get page's download link
     *
     * @access private
     *
     * @return array Link to downloadable page
     */
    private function getPageLink()
    {
        $page1Link = '';
        $page2Link = '';
        $pageLinkArray = [];
        $pageNumber = $this->requestData['page'];
        $fileGrpsDownload = GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']);
        // Get image link.
        while ($fileGrpDownload = array_shift($fileGrpsDownload)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber]]['files'][$fileGrpDownload])) {
                $page1Link = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber]]['files'][$fileGrpDownload]);
                // Get second page, too, if double page view is activated.
                if (
                    $this->requestData['double']
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
     * @access private
     *
     * @return string Link to downloadable work
     */
    private function getWorkLink()
    {
        $workLink = '';
        $fileGrpsDownload = GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']);
        // Get work link.
        while ($fileGrpDownload = array_shift($fileGrpsDownload)) {
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
        if (empty($workLink)) {
            $this->logger->warning('File not found in fileGrps "' . $this->extConf['fileGrpDownload'] . '"');
        }
        return $workLink;
    }

    /**
     * Renders the searchInDocument tool
     *
     * @access private
     *
     * @return void
     */
    private function renderSearchInDocumentTool()
    {
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->extConf['fileGrpFulltext'])
            || empty($this->settings['solrcore'])
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $this->setPage();

        // Quit if no fulltext file is present
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->requestData['page']]]['files'][$fileGrpFulltext];
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
     * @access private
     *
     * @return string with current document id
     */
    private function getCurrentDocumentId()
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
     * @access private
     *
     * @return string with encrypted core name
     */
    private function getEncryptedCoreName()
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
