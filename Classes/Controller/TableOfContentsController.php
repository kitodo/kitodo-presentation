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

use Kitodo\Dlf\Common\Document;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\MathUtility;
use \TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Controller for plugin 'Table Of Contents' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class TableOfContentsController extends ActionController
{
    protected $prefixId = 'tx_dlf';

    /**
     * This holds the active entries according to the currently selected page
     *
     * @var array
     * @access protected
     */
    protected $activeEntries = [];

    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    protected $logger;

    /**
     * @var array
     */
    protected $pluginConf;

    /**
     * SearchController constructor.
     */
    public function __construct()
    {
        $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $this->logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        // Read plugin TS configuration.
        $this->pluginConf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dlf_tableofcontents.'];
    }

    /**
     * This builds an array for one menu entry
     *
     * @access protected
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Document->getLogicalStructure
     * @param bool $recursive : Whether to include the child entries
     *
     * @return array HMENU array for menu entry
     */
    protected function getMenuEntry(array $entry, $recursive = false)
    {
        $entryArray = [];
        // Set "title", "volume", "type" and "pagination" from $entry array.
        $entryArray['title'] = !empty($entry['label']) ? $entry['label'] : $entry['orderlabel'];
        $entryArray['volume'] = $entry['volume'];
        $entryArray['orderlabel'] = $entry['orderlabel'];
        $entryArray['type'] = Helper::translate($entry['type'], 'tx_dlf_structures', $this->settings['pages']);
        $entryArray['pagination'] = htmlspecialchars($entry['pagination']);
        $entryArray['_OVERRIDE_HREF'] = '';
        $entryArray['doNotLinkIt'] = 1;
        $entryArray['ITEM_STATE'] = 'NO';
        // Build menu links based on the $entry['points'] array.
        if (
            !empty($entry['points'])
            && MathUtility::canBeInterpretedAsInteger($entry['points'])
        ) {
            $entryArray['page'] = $entry['points'];

            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['points']
                ];
            }
        } elseif (
            !empty($entry['points'])
            && is_string($entry['points'])
        ) {
            $entryArray['id'] = $entry['points'];
            $entryArray['page'] = 1;
            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['points']
                ];
            }
        } elseif (!empty($entry['targetUid'])) {
            $entryArray['id'] = $entry['targetUid'];
            $entryArray['page'] = 1;
            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['targetUid']
                ];
            }
        }
        // Set "ITEM_STATE" to "CUR" if this entry points to current page.
        if (in_array($entry['id'], $this->activeEntries)) {
            $entryArray['ITEM_STATE'] = 'CUR';
        }
        // Build sub-menu if available and called recursively.
        if (
            $recursive == true
            && !empty($entry['children'])
        ) {
            // Build sub-menu only if one of the following conditions apply:
            // 1. "expAll" is set for menu
            // 2. Current menu node is in rootline
            // 3. Current menu node points to another file
            // 4. Current menu node has no corresponding images
            if (
                !empty($this->pluginConf['menuConf.']['expAll'])
                || $entryArray['ITEM_STATE'] == 'CUR'
                || is_string($entry['points'])
                || empty($this->doc->smLinks['l2p'][$entry['id']])
            ) {
                $entryArray['_SUB_MENU'] = [];
                foreach ($entry['children'] as $child) {
                    // Set "ITEM_STATE" to "ACT" if this entry points to current page and has sub-entries pointing to the same page.
                    if (in_array($child['id'], $this->activeEntries)) {
                        $entryArray['ITEM_STATE'] = 'ACT';
                    }
                    $entryArray['_SUB_MENU'][] = $this->getMenuEntry($child, true);
                }
            }
            // Append "IFSUB" to "ITEM_STATE" if this entry has sub-entries.
            $entryArray['ITEM_STATE'] = ($entryArray['ITEM_STATE'] == 'NO' ? 'IFSUB' : $entryArray['ITEM_STATE'] . 'IFSUB');
        }
        return $entryArray;
    }

    /**
     * The main method of the PlugIn
     *
     * @access public
     */
    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'],$requestData['__trustedProperties']);

        // Check for typoscript configuration to prevent fatal error.
        if (empty($this->pluginConf['menuConf.'])) {
            $this->logger->warning('Incomplete plugin configuration');
        }

        $this->view->assign('toc', $this->makeMenuArray($requestData));
    }

    /**
     * This builds a menu array for HMENU
     *
     * @access public
     * @param array $requestData
     * @return array HMENU array
     */
    public function makeMenuArray($requestData)
    {
        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return [];
        } else {
            if (!empty($requestData['logicalPage'])) {
                $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($requestData['logicalPage']);
            }
            // Set default values for page if not set.
            // $this->piVars['page'] may be integer or string (physical structure @ID)
            if (
                (int)$requestData['page'] > 0
                || empty($requestData['page'])
            ) {
                $requestData['page'] = MathUtility::forceIntegerInRange((int)$requestData['page'],
                    1, $this->doc->numPages, 1);
            } else {
                $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
            }
            $requestData['double'] = MathUtility::forceIntegerInRange($requestData['double'],
                0, 1, 0);
        }
        $menuArray = [];
        // Does the document have physical elements or is it an external file?
        if (
            !empty($this->doc->physicalStructure)
            || !MathUtility::canBeInterpretedAsInteger($this->doc->uid)
        ) {
            // Get all logical units the current page or track is a part of.
            if (
                !empty($requestData['page'])
                && !empty($this->doc->physicalStructure)
            ) {
                $this->activeEntries = array_merge((array)$this->doc->smLinks['p2l'][$this->doc->physicalStructure[0]],
                    (array)$this->doc->smLinks['p2l'][$this->doc->physicalStructure[$requestData['page']]]);
                if (
                    !empty($requestData['double'])
                    && $requestData['page'] < $this->doc->numPages
                ) {
                    $this->activeEntries = array_merge($this->activeEntries,
                        (array)$this->doc->smLinks['p2l'][$this->doc->physicalStructure[$requestData['page'] + 1]]);
                }
            }
            // Go through table of contents and create all menu entries.
            foreach ($this->doc->tableOfContents as $entry) {
                $menuArray[] = $this->getMenuEntry($entry, true);
            }
        } else {
            // Go through table of contents and create top-level menu entries.
            foreach ($this->doc->tableOfContents as $entry) {
                $menuArray[] = $this->getMenuEntry($entry, false);
            }
            // Build table of contents from database.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $excludeOtherWhere = '';
            if ($this->settings['excludeOther']) {
                $excludeOtherWhere = 'tx_dlf_documents.pid=' . intval($this->settings['pages']);
            }
            // Check if there are any metadata to suggest.
            $result = $queryBuilder
                ->select(
                    'tx_dlf_documents.uid AS uid',
                    'tx_dlf_documents.title AS title',
                    'tx_dlf_documents.volume AS volume',
                    'tx_dlf_documents.mets_label AS mets_label',
                    'tx_dlf_documents.mets_orderlabel AS mets_orderlabel',
                    'tx_dlf_structures_join.index_name AS type'
                )
                ->innerJoin(
                    'tx_dlf_documents',
                    'tx_dlf_structures',
                    'tx_dlf_structures_join',
                    $queryBuilder->expr()->eq(
                        'tx_dlf_structures_join.uid',
                        'tx_dlf_documents.structure'
                    )
                )
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($this->doc->uid)),
                    $queryBuilder->expr()->eq('tx_dlf_structures_join.pid', intval($this->doc->pid)),
                    $excludeOtherWhere
                )
                ->addOrderBy('tx_dlf_documents.volume_sorting')
                ->addOrderBy('tx_dlf_documents.mets_orderlabel')
                ->execute();

            $allResults = $result->fetchAll();

            if (count($allResults) > 0) {
                $menuArray[0]['ITEM_STATE'] = 'CURIFSUB';
                $menuArray[0]['_SUB_MENU'] = [];
                foreach ($allResults as $resArray) {
                    $entry = [
                        'label' => !empty($resArray['mets_label']) ? $resArray['mets_label'] : $resArray['title'],
                        'type' => $resArray['type'],
                        'volume' => $resArray['volume'],
                        'orderlabel' => $resArray['mets_orderlabel'],
                        'pagination' => '',
                        'targetUid' => $resArray['uid']
                    ];
                    $menuArray[0]['_SUB_MENU'][] = $this->getMenuEntry($entry, false);
                }
            }
        }
        return $menuArray;
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
}
