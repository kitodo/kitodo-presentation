<?php
namespace Kitodo\Dlf\Plugin;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use Ubl\Iiif\Presentation\Common\Model\Resources\ManifestInterface;
use Ubl\Iiif\Presentation\Common\Vocabulary\Motivation;

/**
 * Plugin 'Page View' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class PageView extends \Kitodo\Dlf\Common\AbstractPlugin {
    public $scriptRelPath = 'Classes/Plugin/PageView.php';

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
    protected $annotationContainers = array();

    /**
     * Adds Viewer javascript
     *
     * @access protected
     *
     * @return void
     */
    protected function addViewerJS() {

        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        // Add OpenLayers library.
        $pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/OpenLayers/ol3.css');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/OpenLayers/glif.min.js');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/OpenLayers/ol3-dlf.js');
        // Add viewer library.
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/Utility.js');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/OL3.js');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/OL3Styles.js');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/OL3Sources.js');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/AltoParser.js');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/ImageManipulationControl.js');
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/FulltextControl.js');
        if ($this->doc instanceof IiifManifest) {
            $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/AnnotationParser.js');
            $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/AnnotationControl.js');
        }
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Javascript/PageView/PageView.js');
        // Add viewer configuration.
        $viewerConfiguration = '
            $(document).ready(function() {
                if (dlfUtils.exists(dlfViewer)) {
                    tx_dlf_viewer = new dlfViewer({
                        controls: ["'.implode('", "', $this->controls).'"],
                        div: "'.$this->conf['elementId'].'",
                        images: '.json_encode($this->images).',
                        fulltexts: '.json_encode($this->fulltexts).',
                        annotationContainers: '.json_encode($this->annotationContainers).',
                        useInternalProxy: '.($this->conf['useInternalProxy'] ? 1 : 0).'
                    });
                }
            });
            ';
        $pageRenderer->addJsFooterInlineCode('kitodo-pageview-configuration', $viewerConfiguration);
    }

    /**
     * Adds pageview interaction (crop, magnifier and rotation)
     *
     * @access protected
     *
     * @return array Marker array
     */
    protected function addInteraction() {
        $markerArray = [];
        if ($this->piVars['id']) {
            if ($this->conf['crop']) {
                $markerArray['###EDITBUTTON###'] = '<a href="javascript: tx_dlf_viewer.activateSelection();">'.$this->pi_getLL('editMode', '', TRUE).'</a>';
                $markerArray['###EDITREMOVE###'] = '<a href="javascript: tx_dlf_viewer.resetCropSelection();">'.$this->pi_getLL('editRemove', '', TRUE).'</a>';
            } else {
                $markerArray['###EDITBUTTON###'] = '';
                $markerArray['###EDITREMOVE###'] = '';
            }
            if ($this->conf['magnifier']) {
                $markerArray['###MAGNIFIER###'] = '<a href="javascript: tx_dlf_viewer.activateMagnifier();">'.$this->pi_getLL('magnifier', '', TRUE).'</a>';
            } else {
                $markerArray['###MAGNIFIER###'] = '';
            }
        }
        return $markerArray;
    }

    /**
     * Adds form to save cropping data to basket
     *
     * @access protected
     *
     * @return array Marker array
     */
    protected function addBasketForm() {
        $markerArray = [];
        // Add basket button
        if ($this->conf['basketButton'] && $this->conf['targetBasket'] && $this->piVars['id']) {
            $label = $this->pi_getLL('addBasket', '', TRUE);
            $params = [
                'id' => $this->piVars['id'],
                'addToBasket' => TRUE
            ];
            if (empty($this->piVars['page'])) {
                $params['page'] = 1;
            }
            $basketConf = [
                'parameter' => $this->conf['targetBasket'],
                'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $params, '', TRUE, FALSE),
                'title' => $label
            ];
            $output = '<form id="addToBasketForm" action="'.$this->cObj->typoLink_URL($basketConf).'" method="post">';
            $output .= '<input type="hidden" name="tx_dlf[startpage]" id="startpage" value="'.$this->piVars['page'].'">';
            $output .= '<input type="hidden" name="tx_dlf[endpage]" id="endpage" value="'.$this->piVars['page'].'">';
            $output .= '<input type="hidden" name="tx_dlf[startX]" id="startX">';
            $output .= '<input type="hidden" name="tx_dlf[startY]" id="startY">';
            $output .= '<input type="hidden" name="tx_dlf[endX]" id="endX">';
            $output .= '<input type="hidden" name="tx_dlf[endY]" id="endY">';
            $output .= '<input type="hidden" name="tx_dlf[rotation]" id="rotation">';
            $output .= '<button id="submitBasketForm" onclick="this.form.submit()">'.$label.'</button>';
            $output .= '</form>';
            $output .= '<script>';
            $output .= '$(document).ready(function() { $("#submitBasketForm").click(function() { $("#addToBasketForm").submit(); }); });';
            $output .= '</script>';
            $markerArray['###BASKETBUTTON###'] = $output;
        } else {
            $markerArray['###BASKETBUTTON###'] = '';
        }
        return $markerArray;
    }

    /**
     * Get image's URL and MIME type
     *
     * @access protected
     *
     * @param integer $page: Page number
     *
     * @return array URL and MIME type of image file
     */
    protected function getImage($page) {
        $image = [];
        // Get @USE value of METS fileGrp.
        $fileGrps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['fileGrps']);
        while ($fileGrp = @array_pop($fileGrps)) {
            // Get image link.
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp])) {
                $image['url'] = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp]);
                if ($this->conf['useInternalProxy']) {
                    // Configure @action URL for form.
                    $linkConf = [
                        'parameter' => $GLOBALS['TSFE']->id,
                        'additionalParams' => '&eID=tx_dlf_pageview_proxy&url='.urlencode($image['url']),
                    ];
                    $image['url'] = $this->cObj->typoLink_URL($linkConf);
                }
                $image['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$fileGrp]);
                break;
            } else {
                Helper::devLog('File not found in fileGrp "'.$fileGrp.'"', DEVLOG_SEVERITY_WARNING);
            }
        }
        return $image;
    }

    /**
     * Get fulltext URL and MIME type
     *
     * @access protected
     *
     * @param integer $page: Page number
     *
     * @return array URL and MIME type of fulltext file
     */
    protected function getFulltext($page) {
        $fulltext = [];
        // Get fulltext link.
        if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$this->conf['fileGrpFulltext']])) {
            $fulltext['url'] = $this->doc->getFileLocation($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$this->conf['fileGrpFulltext']]);
            // Configure @action URL for form.
            $linkConf = [
                'parameter' => $GLOBALS['TSFE']->id,
                'additionalParams' => '&eID=tx_dlf_pageview_proxy&url='.urlencode($fulltext['url']),
            ];
            $fulltext['url'] = $this->cObj->typoLink_URL($linkConf);
            $fulltext['mimetype'] = $this->doc->getFileMimeType($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$page]]['files'][$this->conf['fileGrpFulltext']]);
        } else {
            Helper::devLog('File not found in fileGrp "'.$this->conf['fileGrpFulltext'].'"', DEVLOG_SEVERITY_WARNING);
        }
        return $fulltext;
    }

    /**
     * Get all AnnotationPages / AnnotationLists that contain text Annotations with "painting" motivation
     *
     * @access protected
     *
     * @param integer    $page: the current page's number
     * @return array     An array containing the IRIs of the AnnotationLists / AnnotationPages as well as
     *                   some information about the canvas.
     */
    protected function getAnnotationContainers($page)
    {
        if ($this->doc instanceof IiifManifest) {
            $canvasId = $this->doc->physicalStructure[$page];
            $iiif = $this->doc->getIiif();
            if ($iiif instanceof ManifestInterface) {
                $canvas = $iiif->getContainedResourceById($canvasId);
                /* @var $canvas \Ubl\Iiif\Presentation\Common\Model\Resources\CanvasInterface */
                if ($canvas!=null && !empty($canvas->getPossibleTextAnnotationContainers(Motivation::PAINTING))) {
                    $annotationContainers = array();
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
                                if ($annotation->getBody()->getFormat() == "text/plain"
                                    && $annotation->getBody()->getChars() != null) {
                                    $annotationListData = [];
                                    $annotationListData["uri"] = $annotationContainer->getId();
                                    $annotationListData["label"] = $annotationContainer->getLabelForDisplay();
                                    $annotationContainers[] = $annotationListData;
                                    break;
                                }
                            }
                        }
                    }
                    $result = [
                        "canvas" => [
                            "id" => $canvas->getId(),
                            "width" => $canvas->getWidth(),
                            "height" => $canvas->getHeight(),
                        ],
                        "annotationContainers"=>$annotationContainers
                    ];
                    return $result;
                }
            }
        }
        return array();
    }

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf) {
        $this->init($conf);
        // Load current document.
        $this->loadDocument();
        if ($this->doc === NULL
            || $this->doc->numPages < 1) {
            // Quit without doing anything if required variables are not set.
            return $content;
        } else {
            if (!empty($this->piVars['logicalPage'])) {
                $this->piVars['page'] = $this->doc->getPhysicalPage($this->piVars['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->piVars['logicalPage']);
            }
            // Set default values if not set.
            // $this->piVars['page'] may be integer or string (physical structure @ID)
            if ((int) $this->piVars['page'] > 0 || empty($this->piVars['page'])) {
                $this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);
            } else {
                $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);
            }
            $this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);
        }
        // Load template file.
        $this->getTemplate();
        // Get image data.
        $this->images[0] = $this->getImage($this->piVars['page']);
        $this->fulltexts[0] = $this->getFulltext($this->piVars['page']);
        $this->annotationContainers[0] = $this->getAnnotationContainers($this->piVars['page']);
        if ($this->piVars['double'] && $this->piVars['page'] < $this->doc->numPages) {
            $this->images[1] = $this->getImage($this->piVars['page'] + 1);
            $this->fulltexts[1] = $this->getFulltext($this->piVars['page'] + 1);
            $this->annotationContainers[1] = $this->getAnnotationContainers($this->piVars['page'] + 1);
        }
        // Get the controls for the map.
        $this->controls = explode(',', $this->conf['features']);
        // Fill in the template markers.
        $this->addViewerJS();
        $markerArray = array_merge($this->addInteraction(), $this->addBasketForm());
        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }
}
