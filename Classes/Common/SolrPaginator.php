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

namespace Kitodo\Dlf\Common;

use Kitodo\Dlf\Common\Solr\SolrSearch;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;

class SolrPaginator extends AbstractPaginator
{
    /**
     * @var SolrSearch
     */
    private SolrSearch $solrSearch;

    /**
     * @var array
     */
    private array $paginatedItems = [];

    public function __construct(
        SolrSearch $solrSearch,
        int $currentPageNumber = 1,
        int $itemsPerPage = 25
    ) {
        $this->solrSearch = $solrSearch;
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->solrSearch->submit($offset, $itemsPerPage);
        foreach ($this->solrSearch as $item) {
            $this->paginatedItems[] = $item;
        }
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->solrSearch->count();
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }

    public function getPaginatedItems(): iterable
    {
        return $this->paginatedItems;
    }
}
