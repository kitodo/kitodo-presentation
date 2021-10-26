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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ToolboxController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        // Quit without doing anything if required variable is not set.
        if (empty($requestData['id'])) {
            return;
        }

        $requestData['double'] = MathUtility::forceIntegerInRange($requestData['double'], 0, 1, 0);
        $this->view->assign('double', $requestData['double']);

        // Load current document.
        $this->loadDocument($requestData);

        $tools = explode(',', $this->settings['tools']);
        // Add the tools to the toolbox.
        foreach ($tools as $tool) {
            $tool = trim(str_replace('tx_dlf_', '', $tool));
            $this->$tool($requestData);
            $this->view->assign($tool, true);
        }
    }

    /**
     * Renders the annotation tool
     * @param $requestData
     * @return void
     */
    public function annotationtool($requestData)
    {
        if (
            $this->doc === null
            || $this->doc->numPages < 1
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($requestData['logicalPage'])) {
                $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($requestData['logicalPage']);
            }
            // Set default values if not set.
            // $requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $requestData['page'] > 0
                || empty($requestData['page'])
            ) {
                $requestData['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
        }

        $annotationContainers = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$requestData['page']]]['annotationContainers'];
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
     * @param $requestData
     * @return void
     */
    public function fulltextdownloadtool($requestData)
    {
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->extConf['fileGrpFulltext'])
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($requestData['logicalPage'])) {
                $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($requestData['logicalPage']);
            }
            // Set default values if not set.
            // $requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $requestData['page'] > 0
                || empty($requestData['page'])
            ) {
                $requestData['page'] = MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
        }
        // Get text download.
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$requestData['page']]]['files'][$fileGrpFulltext];
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
     * @param $requestData
     * @return void
     */
    public function fulltexttool($requestData)
    {
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->extConf['fileGrpFulltext'])
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($requestData['logicalPage'])) {
                $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($requestData['logicalPage']);
            }
            // Set default values if not set.
            // $requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $requestData['page'] > 0
                || empty($requestData['page'])
            ) {
                $requestData['page'] = MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
        }
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$requestData['page']]]['files'][$fileGrpFulltext];
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
     * @param $requestData
     * @return void
     */
    public function imagedownloadtool($requestData)
    {
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->settings['fileGrpsImageDownload'])
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($requestData['logicalPage'])) {
                $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($requestData['logicalPage']);
            }
            // Set default values if not set.
            // $requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $requestData['page'] > 0
                || empty($requestData['page'])
            ) {
                $requestData['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
        }
        $imageArray = [];
        // Get left or single page download.
        $imageArray[0] = $this->getImage($requestData['page']);
        if ($requestData['double'] == 1) {
            $imageArray[1] = $this->getImage($requestData['page'] + 1);
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
     * @param $requestData
     */
    public function imagemanipulationtool($requestData)
    {
        // Set parent element for initialization.
        $parentContainer = !empty($this->settings['parentContainer']) ? $this->settings['parentContainer'] : '.tx-dlf-imagemanipulationtool';

        $this->view->assign('imageManipulation', true);
        $this->view->assign('parentContainer', $parentContainer);
    }

    /**
     * Renders the PDF download tool
     * @param $requestData
     * @return void
     */
    public function pdfdownloadtool($requestData)
    {
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->extConf['fileGrpDownload'])
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($requestData['logicalPage'])) {
                $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($requestData['logicalPage']);
            }
            // Set default values if not set.
            // $requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $requestData['page'] > 0
                || empty($requestData['page'])
            ) {
                $requestData['page'] = MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
        }
        // Get single page downloads.
        $this->view->assign('pageLinks', $this->getPageLink($requestData));
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
    protected function getPageLink($requestData)
    {
        $page1Link = '';
        $page2Link = '';
        $pageLinkArray = [];
        $pageNumber = $requestData['page'];
        $fileGrpsDownload = GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']);
        // Get image link.
        while ($fileGrpDownload = array_shift($fileGrpsDownload)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber]]['files'][$fileGrpDownload])) {
                $page1Link = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$pageNumber]]['files'][$fileGrpDownload]);
                // Get second page, too, if double page view is activated.
                if (
                    $requestData['double']
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
        if (!empty($workLink)) {
            $workLink = $workLink;
        } else {
            $this->logger->warning('File not found in fileGrps "' . $this->extConf['fileGrpDownload'] . '"');
        }
        return $workLink;
    }

    /**
     * Renders the searchInDocument tool
     * @param $requestData
     * @return void
     */
    public function searchindocumenttool($requestData)
    {
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->extConf['fileGrpFulltext'])
            || empty($this->settings['solrcore'])
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($requestData['logicalPage'])) {
                $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($requestData['logicalPage']);
            }
            // Set default values if not set.
            // $requestData['page'] may be integer or string (physical structure @ID)
            if (
                (int) $requestData['page'] > 0
                || empty($requestData['page'])
            ) {
                $requestData['page'] = MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
        }

        // Quit if no fulltext file is present
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->settings['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$requestData['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$requestData['page']]]['files'][$fileGrpFulltext];
                break;
            }
        }
        if (empty($fullTextFile)) {
            return;
        }

        // Fill markers.
        $viewArray = [
            'ACTION_URL' => $this->getActionUrl(),
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
     * Get the action url for search form
     *
     * @access protected
     *
     * @return string with action url for search form
     */
    protected function getActionUrl()
    {
        // Configure @action URL for form.
        $uri = $this->uriBuilder->reset()
            ->setTargetPageUid($GLOBALS['TSFE']->id)
            ->setCreateAbsoluteUri(true)
            ->build();

        $actionUrl = $uri;

        if (!empty($this->settings['searchUrl'])) {
            $actionUrl = $this->settings['searchUrl'];
        }
        return $actionUrl;
    }

    /**
     * Get current document id
     *
     * @access protected
     *
     * @return string with current document id
     */
    protected function getCurrentDocumentId()
    {
        $id = $this->doc->uid;

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
