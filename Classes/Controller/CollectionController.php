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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Model\Metadata;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;

/**
 * Controller class for the plugin 'Collection'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class CollectionController extends AbstractController
{
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
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * Show a list of collections
     *
     * @return void
     */
    public function listAction()
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

        // Sort collections according to order in plugin flexform configuration
        if ($this->settings['collections']) {
            $sortedCollections = [];
            foreach (GeneralUtility::intExplode(',', $this->settings['collections']) as $uid) {
                $sortedCollections[$uid] = $this->collectionRepository->findByUid($uid);
            }
            $collections = $sortedCollections;
        } else {
            $collections = $this->collectionRepository->findAll();
        }

        if (count($collections) == 1 && empty($this->settings['dont_show_single']) && is_array($collections)) {
            $this->forward('show', null, null, ['collection' => array_pop($collections)]);
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
            $params['query'] = $solr_query . ' AND partof:0 AND toplevel:true';
            $partOfNothing = $solr->search_raw($params);

            $params['query'] = $solr_query . ' AND NOT partof:0 AND toplevel:true';
            $partOfSomething = $solr->search_raw($params);
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

        $this->view->assign('collections', $processedCollections);
    }

    /**
     * Show a single collection with description and all its documents.
     *
     * @access protected
     *
     * @param \Kitodo\Dlf\Domain\Model\Collection $collection: The collection object
     *
     * @return void
     */
    public function showAction(\Kitodo\Dlf\Domain\Model\Collection $collection)
    {
        $searchParams = $this->getParametersSafely('searchParameter');

        // Instaniate the Solr. Without Solr present, we can't do anything.
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return;
        }

        // Pagination of Results: Pass the currentPage to the fluid template to calculate current index of search result.
        $widgetPage = $this->getParametersSafely('@widget_0');
        if (empty($widgetPage)) {
            $widgetPage = ['currentPage' => 1];
        }

        $searchParams['collection'] = $collection;
        // If a targetPid is given, the results will be shown by ListView on the target page.
        if (!empty($this->settings['targetPid'])) {
            $this->redirect('main', 'ListView', null,
                [
                    'searchParameter' => $searchParams,
                    'widgetPage' => $widgetPage
                ], $this->settings['targetPid']
            );
        }

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        // get all documents of given collection
        $solrResults = $this->documentRepository->findSolrByCollection($collection, $this->settings, $searchParams, $listedMetadata);

        $this->view->assign('viewData', $this->viewData);
        $this->view->assign('documents', $solrResults);
        $this->view->assign('collection', $collection);
        $this->view->assign('widgetPage', $widgetPage);
        $this->view->assign('lastSearch', $searchParams);
        $this->view->assign('sortableMetadata', $sortableMetadata);
        $this->view->assign('listedMetadata', $listedMetadata);
    }

    /**
     * This is an uncached helper action to make sorting possible on collection single views.
     *
     * @access protected
     *
     * @return void
     */
    public function showSortedAction()
    {
        // if search was triggered, get search parameters from POST variables
        $searchParams = $this->getParametersSafely('searchParameter');

        $collection = null;
        if ($searchParams['collection']['__identity'] && MathUtility::canBeInterpretedAsInteger($searchParams['collection']['__identity'])) {
            $collection = $this->collectionRepository->findByUid($searchParams['collection']['__identity']);
        }

        // output is done by show action
        $this->forward('show', null, null, ['searchParameter' => $searchParams, 'collection' => $collection]);

    }
}
