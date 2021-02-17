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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Fulltext tool for the plugin 'Toolbox' of the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class FulltextTool extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Tools/FulltextTool.php';

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
        // Load current document.
        $this->loadDocument();
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->conf['fileGrpFulltext'])
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
                $this->piVars['page'] = MathUtility::forceIntegerInRange((int) $this->piVars['page'], 1, $this->doc->numPages, 1);
            } else {
                $this->piVars['page'] = array_search($this->piVars['page'], $this->doc->physicalStructure);
            }
            $this->piVars['double'] = MathUtility::forceIntegerInRange($this->piVars['double'], 0, 1, 0);
        }
        // Load template file.
        $this->getTemplate();
        $fileGrpFulltexts = GeneralUtility::trimExplode(',', $this->conf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpFulltexts)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$fileGrpFulltext];
                break;
            }
        }
        if (!empty($fullTextFile)) {
            $markerArray['###FULLTEXT_SELECT###'] = '<a class="select switchoff" id="tx-dlf-tools-fulltext" title="" data-dic="fulltext:' . htmlspecialchars($this->pi_getLL('fulltext', '')) . ';fulltext-on:' . htmlspecialchars($this->pi_getLL('fulltext-on', '')) . ';fulltext-off:' . htmlspecialchars($this->pi_getLL('fulltext-off', '')) . ';activate-full-text-initially:' . MathUtility::forceIntegerInRange($this->conf['activateFullTextInitially'], 0, 1, 0) . ';full-text-scroll-element:' . $this->conf['fullTextScrollElement'] . '">&nbsp;</a>';
        } else {
            $markerArray['###FULLTEXT_SELECT###'] = '<span class="no-fulltext">' . htmlspecialchars($this->pi_getLL('fulltext-not-available', '')) . '</span>';
        }
        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }
}
