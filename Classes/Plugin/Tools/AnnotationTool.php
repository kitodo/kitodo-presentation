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

namespace Kitodo\Dlf\Plugin\Tools;

use Kitodo\Dlf\Common\AbstractPlugin;
use Kitodo\Dlf\Common\Helper;

/**
 * Tool 'Annotation selection' for the plugin 'DLF: Toolbox' of the 'dlf' extension.
 * Allows the display of IIIF annotations.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Lutz Helm <helm@ub.uni-leipzig.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class AnnotationTool extends AbstractPlugin
{
    /**
     * @access public
     * @var string
     */
    public $scriptRelPath = 'Classes/Plugin/Tools/AnnotationTool.php';

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string  $content: The PlugIn content
     * @param array  $conf: The PlugIn configuration
     *
     * @return string  The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Merge configuration with conf array of toolbox.
        if (!empty($this->cObj->data['conf'])) {
            $this->conf = Helper::mergeRecursiveWithOverrule($this->cObj->data['conf'], $this->conf);
        }
        // Load current document.
        $this->loadDocument();
        if (
            $this->doc === null
            || $this->doc->numPages < 1
        ) {
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
            if (
                (int) $this->piVars['page'] > 0
                || empty($this->piVars['page'])
            ) {
                $this->piVars['page'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);
            } else {
                $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);
            }
            $this->piVars['double'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);
        }
        // Load template file.
        $this->getTemplate();
        $annotationContainers = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['annotationContainers'];
        if (
            $annotationContainers != null
            && sizeof($annotationContainers) > 0
        ) {
            $markerArray['###ANNOTATION_SELECT###'] = '<a class="select switchoff" id="tx-dlf-tools-annotations" title="" data-dic="annotations-on:' . htmlspecialchars($this->pi_getLL('annotations-on', '')) . ';annotations-off:' . htmlspecialchars($this->pi_getLL('annotations-off', '')) . '">&nbsp;</a>';
        } else {
            $markerArray['###ANNOTATION_SELECT###'] = '<span class="no-annotations">' . htmlspecialchars($this->pi_getLL('annotations-not-available', '')) . '</span>';
        }
        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }
}
