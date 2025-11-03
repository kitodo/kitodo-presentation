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

use Kitodo\Dlf\Pagination\PageGridPagination;
use Kitodo\Dlf\Pagination\PageGridPaginator;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller class for the plugin 'Page Grid'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class PageGridController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        $this->loadDocument();
        if (
            $this->isDocMissingOrEmpty()
            || empty($this->useGroupsConfiguration->getThumbnail())
        ) {
            // Quit without doing anything if required variables are not set.
            return $this->htmlResponse();
        }

        // Access cachemanager for pagegrid
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_dlf_pagegrid');
        $cacheKey = 'dlf_' . md5($this->document->getCurrentDocument()->recordId);
        $cachedData = $cache->get($cacheKey);

        $entryArray = [];

        if ($cachedData) {
            $entryArray = $cachedData; // Load from cache
        } else {
            $numPages = $this->document->getCurrentDocument()->numPages;
            // Iterate through visible page set and display thumbnails.
            for ($i = 1; $i <= $numPages; $i++) {
                $foundEntry = $this->getEntry($i);
                $foundEntry['state'] = ($i == $this->requestData['page']) ? 'cur' : 'no';
                $entryArray[] = $foundEntry;
            }
            $cache->set($cacheKey, $entryArray);
        }

        // Get current page from request data because the parameter is shared between plugins
        $currentPage = $this->requestData['page'] ?? 1;

        $itemsPerPage = $this->settings['paginate']['itemsPerPage'] ?? 25;

        $pageGridPaginator = new PageGridPaginator($entryArray, $currentPage, $itemsPerPage);
        $pageGridPagination = new PageGridPagination($pageGridPaginator);

        $pagination = $this->buildSimplePagination($pageGridPagination, $pageGridPaginator);
        $this->view->assignMultiple([ 'pagination' => $pagination, 'paginator' => $pageGridPaginator ]);

        $this->view->assign('docUid', $this->requestData['id']);

        return $this->htmlResponse();
    }

    /**
     * Renders entry for one page of the current document.
     *
     * @access protected
     *
     * @param int $number The page to render
     *
     * @return array The rendered entry ready for fluid
     */
    protected function getEntry(int $number): array
    {
        $entry = [];

        // Set pagination.
        $entry['pagination'] = htmlspecialchars($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$number]]['orderlabel']);
        $entry['page'] = $number;
        $entry['thumbnail'] = '';

        // Get thumbnail or placeholder.
        $useGroups = $this->useGroupsConfiguration->getThumbnail();
        if (is_array($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$number]]['files'])) {
            if (array_intersect($useGroups, array_keys($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$number]]['files'])) !== []) {
                while ($useGroup = array_shift($useGroups)) {
                    if (!empty($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$number]]['files'][$useGroup])) {
                        $entry['thumbnail'] = $this->document->getCurrentDocument()->getFileLocationInUsegroup($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$number]]['files'][$useGroup], $useGroup);
                        break;
                    }
                }
            }
        }
        return $entry;
    }
}
