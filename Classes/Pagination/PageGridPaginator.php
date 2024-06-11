<?php

declare(strict_types=1);

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;

final class PageGridPaginator extends AbstractPaginator
{
    /**
     * @var array
     */
    private array $items;

    /**
     * @var int
     */
    public int $publicItemsPerPage;

    /**
     * @var array
     */
    private array $paginatedItems = [];

    public function __construct(
        array $items,
        int $currentPageNumber = 1,
        int $itemsPerPage = 10
    ) {
        $this->items = $items;
        $this->publicItemsPerPage = $itemsPerPage;
        $this->setCurrentPageNumber((int) ceil(($currentPageNumber / $this->publicItemsPerPage)));
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    /**
     * @return iterable|array
     */
    public function getPaginatedItems(): iterable
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->paginatedItems = array_slice($this->items, $offset, $itemsPerPage);
    }

    protected function getTotalAmountOfItems(): int
    {
        return count($this->items);
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }

    /**
     * @return int
     */
    public function getPublicItemsPerPage(): int
    {
        return $this->publicItemsPerPage;
    }

    /**
     * @param int $publicItemsPerPage
     */
    public function setPublicItemsPerPage(int $publicItemsPerPage): void
    {
        $this->publicItemsPerPage = $publicItemsPerPage;
    }


}
