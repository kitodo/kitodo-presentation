<?php
namespace Kitodo\Dlf\Plugin;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;

/**
 * Plugin 'Collection' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Collection extends \Kitodo\Dlf\Common\AbstractPlugin {
    public $scriptRelPath = 'Classes/Plugin/Collection.php';

    /**
     * This holds the hook objects
     *
     * @var array
     * @access protected
     */
    protected $hookObjects = [];

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
    public function main($content, $conf) {
        $this->init($conf);
        // Turn cache on.
        $this->setCache(TRUE);
        // Quit without doing anything if required configuration variables are not set.
        if (empty($this->conf['pages'])) {
            Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
            return $content;
        }
        // Load template file.
        $this->getTemplate();
        // Get hook objects.
        $this->hookObjects = Helper::getHookObjects($this->scriptRelPath);
        if (!empty($this->piVars['collection'])) {
            $this->showSingleCollection(intval($this->piVars['collection']));
        } else {
            $content .= $this->showCollectionList();
        }
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Builds a collection list
     *
     * @access protected
     *
     * @return string The list of collections ready to output
     */
    protected function showCollectionList() {
        $selectedCollections = 'tx_dlf_collections.uid != 0';
        $orderBy = 'tx_dlf_collections.label';
        $showUserDefinedColls = '';
        // Handle collections set by configuration.
        if ($this->conf['collections']) {
            if (count(explode(',', $this->conf['collections'])) == 1
                && empty($this->conf['dont_show_single'])) {
                $this->showSingleCollection(intval(trim($this->conf['collections'], ' ,')));
            }
            $selectedCollections = 'tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).')';
            $orderBy = 'FIELD(tx_dlf_collections.uid,'.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).')';
        }
        // Should user-defined collections be shown?
        if (empty($this->conf['show_userdefined'])) {
            $showUserDefinedColls = ' AND tx_dlf_collections.fe_cruser_id=0';
        } elseif ($this->conf['show_userdefined'] > 0) {
            if (!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
                $showUserDefinedColls = ' AND tx_dlf_collections.fe_cruser_id='.intval($GLOBALS['TSFE']->fe_user->user['uid']);
            } else {
                $showUserDefinedColls = ' AND NOT tx_dlf_collections.fe_cruser_id=0';
            }
        }
        // Get collections.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_collections.index_name AS index_name,tx_dlf_collections.index_search as index_query,tx_dlf_collections.uid AS uid,tx_dlf_collections.sys_language_uid AS sys_language_uid,tx_dlf_collections.label AS label,tx_dlf_collections.thumbnail AS thumbnail,tx_dlf_collections.description AS description,tx_dlf_collections.priority AS priority',
            'tx_dlf_collections',
            $selectedCollections
                .$showUserDefinedColls
                .' AND tx_dlf_collections.pid='.intval($this->conf['pages'])
                .' AND (tx_dlf_collections.sys_language_uid IN (-1,0) OR (tx_dlf_collections.sys_language_uid = '.$GLOBALS['TSFE']->sys_language_uid.' AND tx_dlf_collections.l18n_parent = 0))'
                .Helper::whereClause('tx_dlf_collections'),
            '',
            $orderBy
        );
        $count = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
        $content = '';
        if ($count == 1
            && empty($this->conf['dont_show_single'])) {
            $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
            $this->showSingleCollection(intval($resArray['uid']));
        }
        $solr = Solr::getInstance($this->conf['solrcore']);
        // We only care about the UID and partOf in the results and want them sorted
        $params['fields'] = 'uid,partof';
        $params['sort'] = ['uid' => 'asc'];
        $collections = [];
        while ($collectionData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            if ($collectionData['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content && $GLOBALS['TSFE']->sys_language_contentOL) {
                $collections[$collectionData['uid']] = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_dlf_collections', $collectionData, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
            } else {
                $collections[$collectionData['uid']] = $collectionData;
            }
        }
        $markerArray = [];
        // Process results.
        foreach ($collections as $collection) {
            $solr_query = '';
            if ($collection['index_query'] != '') {
                $solr_query .= '('.$collection['index_query'].')';
            } else {
                $solr_query .= 'collection:("'.$collection['index_name'].'")';
            }
            $partOfNothing = $solr->search_raw($solr_query.' AND partof:0', $params);
            $partOfSomething = $solr->search_raw($solr_query.' AND NOT partof:0', $params);
            // Titles are all documents that are "root" elements i.e. partof == 0
            $collection['titles'] = [];
            foreach ($partOfNothing as $doc) {
                $collection['titles'][$doc->uid] = $doc->uid;
            }
            // Volumes are documents that are both
            // a) "leaf" elements i.e. partof != 0
            // b) "root" elements that are not referenced by other documents ("root" elements that have no descendants)
            $collection['volumes'] = $collection['titles'];
            foreach ($partOfSomething as $doc) {
                $collection['volumes'][$doc->uid] = $doc->uid;
                // If a document is referenced via partof, it’s not a volume anymore.
                unset($collection['volumes'][$doc->partof]);
            }
            // Generate random but unique array key taking priority into account.
            do {
                $_key = ($collection['priority'] * 1000) + mt_rand(0, 1000);
            } while (!empty($markerArray[$_key]));
            // Merge plugin variables with new set of values.
            $additionalParams = ['collection' => $collection['uid']];
            if (is_array($this->piVars)) {
                $piVars = $this->piVars;
                unset($piVars['DATA']);
                $additionalParams = Helper::mergeRecursiveWithOverrule($piVars, $additionalParams);
            }
            // Build typolink configuration array.
            $conf = [
                'useCacheHash' => 1,
                'parameter' => $GLOBALS['TSFE']->id,
                'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE)
            ];
            // Link collection's title to list view.
            $markerArray[$_key]['###TITLE###'] = $this->cObj->typoLink(htmlspecialchars($collection['label']), $conf);
            // Add feed link if applicable.
            if (!empty($this->conf['targetFeed'])) {
                $img = '<img src="'.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'Resources/Public/Icons/txdlffeeds.png" alt="'.$this->pi_getLL('feedAlt', '', TRUE).'" title="'.$this->pi_getLL('feedTitle', '', TRUE).'" />';
                $markerArray[$_key]['###FEED###'] = $this->pi_linkTP($img, [$this->prefixId => ['collection' => $collection['uid']]], FALSE, $this->conf['targetFeed']);
            } else {
                $markerArray[$_key]['###FEED###'] = '';
            }
            // Add thumbnail.
            if (!empty($collection['thumbnail'])) {
                $markerArray[$_key]['###THUMBNAIL###'] = '<img alt="" title="'.htmlspecialchars($collection['label']).'" src="'.$collection['thumbnail'].'" />';
            } else {
                $markerArray[$_key]['###THUMBNAIL###'] = '';
            }
            // Add description.
            $markerArray[$_key]['###DESCRIPTION###'] = $this->pi_RTEcssText($collection['description']);
            // Build statistic's output.
            $labelTitles = $this->pi_getLL((count($collection['titles']) > 1 ? 'titles' : 'title'), '', FALSE);
            $markerArray[$_key]['###COUNT_TITLES###'] = htmlspecialchars(count($collection['titles']).$labelTitles);
            $labelVolumes = $this->pi_getLL((count($collection['volumes']) > 1 ? 'volumes' : 'volume'), '', FALSE);
            $markerArray[$_key]['###COUNT_VOLUMES###'] = htmlspecialchars(count($collection['volumes']).$labelVolumes);
        }
        // Randomize sorting?
        if (!empty($this->conf['randomize'])) {
            ksort($markerArray, SORT_NUMERIC);
            // Don't cache the output.
            $this->setCache(FALSE);
        }
        $entry = $this->templateService->getSubpart($this->template, '###ENTRY###');
        foreach ($markerArray as $marker) {
            $content .= $this->templateService->substituteMarkerArray($entry, $marker);
        }
        // Hook for getting custom collection hierarchies/subentries (requested by SBB).
        foreach ($this->hookObjects as $hookObj) {
            if (method_exists($hookObj, 'showCollectionList_getCustomCollectionList')) {
                $hookObj->showCollectionList_getCustomCollectionList($this, $this->conf['templateFile'], $content, $markerArray);
            }
        }
        return $this->templateService->substituteSubpart($this->template, '###ENTRY###', $content, TRUE);
    }

    /**
     * Builds a collection's list
     *
     * @access protected
     *
     * @param integer $id: The collection's UID
     *
     * @return void
     */
    protected function showSingleCollection($id) {
        $additionalWhere = '';
        // Should user-defined collections be shown?
        if (empty($this->conf['show_userdefined'])) {
            $additionalWhere = ' AND tx_dlf_collections.fe_cruser_id=0';
        } elseif ($this->conf['show_userdefined'] > 0) {
            $additionalWhere = ' AND NOT tx_dlf_collections.fe_cruser_id=0';
        }
        // Get collection information from DB
        $collection = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_collections.index_name AS index_name, tx_dlf_collections.index_search as index_search, tx_dlf_collections.label AS collLabel, tx_dlf_collections.description AS collDesc, tx_dlf_collections.thumbnail AS collThumb, tx_dlf_collections.fe_cruser_id',
            'tx_dlf_collections',
            'tx_dlf_collections.pid='.intval($this->conf['pages'])
                .' AND tx_dlf_collections.uid='.intval($id)
                .$additionalWhere
                .Helper::whereClause('tx_dlf_collections'),
            '',
            '',
            '1'
        );
        // Fetch corresponding document UIDs from Solr.
        $collectionData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($collection);
        if ($collectionData['index_search'] != '') {
            $solr_query = '('.$collectionData['index_search'].')';
        } else {
            $solr_query = 'collection:("'.$collectionData['index_name'].'")';
        }
        $solr = Solr::getInstance($this->conf['solrcore']);
        if (!$solr->ready) {
            Helper::devLog('Apache Solr not available', DEVLOG_SEVERITY_ERROR);
            return;
        }
        $params['fields'] = 'uid';
        $params['sort'] = ['uid' => 'asc'];
        $solrResult = $solr->search_raw($solr_query, $params);
        // Initialize array
        $documentSet = [];
        foreach ($solrResult as $doc) {
            $documentSet[] = $doc->uid;
        }
        $documentSet = array_unique($documentSet);
        // Fetch document info for UIDs in $documentSet from DB
        $documents = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_documents.uid AS uid, tx_dlf_documents.metadata_sorting AS metadata_sorting, tx_dlf_documents.volume_sorting AS volume_sorting, tx_dlf_documents.partof AS partof',
            'tx_dlf_documents',
            'tx_dlf_documents.pid='.intval($this->conf['pages'])
                .' AND tx_dlf_documents.uid IN ('.implode(',', $documentSet).')'
                .Helper::whereClause('tx_dlf_documents')
        );
        $toplevel = [];
        $subparts = [];
        $listMetadata = [];
        // Process results.
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($documents)) {
            if (empty($l10nOverlay)) {
                $l10nOverlay = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_dlf_collections', $resArray, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
            }
            if (empty($listMetadata)) {
                $listMetadata = [
                    'label' => !empty($l10nOverlay['label']) ? htmlspecialchars($l10nOverlay['label']) : htmlspecialchars($collectionData['collLabel']),
                    'description' => !empty($l10nOverlay['description']) ? $this->pi_RTEcssText($l10nOverlay['description']) : $this->pi_RTEcssText($collectionData['collDesc']),
                    'thumbnail' => htmlspecialchars($collectionData['collThumb']),
                    'options' => [
                        'source' => 'collection',
                        'select' => $id,
                        'userid' => $collectionData['userid'],
                        'params' => ['filterquery' => [['query' => 'collection_faceting:("'.$collectionData['index_name'].'")']]],
                        'core' => '',
                        'pid' => $this->conf['pages'],
                        'order' => 'title',
                        'order.asc' => TRUE
                    ]
                ];
            }
            // Split toplevel documents from volumes.
            if ($resArray['partof'] == 0) {
                // Prepare document's metadata for sorting.
                $sorting = unserialize($resArray['metadata_sorting']);
                if (!empty($sorting['type']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['type'])) {
                    $sorting['type'] = Helper::getIndexNameFromUid($sorting['type'], 'tx_dlf_structures', $this->conf['pages']);
                }
                if (!empty($sorting['owner']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['owner'])) {
                    $sorting['owner'] = Helper::getIndexNameFromUid($sorting['owner'], 'tx_dlf_libraries', $this->conf['pages']);
                }
                if (!empty($sorting['collection']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($sorting['collection'])) {
                    $sorting['collection'] = Helper::getIndexNameFromUid($sorting['collection'], 'tx_dlf_collections', $this->conf['pages']);
                }
                $toplevel[$resArray['uid']] = [
                    'u' => $resArray['uid'],
                    'h' => '',
                    's' => $sorting,
                    'p' => []
                ];
            } else {
                $subparts[$resArray['partof']][$resArray['volume_sorting']] = $resArray['uid'];
            }
        }
        // Add volumes to the corresponding toplevel documents.
        foreach ($subparts as $partof => $parts) {
            if (!empty($toplevel[$partof])) {
                ksort($parts);
                foreach ($parts as $part) {
                    $toplevel[$partof]['p'][] = ['u' => $part];
                }
            }
        }
        // Save list of documents.
        $list = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(DocumentList::class);
        $list->reset();
        $list->add(array_values($toplevel));
        $listMetadata['options']['numberOfToplevelHits'] = count($list);
        $list->metadata = $listMetadata;
        $list->save();
        // Clean output buffer.
        ob_end_clean();
        // Send headers.
        header('Location: '.\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL(['parameter' => $this->conf['targetPid']])));
        exit;
    }
}
