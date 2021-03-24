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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * SearchInDocument tool for the plugin 'Toolbox' of the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class SearchInDocumentTool extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Tools/SearchInDocumentTool.php';

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

        $this->addSearchInDocumentJS();

        // Load current document.
        $this->loadDocument();
        if (
            $this->doc === null
            || $this->doc->numPages < 1
            || empty($this->conf['fileGrpFulltext'])
            || empty($this->conf['solrcore'])
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
        }

        // Quit if no fulltext file is present
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $this->conf['fileGrpFulltext']);
        while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
            if (!empty($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$fileGrpFulltext])) {
                $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$fileGrpFulltext];
                break;
            }
        }
        if (empty($fullTextFile)) {
            return $content;
        }

        // Load template file.
        $this->getTemplate();

        // Configure @action URL for form.
        $linkConf = [
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http']
        ];

        $encryptedSolr = $this->getEncryptedCoreName();
        // Fill markers.
        $markerArray = [
            '###ACTION_URL###' => $this->cObj->typoLink_URL($linkConf),
            '###LABEL_QUERY###' => htmlspecialchars($this->pi_getLL('label.query')),
            '###LABEL_DELETE_SEARCH###' => htmlspecialchars($this->pi_getLL('label.delete_search')),
            '###LABEL_LOADING###' => htmlspecialchars($this->pi_getLL('label.loading')),
            '###LABEL_SUBMIT###' => htmlspecialchars($this->pi_getLL('label.submit')),
            '###LABEL_SEARCH_IN_DOCUMENT###' => htmlspecialchars($this->pi_getLL('label.searchInDocument')),
            '###LABEL_NEXT###' => htmlspecialchars($this->pi_getLL('label.next')),
            '###LABEL_PREVIOUS###' => htmlspecialchars($this->pi_getLL('label.previous')),
            '###LABEL_PAGE###' => htmlspecialchars($this->pi_getLL('label.logicalPage')),
            '###LABEL_NORESULT###' => htmlspecialchars($this->pi_getLL('label.noresult')),
            '###CURRENT_DOCUMENT###' => $this->doc->uid,
            '###SOLR_ENCRYPTED###' => $encryptedSolr ?: '',
        ];

        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Adds the JS files necessary for search in document
     *
     * @access protected
     *
     * @return void
     */
    protected function addSearchInDocumentJS()
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFooterFile(PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($this->extKey)) . 'Resources/Public/Javascript/Search/SearchInDocument.js');
    }

    /**
     * Get the encrypted Solr core name
     *
     * @access protected
     *
     * @return string with encrypted core name
     */
    protected function getEncryptedCoreName()
    {
        // Get core name.
        $name = Helper::getIndexNameFromUid($this->conf['solrcore'], 'tx_dlf_solrcores');
        // Encrypt core name.
        if (!empty($name)) {
            $name = Helper::encrypt($name);
        }
        return $name;
    }
}
