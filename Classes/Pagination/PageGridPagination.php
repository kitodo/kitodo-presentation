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

use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;


final class PageGridPagination implements PaginationInterface
{
    /**
     * @var PageGridPaginator
     */
    protected $paginator;

    public function __construct(PaginatorInterface $paginator)
    {
        // @phpstan-ignore-next-line
        $this->paginator = $paginator;
    }

    public function getPreviousPageNumber(): ?int
    {
        $previousPage = (int) (($this->paginator->getCurrentPageNumber() - 1) * $this->paginator->getPublicItemsPerPage()) - ($this->paginator->getPublicItemsPerPage() - 1);

        if ($previousPage > $this->paginator->getNumberOfPages()) {
            return null;
        }

        return $previousPage >= $this->getFirstPageNumber()
            ? $previousPage
            : null
            ;
    }

    public function getNextPageNumber(): ?int
    {
        $nextPage = (int) ($this->paginator->getCurrentPageNumber() * count($this->paginator->getPaginatedItems()) + 1);

        return $nextPage <= $this->paginator->getNumberOfPages()
            ? $nextPage
            : null
            ;
    }

    public function getFirstPageNumber(): int
    {
        return 1;
    }

    public function getLastPageNumber(): int
    {
        return $this->paginator->getNumberOfPages();
    }

    public function getStartRecordNumber(): int
    {
        if ($this->paginator->getCurrentPageNumber() > $this->paginator->getNumberOfPages()) {
            return 0;
        }

        return $this->paginator->getKeyOfFirstPaginatedItem() + 1;
    }

    public function getEndRecordNumber(): int
    {
        if ($this->paginator->getCurrentPageNumber() > $this->paginator->getNumberOfPages()) {
            return 0;
        }

        return $this->paginator->getKeyOfLastPaginatedItem() + 1;
    }

    /**
     * @return int[]
     */
    public function getAllPageNumbers(): array
    {
        return range($this->getFirstPageNumber(), $this->getLastPageNumber());
    }
}
