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
        }

        // Quit if no fulltext file is present
        $fullTextFile = $this->doc->physicalStructureInfo[$this->doc->physicalStructure[$this->piVars['page']]]['files'][$this->conf['fileGrpFulltext']];
        if (empty($fullTextFile)) {
            return $content;
        }

        // Load template file.
        $this->getTemplate();

        // Configure @action URL for form.
        $linkConf = [
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1
        ];

        $encryptedSolr = $this->getEncryptedCoreName();
        // Fill markers.
        $markerArray = [
            '###ACTION_URL###' => $this->cObj->typoLink_URL($linkConf),
            '###LABEL_QUERY###' => $this->pi_getLL('label.query'),
            '###LABEL_DELETE_SEARCH###' => $this->pi_getLL('label.delete_search'),
            '###LABEL_LOADING###' => $this->pi_getLL('label.loading'),
            '###LABEL_SUBMIT###' => $this->pi_getLL('label.submit'),
            '###LABEL_SEARCH_IN_DOCUMENT###' => $this->pi_getLL('label.searchInDocument'),
            '###LABEL_NEXT###' => $this->pi_getLL('label.next'),
            '###LABEL_PREVIOUS###' => $this->pi_getLL('label.previous'),
            '###LABEL_PAGE###' => $this->pi_getLL('label.logicalPage'),
            '###CURRENT_DOCUMENT###' => $this->doc->uid,
            '###SOLR_ENCRYPTED###' => isset($encryptedSolr['encrypted']) ? $encryptedSolr['encrypted'] : '',
            '###SOLR_HASH###' => isset($encryptedSolr['hash']) ? $encryptedSolr['hash'] : '',
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
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->addJsFooterFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'Resources/Public/Javascript/Search/SearchInDocument.js');
    }

    /**
     * Get the encrypted Solr core name
     *
     * @access protected
     *
     * @return array with encrypted core name and hash
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
