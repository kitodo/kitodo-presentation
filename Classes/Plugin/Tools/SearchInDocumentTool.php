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
 * SearchInDocument tool for the plugin 'Toolbox' of the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
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

        // Fill markers.
        $markerArray = [
            '###ACTION_URL###' => $this->getActionUrl(),
            '###LABEL_QUERY###' => htmlspecialchars($this->pi_getLL('label.query')),
            '###LABEL_DELETE_SEARCH###' => htmlspecialchars($this->pi_getLL('label.delete_search')),
            '###LABEL_LOADING###' => htmlspecialchars($this->pi_getLL('label.loading')),
            '###LABEL_SUBMIT###' => htmlspecialchars($this->pi_getLL('label.submit')),
            '###LABEL_SEARCH_IN_DOCUMENT###' => htmlspecialchars($this->pi_getLL('label.searchInDocument')),
            '###LABEL_NEXT###' => htmlspecialchars($this->pi_getLL('label.next')),
            '###LABEL_PREVIOUS###' => htmlspecialchars($this->pi_getLL('label.previous')),
            '###LABEL_PAGE###' => htmlspecialchars($this->pi_getLL('label.logicalPage')),
            '###LABEL_NORESULT###' => htmlspecialchars($this->pi_getLL('label.noresult')),
            '###LABEL_QUERY_URL###' => $this->conf['queryInputName'],
            '###LABEL_START###' => $this->conf['startInputName'],
            '###LABEL_ID###' => $this->conf['idInputName'],
            '###LABEL_PAGE_URL###' => $this->conf['pageInputName'],
            '###LABEL_HIGHLIGHT_WORD###' => $this->conf['highlightWordInputName'],
            '###LABEL_ENCRYPTED###' => $this->conf['encryptedInputName'],
            '###CURRENT_DOCUMENT###' => $this->getCurrentDocumentId(),
            '###SOLR_ENCRYPTED###' => $this->getEncryptedCoreName() ? : ''
        ];

        $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Get the action url for search form
     *
     * @access protected
     *
     * @return string with action url for search form
     */
    protected function getActionUrl()
    {
        // Configure @action URL for form.
        $linkConf = [
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http']
        ];

        $actionUrl = $this->cObj->typoLink_URL($linkConf);

        if (!empty($this->conf['searchUrl'])) {
            $actionUrl = $this->conf['searchUrl'];
        }
        return $actionUrl;
    }

    /**
     * Get current document id
     *
     * @access protected
     *
     * @return string with current document id
     */
    protected function getCurrentDocumentId()
    {
        $id = $this->doc->uid;

        if (!empty($this->conf['documentIdUrlSchema'])) {
            $arr = explode('*', $this->conf['documentIdUrlSchema']);

            if (count($arr) == 2) {
                $id = explode($arr[0], $id)[0];
            } else if (count($arr) == 3) {
                $sub = substr($id, strpos($id, $arr[0]) + strlen($arr[0]), strlen($id));
                $id = substr($sub, 0, strpos($sub, $arr[2]));
            }
        }
        return $id;
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
