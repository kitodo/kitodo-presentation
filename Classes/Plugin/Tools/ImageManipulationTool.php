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

use Kitodo\Dlf\Common\Helper;

/**
 * Image Manipulation tool for the plugin 'Toolbox' of the 'dlf' extension
 *
 * @author Jacob Mendt <Jacob.Mendt@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ImageManipulationTool extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Tools/ImageManipulationTool.php';

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
    public function main($content, $conf)
    {
        $this->init($conf);
        // Merge configuration with conf array of toolbox.
        if (!empty($this->cObj->data['conf'])) {
            $this->conf = Helper::mergeRecursiveWithOverrule($this->cObj->data['conf'], $this->conf);
        }
        // Set parent element for initialization.
        $parentContainer = !empty($this->conf['parentContainer']) ? $this->conf['parentContainer'] : '.tx-dlf-imagemanipulationtool';
        // Load template file.
        $this->getTemplate();
        $markerArray['###IMAGEMANIPULATION_SELECT###'] = '<span class="tx-dlf-tools-imagetools" id="tx-dlf-tools-imagetools" data-dic="imagemanipulation-on:' . htmlspecialchars($this->pi_getLL('imagemanipulation-on', '')) . ';imagemanipulation-off:' . htmlspecialchars($this->pi_getLL('imagemanipulation-off', '')) . ';reset:' . htmlspecialchars($this->pi_getLL('reset', '')) . ';saturation:' . htmlspecialchars($this->pi_getLL('saturation', '')) . ';hue:' . htmlspecialchars($this->pi_getLL('hue', '')) . ';contrast:' . htmlspecialchars($this->pi_getLL('contrast', '')) . ';brightness:' . htmlspecialchars($this->pi_getLL('brightness', '')) . ';invert:' . htmlspecialchars($this->pi_getLL('invert', '')) . ';parentContainer:' . $parentContainer . '" title="' . htmlspecialchars($this->pi_getLL('no-support', '')) . '"></span>';
        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }
}
