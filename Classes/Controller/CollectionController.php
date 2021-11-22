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
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Model\Collection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;

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
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @param CollectionRepository $collectionRepository
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

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
            $this->showSingleCollection($this->collectionRepository->findByUid($collection[0]));
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
        $solr = Solr::getInstance($this->settings['solrcore']);

        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return;
        }
        // We only care about the UID and partOf in the results and want them sorted
        $params['fields'] = 'uid,partof';
        $params['sort'] = ['uid' => 'asc'];
        $collections = [];

        // Sort collections according to flexform configuration
        if ($this->settings['collections']) {
            $sortedCollections = [];
            foreach (GeneralUtility::intExplode(',', $this->settings['collections']) as $uid) {
                $sortedCollections[$uid] = $this->collectionRepository->findByUid($uid);
            }
            $collections = $sortedCollections;
        }

        if (count($collections) == 1 && empty($this->settings['dont_show_single'])) {
            $this->showSingleCollection(array_pop($collections));
        }

        $processedCollections = [];

        // Process results.
        foreach ($collections as $collection) {
            $solr_query = '';
            if ($collection->getIndexSearch() != '') {
                $solr_query .= '(' . $collection->getIndexSearch() . ')';
            } else {
                $solr_query .= 'collection:("' . Solr::escapeQuery($collection->getIndexName()) . '")';
            }
            $partOfNothing = $solr->search_raw($solr_query . ' AND partof:0 AND toplevel:true', $params);
            $partOfSomething = $solr->search_raw($solr_query . ' AND NOT partof:0 AND toplevel:true', $params);
            // Titles are all documents that are "root" elements i.e. partof == 0
            $collectionInfo['titles'] = [];
            foreach ($partOfNothing as $doc) {
                $collectionInfo['titles'][$doc->uid] = $doc->uid;
            }
            // Volumes are documents that are both
            // a) "leaf" elements i.e. partof != 0
            // b) "root" elements that are not referenced by other documents ("root" elements that have no descendants)
            $collectionInfo['volumes'] = $collectionInfo['titles'];
            foreach ($partOfSomething as $doc) {
                $collectionInfo['volumes'][$doc->uid] = $doc->uid;
                // If a document is referenced via partof, it’s not a volume anymore.
                unset($collectionInfo['volumes'][$doc->partof]);
            }

            // Generate random but unique array key taking priority into account.
            do {
                $_key = ($collectionInfo['priority'] * 1000) + mt_rand(0, 1000);
            } while (!empty($processedCollections[$_key]));

            $processedCollections[$_key]['collection'] = $collection;
            $processedCollections[$_key]['info'] = $collectionInfo;
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
     * @param \Kitodo\Dlf\Domain\Model\Collection The collection object
     *
     * @return void
     */
    protected function showSingleCollection(\Kitodo\Dlf\Domain\Model\Collection $collection)
    {
        // access storagePid from TypoScript
        $pageSettings = $this->configurationManager->getConfiguration($this->configurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $this->settings['pages'] = $pageSettings["plugin."]["tx_dlf."]["persistence."]["storagePid"];

        // Fetch corresponding document UIDs from Solr.
        if ($collection->getIndexSearch() != '') {
            $solr_query = '(' . $collection->getIndexSearch() . ')';
        } else {
            $solr_query = 'collection:("' . Solr::escapeQuery($collection->getIndexName()) . '") AND toplevel:true';
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

        $this->settings['documentSets'] = implode(',', $documentSet);

        $documents = $this->documentRepository->findDocumentsBySettings($this->settings);

        $toplevel = [];
        $subparts = [];
        $listMetadata = [];
        // Process results.
        /** @var Document $document */
        foreach ($documents as $document) {
            if (empty($listMetadata)) {
                $listMetadata = [
                    'label' => htmlspecialchars($collection->getLabel()),
                    'description' => $collection->getDescription(),
                    'thumbnail' => htmlspecialchars($collection->getThumbnail()),
                    'options' => [
                        'source' => 'collection',
                        'select' => $id,
                        'userid' => $collection->getFeCruserId(),
                        'params' => ['filterquery' => [['query' => 'collection_faceting:("' . $collection->getIndexName() . '")']]],
                        'core' => '',
                        'order' => 'title',
                        'order.asc' => true
                    ]
                ];
            }
            // Prepare document's metadata for sorting.
            $sorting = unserialize($document->getMetadataSorting());
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
            if ($document->getPartof() == 0) {
                $toplevel[$document->getUid()] = [
                    'u' => $document->getUid(),
                    'h' => '',
                    's' => $sorting,
                    'p' => []
                ];
            } else {
                // volume_sorting should be always set - but it's not a required field. We append the uid to the array key to make it always unique.
                $subparts[$document->getPartof()][$document->getVolumeSorting() . str_pad($document->getUid(), 9, '0', STR_PAD_LEFT)] = [
                    'u' => $document->getUid(),
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
