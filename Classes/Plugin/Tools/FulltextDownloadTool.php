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
 * Fulltext Download tool for the plugin 'Toolbox' of the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class FulltextDownloadTool extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Tools/FulltextDownloadTool.php';

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
        }

        $this->setPage();

        // Load template file.
        $this->getTemplate();
        // Get text download.
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->conf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$fileGrpFulltext];
                break;
            }
        }
        if (!empty($fullTextFile)) {
            $markerArray['###FULLTEXT_DOWNLOAD###'] = '<a href="#" id="tx-dlf-tools-fulltextdownload" title="' . htmlspecialchars($this->pi_getLL('download-current-page', '')) . '">' . htmlspecialchars($this->pi_getLL('download-current-page', '')) . '</a>';
        } else {
            $markerArray['###FULLTEXT_DOWNLOAD###'] = '<span class="no-fulltext">' . htmlspecialchars($this->pi_getLL('fulltext-not-available', '')) . '</span>';
        }
        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }

}
