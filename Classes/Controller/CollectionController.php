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
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
     * @access protected
     * @var array The current search parameter
     */
    protected $searchParams = [];

    /**
     * Show a list of collections
     *
     * @access public
     *
     * @return void
     */
    public function listAction(): void
    {
        // Quit without doing anything if required variables are not set.
        if (empty($this->settings['solrcore'])) {
            $this->logger->warning('Incomplete plugin configuration for SOLR. Please check the plugin settings for UID of SOLR core.');
            return;
        }

        $collections = [];

        // Sort collections according to order in plugin flexform configuration
        if ($this->settings['collections']) {
            foreach (GeneralUtility::intExplode(',', $this->settings['collections']) as $uid) {
                $collections[$uid] = $this->collectionRepository->findByUid($uid);
            }
        } else {
            $collections = $this->collectionRepository->findAll();
        }

        if ($this->settings['showSingle'] == 1) {
            if (count($collections) == 1 && is_array($collections)) {
                $this->forward('show', null, null, ['collection' => array_pop($collections)]);
            } else {
                $searchParams = $this->getParametersSafely('searchParameter');
                $collection = $this->collectionRepository->findByUid($searchParams['collection']);
                $this->forward('show', null, null, ['collection' => $collection]);
            }
        }

        $processedCollections = $this->processCollections($collections);

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
     * @param ?Collection $collection The collection object
     *
     * @return void
     */
    public function showAction(?Collection $collection = null): void
    {
        // Quit without doing anything if required variables are not set.
        if (empty($this->settings['solrcore'])) {
            $this->logger->warning('Incomplete plugin configuration for SOLR. Please check the plugin settings for UID of SOLR core.');
            return;
        }

        $this->searchParams = $this->getParametersSafely('searchParameter');
        $searchRequestData = GeneralUtility::_GPmerged('tx_dlf_search');

        if (isset($searchRequestData['searchParameter']) && is_array($searchRequestData['searchParameter'])) {
            $this->searchParams = array_merge($this->searchParams ?: [], $searchRequestData['searchParameter']);
            $this->request->getAttribute('frontend.user')->setKey('ses', 'search', $this->searchParams);
        }

        if (!isset($this->searchParams['collection']) && !isset($collection)) {
            $this->logger->warning('Collection is not set.');
            return;
        }

        // Get current page from request data because the parameter is shared between plugins
        $currentPage = $this->requestData['page'] ?? 1;

        if (!isset($collection)) {
            $collection = $this->collectionRepository->findByUid($this->searchParams['collection']);
        } else {
            $this->searchParams['collection'] = $collection->getUid();
        }

        // If a targetPid is given, the results will be shown by Collection on the target page.
        if (!empty($this->settings['targetPid'])) {
            $this->redirect(
                'show',
                'Collection',
                null,
                [
                    'collection' => $collection
                ],
                $this->settings['targetPid']
            );
        }

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        $solrResults = $this->documentRepository->findSolrByCollection($collection, $this->settings, $this->searchParams, $listedMetadata);

        $itemsPerPage = $this->settings['list']['paginate']['itemsPerPage'] ?? 25;

        $solrPaginator = new SolrPaginator($solrResults, $currentPage, $itemsPerPage);
        $simplePagination = new SimplePagination($solrPaginator);

        $pagination = $this->buildSimplePagination($simplePagination, $solrPaginator);
        $this->view->assignMultiple([ 'pagination' => $pagination, 'paginator' => $solrPaginator ]);

        $this->view->assign('viewData', $this->viewData);
        $this->view->assign('countDocuments', $solrResults->count());
        $this->view->assign('countResults', $solrResults->getNumFound());
        $this->view->assign('collection', $collection);
        $this->view->assign('page', $currentPage);
        $this->view->assign('lastSearch', $this->searchParams);
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
        if ($searchParams['collection'] && MathUtility::canBeInterpretedAsInteger($searchParams['collection'])) {
            $collection = $this->collectionRepository->findByUid($searchParams['collection']);
        }

        // output is done by show action
        $this->forward('show', null, null, ['collection' => $collection, 'searchParams' => $searchParams]);

    }

    /**
     * Processes collections for displaying in the frontend.
     *
     * @access private
     *
     * @param QueryResultInterface|array|object $collections to be processed
     *
     * @return array
     */
    private function processCollections($collections): array
    {
        $solr = Solr::getInstance($this->settings['solrcore']);

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
