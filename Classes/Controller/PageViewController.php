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

use Kitodo\Dlf\Common\DocumentAnnotation;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use Kitodo\Dlf\Common\MetsDocument;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Ubl\Iiif\Presentation\Common\Model\Resources\CanvasInterface;
use Ubl\Iiif\Presentation\Common\Model\Resources\ManifestInterface;
use Ubl\Iiif\Presentation\Common\Vocabulary\Motivation;

/**
 * Controller class for the plugin 'Page View'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class PageViewController extends AbstractController
{
    /**
     * @access protected
     * @var array Holds the controls to add to the map
     */
    protected array $controls = [];

    /**
     * @access protected
     * @var array Holds the current images' URLs and MIME types
     */
    protected array $images = [];

    /**
     * Holds the current scores' URL, MIME types and the
     * id of the current page
     *
     * @var array
     * @access protected
     */
    protected $scores = [];

    /**
     * @var array
     * @access protected
     */
    protected $measures = [];

    /**
     * Holds the current fulltexts' URLs
     *
     * @var array
     * @access protected
     * @var array Holds the current full texts' URLs
     */
    protected array $fulltexts = [];

    /**
     * Holds the current AnnotationLists / AnnotationPages
     *
     * @access protected
     * @var array Holds the current AnnotationLists / AnnotationPages
     */
    protected array $annotationContainers = [];


    /**
     * Holds the verovio relevant annotations
     *
     * @var array
     */
    protected $verovioAnnotations = [];

    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        // Load current document.
        $this->loadDocument();

        if ($this->isDocMissingOrEmpty()) {
            // if document is empty and current document type is a multi document type then redirect to multi view
            if (!$this->isDocMissing() &&
                $this->isMultiDocumentType($this->document->getCurrentDocument()->tableOfContents[0]['type'])) {
                return $this->multiviewRedirect();
            }
            return $this->htmlResponse();
        } elseif (array_key_exists('multiViewSource', $this->requestData)) {
            // if request data contains multiViewSource parameter then redirect to multi view
            return $this->multiviewRedirect();
        }

        $this->setPage();

        $page = $this->requestData['page'] ?? 0;

        $this->view->assign('viewData', $this->viewData);
        $this->view->assign('forceAbsoluteUrl', $this->extConf['general']['forceAbsoluteUrl'] ?? 0);
        $this->view->assign('docId', $this->requestData['id']);
        $this->view->assign('page', $page);

        // Get the controls for the map.
        $this->controls = explode(',', $this->settings['features'] ?? '');
        $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);

        $documentAnnotation = DocumentAnnotation::getInstance($this->document);
        $this->verovioAnnotations = $documentAnnotation->getVerovioRelevantAnnotations();

        // Get image data.
        $this->images[0] = $this->getImage($page);
        $this->fulltexts[0] = $this->getFulltext($page);
        $this->annotationContainers[0] = $this->getAnnotationContainers($page);
        if ($this->requestData['double'] && $page < $this->document->getCurrentDocument()->numPages) {
            $this->images[1] = $this->getImage($page + 1);
            $this->fulltexts[1] = $this->getFulltext($page + 1);
            $this->annotationContainers[1] = $this->getAnnotationContainers($page + 1);
        }

        $this->scores = $this->getScore($page);
        $this->measures = $this->getMeasures($page);


        $this->view->assign('images', $this->images);
        $this->addViewerJS();

        return $this->htmlResponse();
    }

    /**
     * Converts either measure into page or page into measure
     * @param $document
     * @param $measure
     * @param $page
     * @return false|int|mixed|string|null
     */
    public function convertMeasureOrPage($document, $measure = null, $page = null)
    {
        $return = null;
        $measure2Page = array_column($document->musicalStructure, 'page');
        if ($measure) {
            $return = $measure2Page[$measure];
        } elseif ($page) {
            $return = array_search($page, $measure2Page);
        }

        return $return;
    }

    /**
     * Get all measures from musical struct
     * @param int $page
     * @param ?MetsDocument $specificDoc
     * @param int|null $docNumber
     * @return array
     */
    protected function getMeasures(int $page, ?MetsDocument $specificDoc = null, ?int $docNumber = null): array
    {
        if ($specificDoc) {
            $doc = $specificDoc;
        } else {
            $doc = $this->document->getCurrentDocument();
        }

        $measureCoordsFromCurrentSite = [];
        $measureCounterToMeasureId = [];
        $measureLinks = [];
        if (array_key_exists($page, $doc->physicalStructure)) {
            $currentPhysId = $doc->physicalStructure[$page];
            $defaultFileId = $doc->physicalStructureInfo[$currentPhysId]['files']['DEFAULT'] ?? null;
            if ($doc instanceof MetsDocument) {
                if (isset($defaultFileId)) {
                    $musicalStruct = $doc->musicalStructureInfo;

                    $i = 0;
                    foreach ($musicalStruct as $measureData) {
                        if (isset($measureData['files'])
                            && $defaultFileId == $measureData['files']['DEFAULT']['fileid']) {
                            $measureCoordsFromCurrentSite[$measureData['files']['SCORE']['begin']] = $measureData['files']['DEFAULT']['coords'];
                            $measureCounterToMeasureId[$i] = $measureData['files']['SCORE']['begin'];

                            if ($specificDoc) {
                                // build link for each measure
                                $params = [
                                    'tx_dlf' => $this->requestData,
                                    'tx_dlf[docMeasure][' . $docNumber . ']' => $i
                                ];
                            } else {
                                // build link for each measure
                                $params = [
                                    'tx_dlf' => $this->requestData,
                                    'tx_dlf[measure]' => $i
                                ];
                            }
                            $uriBuilder = $this->uriBuilder;
                            $uri = $uriBuilder
                                ->setArguments($params)
                                ->setArgumentPrefix('tx_dlf')
                                ->uriFor('main');
                            $measureLinks[$measureData['files']['SCORE']['begin']] = $uri;

                        }
                        $i++;
                    }
                }
            }
        }
        return [
            'measureCoordsCurrentSite' => $measureCoordsFromCurrentSite,
            'measureCounterToMeasureId' => $measureCounterToMeasureId,
            'measureLinks' => $measureLinks
        ];
    }

    /**
     * Get score URL and MIME type
     *
     * @access protected
     *
     * @param int $page: Page number
     * @param ?MetsDocument $specificDoc
     *
     * @return array URL and MIME type of fulltext file
     */
    protected function getScore(int $page, ?MetsDocument $specificDoc = null)
    {
        $score = [];
        $loc = '';
        if ($specificDoc) {
            $doc = $specificDoc;
        } else {
            $doc = $this->document->getCurrentDocument();
        }
        if ($doc instanceof MetsDocument) {
            $useGroups = $this->useGroupsConfiguration->getScore();

            if (array_key_exists($page, $doc->physicalStructure)) {
                $pageId = $doc->physicalStructure[$page];
                $files = $doc->physicalStructureInfo[$pageId]['files'] ?? [];

                foreach ($useGroups as $useGroup) {
                    if (isset($files[$useGroup])) {
                        $loc = $files[$useGroup];
                        break;
                    }
                }

                if (!empty($loc)) {
                    $score['mimetype'] = $doc->getFileMimeType($loc);
                    $score['pagebeginning'] = $doc->getPageBeginning($pageId, $loc);
                    $score['url'] = $doc->getFileLocation($loc);
                    if ($this->settings['useInternalProxy']) {
                        // Configure @action URL for form.
                        $uri = $this->uriBuilder->reset()
                            ->setTargetPageUid($this->pageUid)
                            ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? true : false)
                            ->setArguments(
                                [
                                    'eID' => 'tx_dlf_pageview_proxy',
                                    'url' => $score['url'],
                                    'uHash' => GeneralUtility::hmac($score['url'], 'PageViewProxy')
                                ]
                            )
                            ->build();

                        $score['url'] = $uri;
                    }
                }
            }
        }

        if (empty($score)) {
            $this->logger->notice('No score file found for page "' . $page . '" in fileGrps "' . ($this->extConf['files']['useGroupsScore'] ?? '') . '"');
        }
        return $score;
    }

    /**
     * Get fulltext URL and MIME type
     *
     * @access protected
     *
     * @param int $page Page number
     *
     * @return array URL and MIME type of fulltext file
     */
    protected function getFulltext(int $page): array
    {
        $fulltext = [];
        // Get fulltext link.
        $useGroups = $this->useGroupsConfiguration->getFulltext();
        if (array_key_exists($page, $this->document->getCurrentDocument()->physicalStructure)) {
            $physicalStructureInfo = $this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$page]];
            $files = $physicalStructureInfo['files'];
            while ($useGroup = array_shift($useGroups)) {
                if (!empty($files[$useGroup])) {
                    $file = $this->document->getCurrentDocument()->getFileInfo($files[$useGroup]);
                    $fulltext['url'] = $file['location'];
                    if ($this->settings['useInternalProxy']) {
                        $this->configureProxyUrl($fulltext['url']);
                    }
                    $fulltext['mimetype'] = $file['mimeType'];
                    break;
                } else {
                    $this->logger->notice('No full-text file found for page "' . $page . '" in fileGrp "' . $useGroup . '"');
                }
            }
        }

        if (empty($fulltext)) {
            $this->logger->notice('No full-text file found for page "' . $page . '" in fileGrps "' . ($this->extConf['files']['useGroupsFulltext'] ?? '') . '"');
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
    protected function addViewerJS(): void
    {
        $currentMeasureId = '';
        $docPage = 0;

        if (isset($this->requestData['page'])) {
            $docPage = $this->requestData['page'];
        }

        $docMeasures = $this->getMeasures($docPage);
        if (isset($this->requestData['measure'])
            && isset($docMeasures['measureCounterToMeasureId'][$this->requestData['measure']])) {
            $currentMeasureId = $docMeasures['measureCounterToMeasureId'][$this->requestData['measure']];
        }

        $viewer = [
            'controls' => $this->controls,
            'div' => $this->settings['elementId'] ?? 'tx-dlf-map',
            'progressElementId' => $this->settings['progressElementId'] ?? 'tx-dlf-page-progress',
            'images' => $this->images,
            'fulltexts' => $this->fulltexts,
            'score' => $this->scores,
            'annotationContainers' => $this->annotationContainers,
            'measureCoords' => $docMeasures['measureCoordsCurrentSite'],
            'useInternalProxy' => $this->settings['useInternalProxy'],
            'verovioAnnotations' => $this->verovioAnnotations,
            'currentMeasureId' => $currentMeasureId,
            'measureIdLinks' => $docMeasures['measureLinks']
        ];

        // Viewer configuration.
        $viewerConfiguration = '$(document).ready(function() {
                    if (dlfUtils.exists(dlfViewer)) {
                        tx_dlf_viewer = new dlfViewer(' . json_encode($viewer) . ');
                    }
                });';

        $this->view->assign('viewerConfiguration', $viewerConfiguration);
    }

    /**
     * Get all AnnotationPages / AnnotationLists that contain text Annotations with motivation "painting"
     *
     * @access protected
     *
     * @param int $page Page number
     * @return array An array containing the IRIs of the AnnotationLists / AnnotationPages as well as some information about the canvas.
     */
    protected function getAnnotationContainers(int $page): array
    {
        if ($this->document->getCurrentDocument() instanceof IiifManifest) {
            $canvasId = $this->document->getCurrentDocument()->physicalStructure[$page];
            $iiif = $this->document->getCurrentDocument()->getIiif();
            if ($iiif instanceof ManifestInterface) {
                $canvas = $iiif->getContainedResourceById($canvasId);
                /** @var CanvasInterface $canvas */
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
                    return [
                        'canvas' => [
                            'id' => $canvas->getId(),
                            'width' => $canvas->getWidth(),
                            'height' => $canvas->getHeight(),
                        ],
                        'annotationContainers' => $annotationContainers
                    ];
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
     * @param int $page Page number
     * @param ?MetsDocument $specificDoc
     *
     * @return array URL and MIME type of image file
     */
    protected function getImage(int $page, ?MetsDocument $specificDoc = null): array
    {
        $image = [];
        // Get @USE value of METS fileGrp.
        $useGroups = $this->useGroupsConfiguration->getImage();
        // Reverse the order of the image groups
        // e.g. `MAX` is used first when configuration order is `DEFAULT,MAX`
        $useGroups = array_reverse($useGroups);
        foreach ($useGroups as $useGroup) {
            // Get file info for the specific page and file group
            $file = $this->fetchFileInfo($page, $useGroup, $specificDoc);

            if ($file && Helper::filterFilesByMimeType($file, ['image'], true, 'mimeType')) {
                $image['url'] = $file['location'];
                $image['mimetype'] = $file['mimeType'];

                // Only deliver static images via the internal PageViewProxy.
                // (For IIP and IIIF, the viewer needs to build and access a separate metadata URL, see `getMetadataURL` in `OLSources.js`.)
                if ($this->settings['useInternalProxy'] && !Helper::filterFilesByMimeType($image, ['application'], ['IIIF', 'IIP', 'ZOOMIFY'])) {
                    $this->configureProxyUrl($image['url']);
                }
                break;
            } else {
                $this->logger->notice('No image file found for page "' . $page . '" in fileGrp "' . $useGroup . '"');
            }
        }

        if (empty($image)) {
            $this->logger->warning('No image file found for page "' . $page . '" in fileGrps "' . ($this->extConf['files']['useGroupsImage'] ?? '') . '"');
        }
        return $image;
    }

    /**
     * Fetch file info for a specific page and file group.
     *
     * @param int $page Page number
     * @param string $fileGrpImages File group
     * @param ?MetsDocument $specificDoc Optional specific document
     *
     * @return array|null File info array or null if not found
     */
    private function fetchFileInfo(int $page, string $fileGrpImages, ?MetsDocument $specificDoc): ?array
    {
        // Get the physical structure info for the specified page
        if ($specificDoc) {
            $physicalStructureInfo = $specificDoc->physicalStructureInfo[$specificDoc->physicalStructure[$page]];
        } else {
            if (array_key_exists($page, $this->document->getCurrentDocument()->physicalStructure)) {
                $physicalStructureInfo = $this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$page]];
            }
        }

        // Get the files for the specified file group
        $files = $physicalStructureInfo['files'] ?? null;
        if ($files && !empty($files[$fileGrpImages])) {
            // Get the file info for the specified file group
            if ($specificDoc) {
                return $specificDoc->getFileInfo($files[$fileGrpImages]);
            } else {
                return $this->document->getCurrentDocument()->getFileInfo($files[$fileGrpImages]);
            }
        }

        return null;
    }

    /**
     * Redirect to multiview.
     *
     * @access private
     *
     * @return ResponseInterface
     */
    private function multiviewRedirect(): ResponseInterface
    {
        $arguments = [
            'tx_dlf[id]' => $this->requestData['id'],
            'tx_dlf[page]' => $this->requestData['page'],
            'tx_dlf[multiview]' => 1
        ];

        if (array_key_exists('multiViewSource', $this->requestData)) {
            $arguments['tx_dlf[multiViewSource]'] = $this->requestData['multiViewSource'];
        }

        $uri = $this->uriBuilder
            ->reset()
            ->setArguments($arguments)
            ->build();
        return new RedirectResponse($this->addBaseUriIfNecessary($uri), 308);
    }
}
