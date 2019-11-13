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

namespace Kitodo\Dlf\Plugin;

/**
 * Plugin 'Toolbox' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Toolbox extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Toolbox.php';

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
        // Quit without doing anything if required variable is not set.
        if (empty($this->piVars['id'])) {
            return $content;
        }
        // Load template file.
        $this->getTemplate();
        // Build data array.
        $data = [
            'conf' => $this->conf,
            'piVars' => $this->piVars,
        ];
        // Get template subpart for tools.
        $subpart = $this->templateService->getSubpart($this->template, '###TOOLS###');
        $tools = explode(',', $this->conf['tools']);
        // Add the tools to the toolbox.
        foreach ($tools as $tool) {
            $tool = trim($tool);
            $cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
            $cObj->data = $data;
            $content .= $this->templateService->substituteMarkerArray($subpart, ['###TOOL###' => $cObj->cObjGetSingle($GLOBALS['TSFE']->tmpl->setup['plugin.'][$tool], $GLOBALS['TSFE']->tmpl->setup['plugin.'][$tool . '.'])]);
        }
        return $this->pi_wrapInBaseClass($this->templateService->substituteSubpart($this->template, '###TOOLS###', $content, true));
    }
}
