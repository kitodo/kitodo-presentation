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

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A metadata kind (title, year, ...) and its configuration for display and indexing.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Metadata extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \Kitodo\Dlf\Domain\Model\Metadata
     */
    protected $l18nParent;

    /**
     * Order (relative position) of this entry in metadata plugin and backend list.
     *
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
     * The formats that encode this metadatum (local IRRE field to ``tx_dlf_metadataformat``).
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Kitodo\Dlf\Domain\Model\MetadataFormat>
     * @Extbase\ORM\Lazy
     * @Extbase\ORM\Cascade("remove")
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
     * constructor
     */
    public function __construct()
    {
        // Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    protected function initStorageObjects()
    {
        $this->format = new ObjectStorage();
    }

    /**
     * @return \Kitodo\Dlf\Domain\Model\Metadata
     */
    public function getL18nParent(): Metadata
    {
        return $this->l18nParent;
    }

    /**
     * @param int $l18nParent
     */
    public function setL18nParent(Metadata $l18nParent): void
    {
        $this->l18nParent = $l18nParent;
    }

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
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Kitodo\Dlf\Domain\Model\MetadataFormat> $format
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Kitodo\Dlf\Domain\Model\MetadataFormat> $format
     */
    public function setFormat(ObjectStorage $format): void
    {
        $this->format = $format;
    }

    /**
     * Adds a Format
     *
     * @param \Kitodo\Dlf\Domain\Model\MetadataFormat $format
     *
     * @return void
     */
    public function addFormat(MetadataFormat $format)
    {
        $this->format->attach($format);
    }

    /**
     * Removes a Format
     *
     * @param \Kitodo\Dlf\Domain\Model\MetadataFormat $formatToRemove
     *
     * @return void
     */
    public function removeFormat(MetadataFormat $formatToRemove)
    {
        $this->format->detach($formatToRemove);
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

}
