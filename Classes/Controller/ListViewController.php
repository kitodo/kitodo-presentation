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

use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Model\Metadata;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;

/**
 * Plugin 'List View' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @author Frank Ulrich Weber <fuw@zeutschel.de>
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

        $collection = null;
        if ($this->searchParams['collection'] && MathUtility::canBeInterpretedAsInteger($this->searchParams['collection'])) {
            $collection = $this->collectionRepository->findByUid($this->searchParams['collection']);
        }

        $widgetPage = $this->getParametersSafely('@widget_0');
        if (empty($widgetPage)) {
            $widgetPage = ['currentPage' => 1];
        }

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        $solrResults = [];
        if (is_array($this->searchParams) && !empty($this->searchParams)) {
            $solrResults = $this->documentRepository->findSolrByCollection($collection ? : null, $this->settings, $this->searchParams, $listedMetadata);
        }

        $documents = $solrResults['documents'] ? : [];
        $this->view->assign('documents', $documents);
        $rawResults = $solrResults['solrResults']['documents'] ? : [];
        $this->view->assign('numResults', count($rawResults));
        $this->view->assign('widgetPage', $widgetPage);
        $this->view->assign('lastSearch', $this->searchParams);

        $this->view->assign('sortableMetadata', $sortableMetadata);
        $this->view->assign('listedMetadata', $listedMetadata);
    }
}
