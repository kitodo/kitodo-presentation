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

namespace Kitodo\Dlf\Domain\Model;

class Metadata extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var int
     */
    protected $sorting;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $index_name;

    /**
     * @var int
     */
    protected $format;

    /**
     * @var string
     */
    protected $default_value;

    /**
     * @var string
     */
    protected $wrap;

    /**
     * @var int
     */
    protected $index_tokenized;

    /**
     * @var int
     */
    protected $index_stored;

    /**
     * @var int
     */
    protected $index_indexed;

    /**
     * @var float
     */
    protected $index_boost;

    /**
     * @var int
     */
    protected $is_sortable;

    /**
     * @var int
     */
    protected $is_facet;

    /**
     * @var int
     */
    protected $is_listed;

    /**
     * @var int
     */
    protected $index_autocomplete;

    /**
     * @var int
     */
    protected $status;

    /**
     * @return int
     */
    public function getSorting(): int
    {
        return $this->sorting;
    }

    /**
     * @param int $sorting
     */
    public function setSorting(int $sorting): void
    {
        $this->sorting = $sorting;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getIndexName(): string
    {
        return $this->index_name;
    }

    /**
     * @param string $index_name
     */
    public function setIndexName(string $index_name): void
    {
        $this->index_name = $index_name;
    }

    /**
     * @return int
     */
    public function getFormat(): int
    {
        return $this->format;
    }

    /**
     * @param int $format
     */
    public function setFormat(int $format): void
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        return $this->default_value;
    }

    /**
     * @param string $default_value
     */
    public function setDefaultValue(string $default_value): void
    {
        $this->default_value = $default_value;
    }

    /**
     * @return string
     */
    public function getWrap(): string
    {
        return $this->wrap;
    }

    /**
     * @param string $wrap
     */
    public function setWrap(string $wrap): void
    {
        $this->wrap = $wrap;
    }

    /**
     * @return int
     */
    public function getIndexTokenized(): int
    {
        return $this->index_tokenized;
    }

    /**
     * @param int $index_tokenized
     */
    public function setIndexTokenized(int $index_tokenized): void
    {
        $this->index_tokenized = $index_tokenized;
    }

    /**
     * @return int
     */
    public function getIndexStored(): int
    {
        return $this->index_stored;
    }

    /**
     * @param int $index_stored
     */
    public function setIndexStored(int $index_stored): void
    {
        $this->index_stored = $index_stored;
    }

    /**
     * @return int
     */
    public function getIndexIndexed(): int
    {
        return $this->index_indexed;
    }

    /**
     * @param int $index_indexed
     */
    public function setIndexIndexed(int $index_indexed): void
    {
        $this->index_indexed = $index_indexed;
    }

    /**
     * @return float
     */
    public function getIndexBoost(): float
    {
        return $this->index_boost;
    }

    /**
     * @param float $index_boost
     */
    public function setIndexBoost(float $index_boost): void
    {
        $this->index_boost = $index_boost;
    }

    /**
     * @return int
     */
    public function getIsSortable(): int
    {
        return $this->is_sortable;
    }

    /**
     * @param int $is_sortable
     */
    public function setIsSortable(int $is_sortable): void
    {
        $this->is_sortable = $is_sortable;
    }

    /**
     * @return int
     */
    public function getIsFacet(): int
    {
        return $this->is_facet;
    }

    /**
     * @param int $is_facet
     */
    public function setIsFacet(int $is_facet): void
    {
        $this->is_facet = $is_facet;
    }

    /**
     * @return int
     */
    public function getIsListed(): int
    {
        return $this->is_listed;
    }

    /**
     * @param int $is_listed
     */
    public function setIsListed(int $is_listed): void
    {
        $this->is_listed = $is_listed;
    }

    /**
     * @return int
     */
    public function getIndexAutocomplete(): int
    {
        return $this->index_autocomplete;
    }

    /**
     * @param int $index_autocomplete
     */
    public function setIndexAutocomplete(int $index_autocomplete): void
    {
        $this->index_autocomplete = $index_autocomplete;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

}
