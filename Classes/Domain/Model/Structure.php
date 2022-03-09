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
 * Domain model of 'Structure'.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Structure extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var int
     */
    protected $toplevel;

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
    protected $oaiName;

    /**
     * @var int
     */
    protected $thumbnail;

    /**
     * @var int
     */
    protected $status;

    /**
     * @return int
     */
    public function getToplevel(): int
    {
        return $this->toplevel;
    }

    /**
     * @param int $toplevel
     */
    public function setToplevel(int $toplevel): void
    {
        $this->toplevel = $toplevel;
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
     * @return int
     */
    public function getThumbnail(): int
    {
        return $this->thumbnail;
    }

    /**
     * @param int $thumbnail
     */
    public function setThumbnail(int $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
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
