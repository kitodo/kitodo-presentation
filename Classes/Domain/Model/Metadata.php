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

class Metadata extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
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
    protected $indexName;

    /**
     * @var int
     */
    protected $format;

    /**
     * @var string
     */
    protected $defaultValue;

    /**
     * @var string
     */
    protected $wrap;

    /**
     * @var int
     */
    protected $indexTokenized;

    /**
     * @var int
     */
    protected $indexStored;

    /**
     * @var int
     */
    protected $indexIndexed;

    /**
     * @var float
     */
    protected $indexBoost;

    /**
     * @var int
     */
    protected $isSortable;

    /**
     * @var int
     */
    protected $isFacet;

    /**
     * @var int
     */
    protected $isListed;

    /**
     * @var int
     */
    protected $indexAutocomplete;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $sysLanguageUid;

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
        return $this->indexName;
    }

    /**
     * @param string $indexName
     */
    public function setIndexName(string $indexName): void
    {
        $this->indexName = $indexName;
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
        return $this->defaultValue;
    }

    /**
     * @param string $defaultValue
     */
    public function setDefaultValue(string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
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
        return $this->indexTokenized;
    }

    /**
     * @param int $indexTokenized
     */
    public function setIndexTokenized(int $indexTokenized): void
    {
        $this->indexTokenized = $indexTokenized;
    }

    /**
     * @return int
     */
    public function getIndexStored(): int
    {
        return $this->indexStored;
    }

    /**
     * @param int $indexStored
     */
    public function setIndexStored(int $indexStored): void
    {
        $this->indexStored = $indexStored;
    }

    /**
     * @return int
     */
    public function getIndexIndexed(): int
    {
        return $this->indexIndexed;
    }

    /**
     * @param int $indexIndexed
     */
    public function setIndexIndexed(int $indexIndexed): void
    {
        $this->indexIndexed = $indexIndexed;
    }

    /**
     * @return float
     */
    public function getIndexBoost(): float
    {
        return $this->indexBoost;
    }

    /**
     * @param float $indexBoost
     */
    public function setIndexBoost(float $indexBoost): void
    {
        $this->indexBoost = $indexBoost;
    }

    /**
     * @return int
     */
    public function getIsSortable(): int
    {
        return $this->isSortable;
    }

    /**
     * @param int $isSortable
     */
    public function setIsSortable(int $isSortable): void
    {
        $this->isSortable = $isSortable;
    }

    /**
     * @return int
     */
    public function getIsFacet(): int
    {
        return $this->isFacet;
    }

    /**
     * @param int $isFacet
     */
    public function setIsFacet(int $isFacet): void
    {
        $this->isFacet = $isFacet;
    }

    /**
     * @return int
     */
    public function getIsListed(): int
    {
        return $this->isListed;
    }

    /**
     * @param int $isListed
     */
    public function setIsListed(int $isListed): void
    {
        $this->isListed = $isListed;
    }

    /**
     * @return int
     */
    public function getIndexAutocomplete(): int
    {
        return $this->indexAutocomplete;
    }

    /**
     * @param int $indexAutocomplete
     */
    public function setIndexAutocomplete(int $indexAutocomplete): void
    {
        $this->indexAutocomplete = $indexAutocomplete;
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

    /**
     * @return int
     */
    public function getSysLanguageUid(): int
    {
        return $this->sysLanguageUid;
    }

    /**
     * @param int $sysLanguageUid
     */
    public function setSysLanguageUid(int $sysLanguageUid): void
    {
        $this->sysLanguageUid = $sysLanguageUid;
    }

}
