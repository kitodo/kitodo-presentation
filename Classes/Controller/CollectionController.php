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

use Kitodo\Dlf\Common\SolrPaginator;
use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Domain\Model\Collection;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;

/**
 * Controller class for the plugin 'Collection'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class CollectionController extends AbstractController
{
    /**
     * @access protected
     * @var CollectionRepository
     */
    protected CollectionRepository $collectionRepository;

    /**
     * @access public
     *
     * @param CollectionRepository $collectionRepository
     *
     * @return void
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository): void
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @access protected
     * @var MetadataRepository
     */
    protected MetadataRepository $metadataRepository;

    /**
     * @access public
     *
     * @param MetadataRepository $metadataRepository
     *
     * @return void
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository): void
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * Show a list of collections
     *
     * @access public
     *
     * @return void
     */
    public function listAction(): void
    {
        $solr = Solr::getInstance($this->settings['solrcore']);

        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return;
        }

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

        $processedCollections = $this->processCollections($collections, $solr);

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
     * @param Collection $collection The collection object
     *
     * @return void
     */
    public function showAction(Collection $collection): void
    {
        $searchParams = $this->getParametersSafely('searchParameter');

        // Instantiate the Solr. Without Solr present, we can't do anything.
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return;
        }

        // Pagination of Results: Pass the currentPage to the fluid template to calculate current index of search result.
        $currentPage = $this->getParametersSafely('page');
        if (empty($currentPage)) {
            $currentPage = 1;
        }

        $searchParams['collection'] = $collection;
        // If a targetPid is given, the results will be shown by ListView on the target page.
        if (!empty($this->settings['targetPid'])) {
            $this->redirect('main', 'ListView', null,
                [
                    'searchParameter' => $searchParams,
                    'page' => $currentPage
                ], $this->settings['targetPid']
            );
        }

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        // get all documents of given collection
        $solrResults = null;
        if (is_array($searchParams) && !empty($searchParams)) {
            $solrResults = $this->documentRepository->findSolrByCollection($collection, $this->settings, $searchParams, $listedMetadata);

            $itemsPerPage = $this->settings['list']['paginate']['itemsPerPage'];
            if (empty($itemsPerPage)) {
                $itemsPerPage = 25;
            }
            $solrPaginator = new SolrPaginator($solrResults, $currentPage, $itemsPerPage);
            $simplePagination = new SimplePagination($solrPaginator);

            $pagination = $this->buildSimplePagination($simplePagination, $solrPaginator);
            $this->view->assignMultiple([ 'pagination' => $pagination, 'paginator' => $solrPaginator ]);
        }

        $this->view->assign('viewData', $this->viewData);
        $this->view->assign('documents', $solrResults);
        $this->view->assign('collection', $collection);
        $this->view->assign('page', $currentPage);
        $this->view->assign('lastSearch', $searchParams);
        $this->view->assign('sortableMetadata', $sortableMetadata);
        $this->view->assign('listedMetadata', $listedMetadata);
    }

    /**
     * This is an uncached helper action to make sorting possible on collection single views.
     *
     * @access public
     *
     * @return void
     */
    public function showSortedAction(): void
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

    /**
     * Processes collections for displaying in the frontend.
     *
     * @access private
     *
     * @param QueryResultInterface|array|object $collections to be processed
     * @param Solr $solr for query
     *
     * @return array
     */
    private function processCollections($collections, Solr $solr): array
    {
        $processedCollections = [];

        // Process results.
        foreach ($collections as $collection) {
            $solrQuery = '';
            if ($collection->getIndexSearch() != '') {
                $solrQuery .= '(' . $collection->getIndexSearch() . ')';
            } else {
                $solrQuery .= 'collection:("' . Solr::escapeQuery($collection->getIndexName()) . '")';
            }

            // We only care about the UID and partOf in the results and want them sorted
            $params = [
                'fields' => 'uid,partof',
                'sort' => [
                    'uid' => 'asc'
                ]
            ];
            // virtual collection might yield documents, that are not toplevel true or partof anything
            if ($collection->getIndexSearch()) {
                $params['query'] = $solrQuery;
            } else {
                $params['query'] = $solrQuery . ' AND partof:0 AND toplevel:true';
            }
            $partOfNothing = $solr->searchRaw($params);

            $params['query'] = $solrQuery . ' AND NOT partof:0 AND toplevel:true';
            $partOfSomething = $solr->searchRaw($params);

            $collectionInfo = [];
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

            // Generate random but unique array key taking amount of documents into account.
            do {
                $key = ($collection->getPriority() * 1000) + random_int(0, 1000);
            } while (!empty($processedCollections[$key]));

            $processedCollections[$key]['collection'] = $collection;
            $processedCollections[$key]['info'] = $collectionInfo;
        }

        // Randomize sorting?
        if (!empty($this->settings['randomize'])) {
            ksort($processedCollections, SORT_NUMERIC);
        }

        return $processedCollections;
    }
}
