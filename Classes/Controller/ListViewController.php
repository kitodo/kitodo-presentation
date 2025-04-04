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
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller class for the plugin 'ListView'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ListViewController extends AbstractController
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
     * The main method of the plugin
     *
     * @access public
     *
     * @return void
     */
    public function mainAction(): void
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

        // Get current page from request data because the parameter is shared between plugins
        $currentPage = $this->requestData['page'] ?? 1;

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        if (!empty($this->searchParams)) {
            $solrResults = $this->documentRepository->findSolrWithoutCollection($this->settings, $this->searchParams, $listedMetadata);

            $itemsPerPage = $this->settings['list']['paginate']['itemsPerPage'] ?? 25;

            $solrPaginator = new SolrPaginator($solrResults, $currentPage, $itemsPerPage);
            $simplePagination = new SimplePagination($solrPaginator);

            $pagination = $this->buildSimplePagination($simplePagination, $solrPaginator);
            $this->view->assignMultiple([ 'pagination' => $pagination, 'paginator' => $solrPaginator ]);
        }

        $this->view->assign('viewData', $this->viewData);
        $this->view->assign('countDocuments', !empty($solrResults) ? $solrResults->count() : 0);
        $this->view->assign('countResults', !empty($solrResults) ? $solrResults->getNumFound() : 0);
        $this->view->assign('page', $currentPage);
        $this->view->assign('lastSearch', $this->searchParams);
        $this->view->assign('sortableMetadata', $sortableMetadata);
        $this->view->assign('listedMetadata', $listedMetadata);
    }
}
