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

/**
 * Domain model of the 'Collection'.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Collection extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var int
     */
    protected $feCruserId;

    /**
     * @var string
     */
    protected $feGroup;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $indexName;

    /**
     * @var string
     */
    protected $indexSearch;

    /**
     * @var string
     */
    protected $oaiName;

    /**
     * @var string
     */
    protected $description;

    /**
     * thumbnail
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    protected $thumbnail = null;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var int
     */
    protected $documents;

    /**
     * @var int
     */
    protected $owner;

    /**
     * @var int
     */
    protected $status;

    /**
     * @return int
     */
    public function getFeCruserId(): int
    {
        return $this->feCruserId;
    }

    /**
     * @param string $feCruserId
     */
    public function setFeCruserId(string $feCruserId): void
    {
        $this->feCruserId = $feCruserId;
    }

    /**
     * @return string
     */
    public function getFeGroup(): string
    {
        return $this->feGroup;
    }

    /**
     * @param string $feGroup
     */
    public function setFeGroup(string $feGroup): void
    {
        $this->feGroup = $feGroup;
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
     * @return string
     */
    public function getIndexSearch(): string
    {
        return $this->indexSearch;
    }

    /**
     * @param string $indexSearch
     */
    public function setIndexSearch(string $indexSearch): void
    {
        $this->indexSearch = $indexSearch;
    }

    /**
     * @return string
     */
    public function getOaiName(): string
    {
        return $this->oaiName;
    }

    /**
     * @param string $oaiName
     */
    public function setOaiName(string $oaiName): void
    {
        $this->oaiName = $oaiName;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    public function getThumbnail(): ?\TYPO3\CMS\Extbase\Domain\Model\FileReference
    {
        return $this->thumbnail;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $thumbnail
     */
    public function setThumbnail(?\TYPO3\CMS\Extbase\Domain\Model\FileReference $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getDocuments(): int
    {
        return $this->documents;
    }

    /**
     * @param int $documents
     */
    public function setDocuments(int $documents): void
    {
        $this->documents = $documents;
    }

    /**
     * @return int
     */
    public function getOwner(): int
    {
        return $this->owner;
    }

    /**
     * @param int $owner
     */
    public function setOwner(int $owner): void
    {
        $this->owner = $owner;
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
