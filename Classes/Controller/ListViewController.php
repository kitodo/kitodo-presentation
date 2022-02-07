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

use Kitodo\Dlf\Domain\Model\Metadata;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;

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
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $searchRequestData = GeneralUtility::_GPmerged('tx_dlf_search');
        $collectionRequestData = GeneralUtility::_GPmerged('tx_dlf_collection');

        // ABTODO: This plugin may be called from search and collection plugin...

        $searchParams = $searchRequestData['searchParameter'];
        $widgetPage = $searchRequestData['widgetPage'];
        // solrcore is configured in search plugin and must be passed by parameter
        $this->settings['solrcore'] = $searchRequestData['solrcore'];

        // get all sortable metadata records
        $sortableMetadata = $this->metadataRepository->findByIsSortable(true);

        // get all metadata records to be shown in results
        $listedMetadata = $this->metadataRepository->findByIsListed(true);

        if (is_array($searchParams) && !empty($searchParams)) {
            $solrResults = $this->documentRepository->findSolrByCollection('', $this->settings, $searchParams, $listedMetadata);
        }

        $documents = $solrResults['documents'] ? : [];
        $this->view->assign('documents', $documents);
        $rawResults = $solrResults['solrResults'] ? : [];
        $this->view->assign('numResults', count($rawResults['documents']));
        $this->view->assign('widgetPage', $widgetPage);
        $this->view->assign('lastSearch', $searchParams);

        $this->view->assign('sortableMetadata', $sortableMetadata);
        $this->view->assign('listedMetadata', $listedMetadata);
    }
}
