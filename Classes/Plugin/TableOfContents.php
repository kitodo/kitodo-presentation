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

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Table Of Contents' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class TableOfContents extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/TableOfContents.php';

    /**
     * This holds the active entries according to the currently selected page
     *
     * @var array
     * @access protected
     */
    protected $activeEntries = [];

    /**
     * This builds an array for one menu entry
     *
     * @access protected
     *
     * @param array $entry: The entry's array from \Kitodo\Dlf\Common\Document->getLogicalStructure
     * @param bool $recursive: Whether to include the child entries
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
        $entryArray['type'] = Helper::translate($entry['type'], 'tx_dlf_structures', $this->conf['pages']);
        $entryArray['pagination'] = htmlspecialchars($entry['pagination']);
        $entryArray['_OVERRIDE_HREF'] = '';
        $entryArray['doNotLinkIt'] = 1;
        $entryArray['ITEM_STATE'] = 'NO';
        // Build menu links based on the $entry['points'] array.
        if (
            !empty($entry['points'])
            && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($entry['points'])
        ) {
            $entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(['page' => $entry['points']], true, false, $this->conf['targetPid']);
            $entryArray['doNotLinkIt'] = 0;
            if ($this->conf['basketButton']) {
                $entryArray['basketButtonHref'] = '<a href="' . $this->pi_linkTP_keepPIvars_url(['addToBasket' => 'toc', 'logId' => $entry['id'], 'startpage' => $entry['points']], true, false, $this->conf['targetBasket']) . '">' . htmlspecialchars($this->pi_getLL('basketButton', '')) . '</a>';
            }
        } elseif (
            !empty($entry['points'])
            && is_string($entry['points'])
        ) {
            $entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(['id' => $entry['points'], 'page' => 1], true, false, $this->conf['targetPid']);
            $entryArray['doNotLinkIt'] = 0;
            if ($this->conf['basketButton']) {
                $entryArray['basketButtonHref'] = '<a href="' . $this->pi_linkTP_keepPIvars_url(['addToBasket' => 'toc', 'logId' => $entry['id'], 'startpage' => $entry['points']], true, false, $this->conf['targetBasket']) . '">' . htmlspecialchars($this->pi_getLL('basketButton', '')) . '</a>';
            }
        } elseif (!empty($entry['targetUid'])) {
            $entryArray['_OVERRIDE_HREF'] = $this->pi_linkTP_keepPIvars_url(['id' => $entry['targetUid'], 'page' => 1], true, false, $this->conf['targetPid']);
            $entryArray['doNotLinkIt'] = 0;
            if ($this->conf['basketButton']) {
                $entryArray['basketButtonHref'] = '<a href="' . $this->pi_linkTP_keepPIvars_url(['addToBasket' => 'toc', 'logId' => $entry['id'], 'startpage' => $entry['targetUid']], true, false, $this->conf['targetBasket']) . '">' . htmlspecialchars($this->pi_getLL('basketButton', '')) . '</a>';
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
                !empty($this->conf['menuConf.']['expAll'])
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
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Check for typoscript configuration to prevent fatal error.
        $this->loadDocument();

        if ($this->doc !== null && $this->doc->getTitledata()['type'][0] === 'video') {
            return $this->pi_wrapInBaseClass($this->generateContentWithFluidStandaloneView($this->doc->tableOfContents[0]));
        } else {
            if (empty($this->conf['menuConf.'])) {
                Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
                return $content;
            }
            // Load template file.

            $this->getTemplate();
            $TSconfig = [];
            $TSconfig['special'] = 'userfunction';
            $TSconfig['special.']['userFunc'] = \Kitodo\Dlf\Plugin\TableOfContents::class . '->makeMenuArray';
            $TSconfig = Helper::mergeRecursiveWithOverrule($this->conf['menuConf.'], $TSconfig);
            $markerArray['###TOCMENU###'] = $this->cObj->cObjGetSingle('HMENU', $TSconfig);
            $content .= $this->templateService->substituteMarkerArray($this->template, $markerArray);
            return $this->pi_wrapInBaseClass($content);
        }
    }

    /**
     * This builds a menu array for HMENU
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return array HMENU array
     */
    public function makeMenuArray($content, $conf)
    {
        $this->init($conf);
        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return [];
        } else {
            if (!empty($this->piVars['logicalPage'])) {
                $this->piVars['page'] = $this->doc->getPhysicalPage($this->piVars['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->piVars['logicalPage']);
            }
            // Set default values for page if not set.
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
        $menuArray = [];
        // Does the document have physical elements or is it an external file?
        if (
            !empty($this->doc->physicalStructure)
            || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->doc->uid)
        ) {
            // Get all logical units the current page or track is a part of.
            if (
                !empty($this->piVars['page'])
                && !empty($this->doc->physicalStructure)
            ) {
                $this->activeEntries = array_merge((array) $this->doc->smLinks['p2l'][$this->doc->physicalStructure[0]], (array) $this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page']]]);
                if (
                    !empty($this->piVars['double'])
                    && $this->piVars['page'] < $this->doc->numPages
                ) {
                    $this->activeEntries = array_merge($this->activeEntries, (array) $this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page'] + 1]]);
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
            if ($this->conf['excludeOther']) {
                $excludeOtherWhere = 'tx_dlf_documents.pid=' . intval($this->conf['pages']);
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
}
