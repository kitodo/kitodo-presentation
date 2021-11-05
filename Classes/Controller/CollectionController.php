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

use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

class CollectionController extends AbstractController
{
    /**
     * This holds the hook objects
     *
     * @var array
     * @access protected
     */
    protected $hookObjects = [];

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        // access to GET parameter tx_dlf_collection['collection']
        $requestData = $this->request->getArguments();

        $collection = $requestData['collection'];

        // Quit without doing anything if required configuration variables are not set.
        if (empty($this->settings['pages'])) {
            $this->logger->warning('Incomplete plugin configuration');
        }

        // Get hook objects.
        // TODO: $this->hookObjects = Helper::getHookObjects($this->scriptRelPath);

        if ($collection) {
            $this->showSingleCollection($collection);
        } else {
            $this->showCollectionList();
        }

        $this->view->assign('currentPageUid', $GLOBALS['TSFE']->id);
    }

    /**
     * Builds a collection list
     * @return void
     */
    protected function showCollectionList()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        $selectedCollections = $queryBuilder->expr()->neq('tx_dlf_collections.uid', 0);
        $orderBy = 'tx_dlf_collections.label';
        $showUserDefinedColls = '';
        // Handle collections set by configuration.
        if ($this->settings['collections']) {
            if (
                count(explode(',', $this->settings['collections'])) == 1
                && empty($this->settings['dont_show_single'])
            ) {
                $this->showSingleCollection(intval(trim($this->settings['collections'], ' ,')));
            }
            $selectedCollections = $queryBuilder->expr()->in('tx_dlf_collections.uid', implode(',', GeneralUtility::intExplode(',', $this->settings['collections'])));
        }

        // Should user-defined collections be shown?
        if (empty($this->settings['show_userdefined'])) {
            $showUserDefinedColls = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        } elseif ($this->settings['show_userdefined'] > 0) {
            if (!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
                $showUserDefinedColls = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', intval($GLOBALS['TSFE']->fe_user->user['uid']));
            } else {
                $showUserDefinedColls = $queryBuilder->expr()->neq('tx_dlf_collections.fe_cruser_id', 0);
            }
        }

        // Get collections.
        $queryBuilder
            ->select(
                'tx_dlf_collections.uid AS uid', // required by getRecordOverlay()
                'tx_dlf_collections.pid AS pid', // required by getRecordOverlay()
                'tx_dlf_collections.sys_language_uid AS sys_language_uid', // required by getRecordOverlay()
                'tx_dlf_collections.index_name AS index_name',
                'tx_dlf_collections.index_search as index_query',
                'tx_dlf_collections.label AS label',
                'tx_dlf_collections.thumbnail AS thumbnail',
                'tx_dlf_collections.description AS description',
                'tx_dlf_collections.priority AS priority'
            )
            ->from('tx_dlf_collections')
            ->where(
                $selectedCollections,
                $showUserDefinedColls,
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->settings['pages'])),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('tx_dlf_collections.sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('tx_dlf_collections.sys_language_uid', $GLOBALS['TSFE']->sys_language_uid)
                    ),
                    $queryBuilder->expr()->eq('tx_dlf_collections.l18n_parent', 0)
                )
            )
            ->orderBy($orderBy);

        $result = $queryBuilder->execute();
        $count = $queryBuilder->count('uid')->execute()->fetchColumn(0);

        if ($count == 1 && empty($this->settings['dont_show_single'])) {
            $resArray = $result->fetch();
            $this->showSingleCollection(intval($resArray['uid']));
        }
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            //return $content;
        }
        // We only care about the UID and partOf in the results and want them sorted
        $params['fields'] = 'uid,partof';
        $params['sort'] = ['uid' => 'asc'];
        $collections = [];

        // Get language overlay if on alterative website language.
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        while ($collectionData = $result->fetch()) {
            if ($collectionData['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content) {
                $collections[$collectionData['uid']] = $pageRepository->getRecordOverlay('tx_dlf_collections', $collectionData, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
                // keep the index_name of the default language
                $collections[$collectionData['uid']]['index_name'] = $collectionData['index_name'];
            } else {
                $collections[$collectionData['uid']] = $collectionData;
            }
        }
        // Sort collections according to flexform configuration
        if ($this->settings['collections']) {
            $sortedCollections = [];
            foreach (GeneralUtility::intExplode(',', $this->settings['collections']) as $uid) {
                $sortedCollections[$uid] = $collections[$uid];
            }
            $collections = $sortedCollections;
        }

        $processedCollections = [];

        // Process results.
        foreach ($collections as $collection) {
            $solr_query = '';
            if ($collection['index_query'] != '') {
                $solr_query .= '(' . $collection['index_query'] . ')';
            } else {
                $solr_query .= 'collection:("' . $collection['index_name'] . '")';
            }
            $partOfNothing = $solr->search_raw($solr_query . ' AND partof:0 AND toplevel:true', $params);
            $partOfSomething = $solr->search_raw($solr_query . ' AND NOT partof:0 AND toplevel:true', $params);
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
            } while (!empty($processedCollections[$_key]));

            $collection['countTitles'] = count($collection['titles']);
            $collection['countVolumes'] = count($collection['volumes']);

            $processedCollections[$_key] = $collection;
        }

        // Randomize sorting?
        if (!empty($this->settings['randomize'])) {
            ksort($processedCollections, SORT_NUMERIC);
        }

        // TODO: Hook for getting custom collection hierarchies/subentries (requested by SBB).
        /*    foreach ($this->hookObjects as $hookObj) {
                if (method_exists($hookObj, 'showCollectionList_getCustomCollectionList')) {
                    $hookObj->showCollectionList_getCustomCollectionList($this, $this->settings['templateFile'], $content, $markerArray);
                }
            }
        */

        $this->view->assign('collections', $processedCollections);
    }

    /**
     * Builds a collection's list
     *
     * @access protected
     *
     * @param int $id: The collection's UID
     *
     * @return void
     */
    protected function showSingleCollection($id)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_dlf_collections');

        $additionalWhere = '';
        // Should user-defined collections be shown?
        if (empty($this->settings['show_userdefined'])) {
            $additionalWhere = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        } elseif ($this->settings['show_userdefined'] > 0) {
            $additionalWhere = $queryBuilder->expr()->neq('tx_dlf_collections.fe_cruser_id', 0);
        }

        // Get collection information from DB
        $collection = $queryBuilder
            ->select(
                'tx_dlf_collections.uid AS uid', // required by getRecordOverlay()
                'tx_dlf_collections.pid AS pid', // required by getRecordOverlay()
                'tx_dlf_collections.sys_language_uid AS sys_language_uid', // required by getRecordOverlay()
                'tx_dlf_collections.index_name AS index_name',
                'tx_dlf_collections.index_search as index_search',
                'tx_dlf_collections.label AS label',
                'tx_dlf_collections.description AS description',
                'tx_dlf_collections.thumbnail AS thumbnail',
                'tx_dlf_collections.fe_cruser_id'
            )
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_collections.uid', intval($id)),
                $additionalWhere,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('tx_dlf_collections.sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('tx_dlf_collections.sys_language_uid', $GLOBALS['TSFE']->sys_language_uid)
                    ),
                    $queryBuilder->expr()->eq('tx_dlf_collections.l18n_parent', 0)
                ),
                Helper::whereExpression('tx_dlf_collections')
            )
            ->setMaxResults(1)
            ->execute();

        // Get language overlay if on alterative website language.
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        if ($resArray = $collection->fetch()) {
            if ($resArray['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content) {
                $collectionData = $pageRepository->getRecordOverlay('tx_dlf_collections', $resArray, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
                // keep the index_name of the default language
                $collectionData['index_name'] = $resArray['index_name'];
            } else {
                $collectionData = $resArray;
            }
        } else {
            $this->logger->warning('No collection with UID ' . $id . ' found.');
            return;
        }
        // Fetch corresponding document UIDs from Solr.
        if ($collectionData['index_search'] != '') {
            $solr_query = '(' . $collectionData['index_search'] . ')';
        } else {
            $solr_query = 'collection:("' . $collectionData['index_name'] . '") AND toplevel:true';
        }
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return;
        }
        $params['fields'] = 'uid';
        $params['sort'] = ['uid' => 'asc'];
        $solrResult = $solr->search_raw($solr_query, $params);
        // Initialize array
        $documentSet = [];
        foreach ($solrResult as $doc) {
            if ($doc->uid) {
                $documentSet[] = $doc->uid;
            }
        }
        $documentSet = array_unique($documentSet);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_dlf_documents');
        // Fetch document info for UIDs in $documentSet from DB
        $documents = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.metadata_sorting AS metadata_sorting',
                'tx_dlf_documents.volume_sorting AS volume_sorting',
                'tx_dlf_documents.partof AS partof'
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->settings['pages'])),
                $queryBuilder->expr()->in('tx_dlf_documents.uid', $documentSet),
                Helper::whereExpression('tx_dlf_documents')
            )
            ->execute();

        $toplevel = [];
        $subparts = [];
        $listMetadata = [];
        // Process results.
        while ($resArray = $documents->fetch()) {
            if (empty($listMetadata)) {
                $listMetadata = [
                    'label' => htmlspecialchars($collectionData['label']),
                    'description' => $collectionData['description'],
                    'thumbnail' => htmlspecialchars($collectionData['thumbnail']),
                    'options' => [
                        'source' => 'collection',
                        'select' => $id,
                        'userid' => $collectionData['userid'],
                        'params' => ['filterquery' => [['query' => 'collection_faceting:("' . $collectionData['index_name'] . '")']]],
                        'core' => '',
                        'pid' => $this->settings['pages'],
                        'order' => 'title',
                        'order.asc' => true
                    ]
                ];
            }
            // Prepare document's metadata for sorting.
            $sorting = unserialize($resArray['metadata_sorting']);
            if (!empty($sorting['type']) && MathUtility::canBeInterpretedAsInteger($sorting['type'])) {
                $sorting['type'] = Helper::getIndexNameFromUid($sorting['type'], 'tx_dlf_structures', $this->settings['pages']);
            }
            if (!empty($sorting['owner']) && MathUtility::canBeInterpretedAsInteger($sorting['owner'])) {
                $sorting['owner'] = Helper::getIndexNameFromUid($sorting['owner'], 'tx_dlf_libraries', $this->settings['pages']);
            }
            if (!empty($sorting['collection']) && MathUtility::canBeInterpretedAsInteger($sorting['collection'])) {
                $sorting['collection'] = Helper::getIndexNameFromUid($sorting['collection'], 'tx_dlf_collections', $this->settings['pages']);
            }
            // Split toplevel documents from volumes.
            if ($resArray['partof'] == 0) {
                $toplevel[$resArray['uid']] = [
                    'u' => $resArray['uid'],
                    'h' => '',
                    's' => $sorting,
                    'p' => []
                ];
            } else {
                // volume_sorting should be always set - but it's not a required field. We append the uid to the array key to make it always unique.
                $subparts[$resArray['partof']][$resArray['volume_sorting'] . str_pad($resArray['uid'], 9, '0', STR_PAD_LEFT)] = [
                    'u' => $resArray['uid'],
                    'h' => '',
                    's' => $sorting,
                    'p' => []
                ];
            }
        }
        // Add volumes to the corresponding toplevel documents.
        foreach ($subparts as $partof => $parts) {
            ksort($parts);
            foreach ($parts as $part) {
                if (!empty($toplevel[$partof])) {
                    $toplevel[$partof]['p'][] = ['u' => $part['u']];
                } else {
                    $toplevel[$part['u']] = $part;
                }
            }
        }
        // Save list of documents.
        $list = GeneralUtility::makeInstance(DocumentList::class);
        $list->reset();
        $list->add(array_values($toplevel));
        $listMetadata['options']['numberOfToplevelHits'] = count($list);
        $list->metadata = $listMetadata;
        $list->sort('title');
        $list->save();
        // Clean output buffer.
        ob_end_clean();

        $uri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($this->settings['targetPid'])
            ->uriFor('main', [], 'ListView', 'dlf', 'ListView');
        $this->redirectToURI($uri);
    }
}
