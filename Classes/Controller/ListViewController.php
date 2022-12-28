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
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\MathUtility;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;

/**
 * Controller class for the plugin 'ListView'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @author Frank Ulrich Weber <fuw@zeutschel.de>
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ListViewController extends AbstractController
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
     * @var array $this->searchParams: The current search parameter
     * @access protected
     */
    protected $searchParams;

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $this->searchParams = $this->getParametersSafely('searchParameter');

        // extract collection(s) from collection parameter
        $collection = null;
        if ($this->searchParams['collection']) {
            foreach(explode(',', $this->searchParams['collection']) as $collectionEntry) {
                $collection[] = $this->collectionRepository->findByUid($collectionEntry);
            }
        }

        // Get current page from request data because the parameter is shared between plugins
        $currentPage = $this->requestData['page'];
        if (empty($currentPage)) {
            $currentPage = 1;
        }
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'widgetPage', $widgetPage);

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        $solrResults = null;
        $numResults = 0;
        if (is_array($this->searchParams) && !empty($this->searchParams)) {
            $solrResults = $this->documentRepository->findSolrByCollection($collection ? : null, $this->settings, $this->searchParams, $listedMetadata);
            $numResults = $solrResults->getNumFound();

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
        $this->view->assign('numResults', $numResults);
        $this->view->assign('page', $currentPage);
        $this->view->assign('lastSearch', $this->searchParams);

        $this->view->assign('sortableMetadata', $sortableMetadata);
        $this->view->assign('listedMetadata', $listedMetadata);
    }
}
