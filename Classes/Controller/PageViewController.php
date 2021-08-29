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

use \TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use \Kitodo\Dlf\Domain\Model\SearchForm;
use Ubl\Iiif\Presentation\Common\Model\Resources\ManifestInterface;
use Ubl\Iiif\Presentation\Common\Vocabulary\Motivation;

class PageViewController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    public $prefixId = 'tx_dlf';
    public $extKey = 'dlf';

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    protected $logger;

    /**
     * @var
     */
    protected $extConf;

    /**
     * Holds the controls to add to the map
     *
     * @var array
     * @access protected
     */
    protected $controls = [];

    /**
     * SearchController constructor.
     * @param $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
    }

    // TODO: Needs to be placed in an abstract class
    /**
     * Loads the current document into $this->doc
     *
     * @access protected
     *
     * @return void
     */
    protected function loadDocument($requestData)
    {
        // Check for required variable.
        if (
            !empty($requestData['id'])
            && !empty($this->settings['pages'])
        ) {
            // Should we exclude documents from other pages than $this->settings['pages']?
            $pid = (!empty($this->settings['excludeOther']) ? intval($this->settings['pages']) : 0);
            // Get instance of \Kitodo\Dlf\Common\Document.
            $this->doc = Document::getInstance($requestData['id'], $pid);
            if (!$this->doc->ready) {
                // Destroy the incomplete object.
                $this->doc = null;
                $this->logger->error('Failed to load document with UID ' . $requestData['id']);
            } else {
                // Set configuration PID.
                $this->doc->cPid = $this->settings['pages'];
            }
        } elseif (!empty($requestData['recordId'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Get UID of document with given record identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.uid AS uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($requestData['recordId'])),
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                $requestData['id'] = $resArray['uid'];
                // Set superglobal $_GET array and unset variables to avoid infinite looping.
                $_GET[$this->prefixId]['id'] = $requestData['id'];
                unset($requestData['recordId'], $_GET[$this->prefixId]['recordId']);
                // Try to load document.
                $this->loadDocument();
            } else {
                $this->logger->error('Failed to load document with record ID "' . $requestData['recordId'] . '"');
            }
        } else {
            $this->logger->error('Invalid UID ' . $requestData['id'] . ' or PID ' . $this->settings['pages'] . ' for document loading');
        }
    }


    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);

        // Load current document.
        $this->loadDocument($requestData);
        if (
            $this->doc === null
            || $this->doc->numPages < 1
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
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
                        ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
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
            $this->logger->notice('No full-text file found for page "' . $page . '" in fileGrps "' . $this->conf['settings.fileGrpFulltext'] . '"');
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
        $viewerConfiguration = '<script type="text/javascript">
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
                        ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
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