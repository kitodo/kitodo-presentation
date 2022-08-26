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
     * The main method of the plugin
     *
     * @access public
     *
     * @return void
     */
    public function mainAction(): void
    {
        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissingOrEmpty()) {
            // Quit without doing anything if required variables are not set.
            return;
        }

        $this->setPage();

        // Get image data.
        $this->images[0] = $this->getImage($this->requestData['page']);
        $this->fulltexts[0] = $this->getFulltext($this->requestData['page']);
        $this->annotationContainers[0] = $this->getAnnotationContainers($this->requestData['page']);
        if ($this->requestData['double'] && $this->requestData['page'] < $this->document->getCurrentDocument()->numPages) {
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
     * @param int $page Page number
     *
     * @return array URL and MIME type of fulltext file
     */
    protected function getFulltext(int $page): array
    {
        $fulltext = [];
        // Get fulltext link.
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            $physicalStructureInfo = $this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$page]];
            $fileId = $physicalStructureInfo['files'][$fileGrpFulltext];
            if (!empty($fileId)) {
                $file = $this->document->getCurrentDocument()->getFileInfo($fileId);
                $fulltext['url'] = $file['location'];
                if ($this->settings['useInternalProxy']) {
                    $this->configureProxyUrl($fulltext['url']);
                }
                $fulltext['mimetype'] = $file['mimeType'];
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
    protected function addViewerJS(): void
    {
        // Viewer configuration.
        $imageFileGroups = array_reverse(GeneralUtility::trimExplode(',', $this->extConf['fileGrpImages']));
        $fulltextFileGroups = GeneralUtility::trimExplode(',', $this->extConf['fileGrpFulltext']);
        $config = [
            'forceAbsoluteUrl' => !empty($this->settings['forceAbsoluteUrl']),
            'proxyFileGroups' => !empty($this->settings['useInternalProxy'])
                ? array_merge($imageFileGroups, $fulltextFileGroups)
                : [],
        ];
        $tx_dlf_loaded = [
            'state' => [
                'documentId' => $this->requestData['id'],
                'page' => $this->requestData['page'],
                'simultaneousPages' => (int) $this->requestData['double'] + 1,
            ],
            'urlTemplate' => $this->getUrlTemplate(),
            'fileGroups' => [
                'images' => $imageFileGroups,
                'fulltext' => $fulltextFileGroups,
                'download' => GeneralUtility::trimExplode(',', $this->extConf['fileGrpDownload']),
            ],
            'document' => $this->document->getDoc()->toArray($this->uriBuilder, $config),
        ];
        // TODO: Rethink global tx_dlf_loaded
        $viewerConfiguration = '
            tx_dlf_loaded = ' . json_encode($tx_dlf_loaded) . ';

            tx_dlf_loaded.getVisiblePages = function (firstPageNo = tx_dlf_loaded.state.page) {
                const result = [];
                for (let i = 0; i < tx_dlf_loaded.state.simultaneousPages; i++) {
                    const pageNo = firstPageNo + i;
                    const pageObj = tx_dlf_loaded.document.pages[pageNo - 1];
                    if (pageObj !== undefined) {
                        result.push({ pageNo, pageObj });
                    }
                }
                return result;
            };

            tx_dlf_loaded.makePageUrl = function (pageNo, pageGrid = false) {
                const doublePage = tx_dlf_loaded.state.simultaneousPages >= 2 ? 1 : 0;
                return tx_dlf_loaded.urlTemplate
                    .replace(/DOUBLE_PAGE/, doublePage)
                    .replace(/PAGE_NO/, pageNo)
                    .replace(/PAGE_GRID/, pageGrid ? "1" : "0");
            };

            $(document).ready(function() {
                new dlfController();

                if (dlfUtils.exists(dlfViewer)) {
                    tx_dlf_viewer = new dlfViewer({
                        controls: ["' . implode('", "', $this->controls) . '"],
                        div: "' . $this->settings['elementId'] . '",
                        progressElementId: "' . $this->settings['progressElementId'] . '",
                        images: ' . json_encode($this->images) . ',
                        fulltexts: ' . json_encode($this->fulltexts) . ',
                        annotationContainers: ' . json_encode($this->annotationContainers) . ',
                        useInternalProxy: ' . ($this->settings['useInternalProxy'] ? 1 : 0) . ',
                    });
                }
            });';
        $this->view->assign('viewerConfiguration', $viewerConfiguration);
    }

    /**
     * Get URL template with the following placeholders:
     *
     * * `PAGE_NO` (for value of `tx_dlf[page]`)
     * * `DOUBLE_PAGE` (for value of `tx_dlf[double]`)
     *
     * @return string
     */
    protected function getUrlTemplate()
    {
        // Should work for route enhancers like this:
        //
        //   routeEnhancers:
        //     KitodoWorkview:
        //     type: Plugin
        //     namespace: tx_dlf
        //     routePath: '/{page}/{double}'
        //     requirements:
        //       page: \d+
        //       double: 0|1

        $make = function ($page, $double, $pagegrid) {
            $result = $this->uriBuilder->reset()
                ->setTargetPageUid($GLOBALS['TSFE']->id)
                ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? true : false)
                ->setArguments([
                    'tx_dlf' => array_merge($this->requestData, [
                        'page' => $page,
                        'double' => $double,
                        'pagegrid' => $pagegrid
                    ]),
                ])
                ->build();

            $cHashIdx = strpos($result, '&cHash=');
            if ($cHashIdx !== false) {
                $result = substr($result, 0, $cHashIdx);
            }

            return $result;
        };

        // Generate two URLs that differ only in tx_dlf[page] and tx_dlf[double].
        // We don't know the order of page and double parameters, so use the values for matching.
        $a = $make(2, 1, 0);
        $b = $make(3, 0, 1);

        $lastIdx = 0;
        $result = '';
        for ($i = 0, $len = strlen($a); $i < $len; $i++) {
            if ($a[$i] === $b[$i]) {
                continue;
            }

            $result .= substr($a, $lastIdx, $i - $lastIdx);
            $lastIdx = $i + 1;

            if ($a[$i] === '2') {
                $placeholder = 'PAGE_NO';
            } else if ($a[$i] === '1') {
                $placeholder = 'DOUBLE_PAGE';
            } else {
                $placeholder = 'PAGE_GRID';
            }

            $result .= $placeholder;
        }
        $result .= substr($a, $lastIdx);

        return $result;
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
     * @param int $page Page number
     *
     * @return array URL and MIME type of image file
     */
    protected function getImage(int $page): array
    {
        $image = [];
        // Get @USE value of METS fileGrp.
        $fileGrpsImages = GeneralUtility::trimExplode(',', $this->extConf['fileGrpImages']);
        while ($fileGrpImages = array_pop($fileGrpsImages)) {
            // Get image link.
            $physicalStructureInfo = $this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$page]];
            $fileId = $physicalStructureInfo['files'][$fileGrpImages];
            if (!empty($fileId)) {
                $file = $this->document->getCurrentDocument()->getFileInfo($fileId);
                $image['url'] = $file['location'];
                $image['mimetype'] = $file['mimeType'];

                // Only deliver static images via the internal PageViewProxy.
                // (For IIP and IIIF, the viewer needs to build and access a separate metadata URL, see `getMetadataURL` in `OLSources.js`.)
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
