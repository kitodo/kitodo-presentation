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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use Ubl\Iiif\Presentation\Common\Model\Resources\ManifestInterface;
use Ubl\Iiif\Presentation\Common\Vocabulary\Motivation;

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
        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        // Load current document.
        $this->loadDocument($requestData);
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
            if ((int) $requestData['page'] > 0 || empty($requestData['page'])) {
                $requestData['page'] = MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
            $requestData['double'] = MathUtility::forceIntegerInRange($requestData['double'], 0, 1, 0);
        }
        // Get image data.
        $this->images[0] = $this->getImage($requestData['page']);
        $this->fulltexts[0] = $this->getFulltext($requestData['page']);
        $this->annotationContainers[0] = $this->getAnnotationContainers($requestData['page']);
        if ($requestData['double'] && $requestData['page'] < $this->doc->numPages) {
            $this->images[1] = $this->getImage($requestData['page'] + 1);
            $this->fulltexts[1] = $this->getFulltext($requestData['page'] + 1);
            $this->annotationContainers[1] = $this->getAnnotationContainers($requestData['page'] + 1);
        }

        // Get the controls for the map.
        $this->controls = explode(',', $this->settings['features']);

        $this->view->assign('forceAbsoluteUrl', $this->settings['forceAbsoluteUrl']);

        $this->addViewerJS();

        $this->view->assign('images', $this->images);
        $this->view->assign('docId', $requestData['id']);
        $this->view->assign('page', $requestData['page']);
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
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrpFulltext])) {
                $fulltext['url'] = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrpFulltext]);
                if ($this->settings['useInternalProxy']) {
                    // Configure @action URL for form.
                    $uri = $this->uriBuilder->reset()
                        ->setTargetPageUid($GLOBALS['TSFE']->id)
                        ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? true : false)
                        ->setArguments(['eID' => 'tx_dlf_pageview_proxy', 'url' => urlencode($fulltext['url'])])
                        ->build();

                    $fulltext['url'] = $uri;
                }
                $fulltext['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrpFulltext]);
                break;
            } else {
                $this->logger->notice('No full-text file found for page "' . $page . '" in fileGrp "' . $fileGrpFulltext . '"');
            }
        }
        if (empty($fulltext)) {
            $this->logger->notice('No full-text file found for page "' . $page . '" in fileGrps "' . $this->settings['fileGrpFulltext'] . '"');
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
        $viewerConfiguration = '<script>
            $(document).ready(function() {
                if (dlfUtils.exists(dlfViewer)) {
                    tx_dlf_viewer = new dlfViewer({
                        controls: ["' . implode('", "', $this->controls) . '"],
                        div: "' . $this->settings['elementId'] . '",
                        images: ' . json_encode($this->images) . ',
                        fulltexts: ' . json_encode($this->fulltexts) . ',
                        annotationContainers: ' . json_encode($this->annotationContainers) . ',
                        useInternalProxy: ' . ($this->settings['useInternalProxy'] ? 1 : 0) . '
                    });
                }
            });
        </script>';
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
        if ($this->doc instanceof IiifManifest) {
            $canvasId = $this->doc->physicalStructure[$page];
            $iiif = $this->doc->getIiif();
            if ($iiif instanceof ManifestInterface) {
                $canvas = $iiif->getContainedResourceById($canvasId);
                /* @var $canvas \Ubl\Iiif\Presentation\Common\Model\Resources\CanvasInterface */
                if ($canvas != null && !empty($canvas->getPossibleTextAnnotationContainers(Motivation::PAINTING))) {
                    $annotationContainers = [];
                    /*
                     *  TODO Analyzing the annotations on the server side requires loading the annotation lists / pages
                     *  just to determine wether they contain text annotations for painting. This will take time and lead to a bad user experience.
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
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrpImages])) {
                $image['url'] = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrpImages]);
                if ($this->settings['useInternalProxy']) {
                    // Configure @action URL for form.
                    $uri = $this->uriBuilder->reset()
                        ->setTargetPageUid($GLOBALS['TSFE']->id)
                        ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? true : false)
                        ->setArguments(['eID' => 'tx_dlf_pageview_proxy', 'url' => urlencode($image['url'])])
                        ->build();
                    $image['url'] = $uri;
                }
                $image['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrpImages]);
                break;
            } else {
                $this->logger->notice('No image file found for page "' . $page . '" in fileGrp "' . $fileGrpImages . '"');
            }
        }
        if (empty($image)) {
            $this->logger->warning('No image file found for page "' . $page . '" in fileGrps "' . $this->settings['fileGrpImages'] . '"');
        }
        return $image;
    }
}
