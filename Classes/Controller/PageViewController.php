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

use Kitodo\Dlf\Common\IiifManifest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Database\Connection;
use Ubl\Iiif\Presentation\Common\Model\Resources\ManifestInterface;
use Ubl\Iiif\Presentation\Common\Vocabulary\Motivation;

/**
 * Controller class for the plugin 'Page View'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class PageViewController extends AbstractController
{
    /**
     * Holds the controls to add to the map
     *
     * @var array
     * @access protected
     */
    protected $controls = [];

    /**
     * Holds the current images' URLs and MIME types
     *
     * @var array
     * @access protected
     */
    protected $images = [];

    /**
     * Holds the current fulltexts' URLs
     *
     * @var array
     * @access protected
     */
    protected $fulltexts = [];

    /**
     * Holds the current AnnotationLists / AnnotationPages
     *
     * @var array
     * @access protected
     */
    protected $annotationContainers = [];

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissingOrEmpty()) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            $this->setPage();
            $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
        }
        // Get image data.
        $this->images[0] = $this->getImage($this->requestData['page']);
        $this->fulltexts[0] = $this->getFulltext($this->requestData['page']);
        $this->annotationContainers[0] = $this->getAnnotationContainers($this->requestData['page']);
        if ($this->requestData['double'] && $this->requestData['page'] < $this->document->getDoc()->numPages) {
            $this->images[1] = $this->getImage($this->requestData['page'] + 1);
            $this->fulltexts[1] = $this->getFulltext($this->requestData['page'] + 1);
            $this->annotationContainers[1] = $this->getAnnotationContainers($this->requestData['page'] + 1);
        }

        // Get the controls for the map.
        $this->controls = explode(',', $this->settings['features']);

        $this->view->assign('forceAbsoluteUrl', $this->settings['forceAbsoluteUrl']);

        $this->addViewerJS();

        $this->view->assign('images', $this->images);
        $this->view->assign('docId', $this->requestData['id']);
        $this->view->assign('page', $this->requestData['page']);
    }

    /**
     * Get fulltext URL and MIME type
     *
     * @access protected
     *
     * @param int $page: Page number
     *
     * @return array URL and MIME type of fulltext file
     */
    protected function getFulltext($page)
    {
        $fulltext = [];
        // Get fulltext link.
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrpFulltext])) {
                $fulltext['url'] = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrpFulltext]);
                if ($this->settings['useInternalProxy']) {
                    $this->configureProxyUrl($fulltext['url']);
                }
                $fulltext['mimetype'] = $this->document->getDoc()->getFileMimeType($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrpFulltext]);
                break;
            } else {
                $this->logger->notice('No full-text file found for page "' . $page . '" in fileGrp "' . $fileGrpFulltext . '"');
            }
        }
        if (empty($fulltext)) {
            $this->logger->notice('No full-text file found for page "' . $page . '" in fileGrps "' . $this->extConf['fileGrpFulltext'] . '"');
        }
        return $fulltext;
    }

    /**
     * Adds Viewer javascript
     *
     * @access protected
     *
     * @return void
     */
    protected function addViewerJS()
    {
        // Viewer configuration.
        $viewerConfiguration = '$(document).ready(function() {
                if (dlfUtils.exists(dlfViewer)) {
                    tx_dlf_viewer = new dlfViewer({
                        controls: ["' . implode('", "', $this->controls) . '"],
                        div: "' . $this->settings['elementId'] . '",
                        progressElementId: "' . $this->settings['progressElementId'] . '",
                        images: ' . json_encode($this->images) . ',
                        fulltexts: ' . json_encode($this->fulltexts) . ',
                        annotationContainers: ' . json_encode($this->annotationContainers) . ',
                        useInternalProxy: ' . ($this->settings['useInternalProxy'] ? 1 : 0) . '
                    });
                }
            });';
        $this->view->assign('viewerConfiguration', $viewerConfiguration);
    }

    /**
     * Get all AnnotationPages / AnnotationLists that contain text Annotations with motivation "painting"
     *
     * @access protected
     *
     * @param int $page: Page number
     * @return array An array containing the IRIs of the AnnotationLists / AnnotationPages as well as
     *               some information about the canvas.
     */
    protected function getAnnotationContainers($page)
    {
        if ($this->document->getDoc() instanceof IiifManifest) {
            $canvasId = $this->document->getDoc()->physicalStructure[$page];
            $iiif = $this->document->getDoc()->getIiif();
            if ($iiif instanceof ManifestInterface) {
                $canvas = $iiif->getContainedResourceById($canvasId);
                /* @var $canvas \Ubl\Iiif\Presentation\Common\Model\Resources\CanvasInterface */
                if ($canvas != null && !empty($canvas->getPossibleTextAnnotationContainers(Motivation::PAINTING))) {
                    $annotationContainers = [];
                    /*
                     *  TODO Analyzing the annotations on the server side requires loading the annotation lists / pages
                     *  just to determine whether they contain text annotations for painting. This will take time and lead to a bad user experience.
                     *  It would be better to link every annotation and analyze the data on the client side.
                     *
                     *  On the other hand, server connections are potentially better than client connections. Downloading annotation lists
                     */
                    foreach ($canvas->getPossibleTextAnnotationContainers(Motivation::PAINTING) as $annotationContainer) {
                        if (($textAnnotations = $annotationContainer->getTextAnnotations(Motivation::PAINTING)) != null) {
                            foreach ($textAnnotations as $annotation) {
                                if (
                                    $annotation->getBody()->getFormat() == 'text/plain'
                                    && $annotation->getBody()->getChars() != null
                                ) {
                                    $annotationListData = [];
                                    $annotationListData['uri'] = $annotationContainer->getId();
                                    $annotationListData['label'] = $annotationContainer->getLabelForDisplay();
                                    $annotationContainers[] = $annotationListData;
                                    break;
                                }
                            }
                        }
                    }
                    $result = [
                        'canvas' => [
                            'id' => $canvas->getId(),
                            'width' => $canvas->getWidth(),
                            'height' => $canvas->getHeight(),
                        ],
                        'annotationContainers' => $annotationContainers
                    ];
                    return $result;
                }
            }
        }
        return [];
    }

    /**
     * Get image's URL and MIME type
     *
     * @access protected
     *
     * @param int $page: Page number
     *
     * @return array URL and MIME type of image file
     */
    protected function getImage($page)
    {
        $image = [];
        // Get @USE value of METS fileGrp.
        $fileGrpsImages = GeneralUtility::trimExplode(',', $this->extConf['fileGrpImages']);
        while ($fileGrpImages = array_pop($fileGrpsImages)) {
            // Get image link.
            if (!empty($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrpImages])) {
                $image['url'] = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrpImages]);
                $image['mimetype'] = $this->document->getDoc()->getFileMimeType($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$page]]['files'][$fileGrpImages]);

                // Only deliver static images via the internal PageViewProxy.
                // (For IIP and IIIF, the viewer needs to build and access a separate metadata URL, see `getMetdadataURL` in `OLSources.js`.)
                if ($this->settings['useInternalProxy'] && !str_contains(strtolower($image['mimetype']), 'application')) {
                    $this->configureProxyUrl($image['url']);
                }
                break;
            } else {
                $this->logger->notice('No image file found for page "' . $page . '" in fileGrp "' . $fileGrpImages . '"');
            }
        }
        if (empty($image)) {
            $this->logger->warning('No image file found for page "' . $page . '" in fileGrps "' . $this->extConf['fileGrpImages'] . '"');
        }
        return $image;
    }
}
