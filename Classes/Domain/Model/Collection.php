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

class Collection extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var string
     */
    protected $fe_group;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $index_name;

    /**
     * @var string
     */
    protected $index_search;

    /**
     * @var string
     */
    protected $oai_name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $thumbnail;

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
     * @return string
     */
    public function getFeGroup(): string
    {
        return $this->fe_group;
    }

    /**
     * @param string $fe_group
     */
    public function setFeGroup(string $fe_group): void
    {
        $this->fe_group = $fe_group;
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
     * @return string
     */
    public function getIndexSearch(): string
    {
        return $this->index_search;
    }

    /**
     * @param string $index_search
     */
    public function setIndexSearch(string $index_search): void
    {
        $this->index_search = $index_search;
    }

    /**
     * @return string
     */
    public function getOaiName(): string
    {
        return $this->oai_name;
    }

    /**
     * @param string $oai_name
     */
    public function setOaiName(string $oai_name): void
    {
        $this->oai_name = $oai_name;
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
     * @return string
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     */
    public function setThumbnail(string $thumbnail): void
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
