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
 * Domain model of the 'MetadataFormat'. This contains the xpath expressions on the model 'Metadata'.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class MetadataFormat extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var int
     */
    protected $parentId;

    /**
     * @var int
     */
    protected $encoded;

    /**
     * @var string
     */
    protected $xpath;

    /**
     * @var string
     */
    protected $xpathSorting;

    /**
     * @var int
     */
    protected $mandatory;

    /**
     * @return int
     */
    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     */
    public function setParentId(int $parentId): void
    {
        $this->parentId = $parentId;
    }

    /**
     * @return int
     */
    public function getEncoded(): int
    {
        return $this->encoded;
    }

    /**
     * @param int $encoded
     */
    public function setEncoded(int $encoded): void
    {
        $this->encoded = $encoded;
    }

    /**
     * @return string
     */
    public function getXpath(): string
    {
        return $this->xpath;
    }

    /**
     * @param string $xpath
     */
    public function setXpath(string $xpath): void
    {
        $this->xpath = $xpath;
    }

    /**
     * @return string
     */
    public function getXpathSorting(): string
    {
        return $this->xpathSorting;
    }

    /**
     * @param string $xpathSorting
     */
    public function setXpathSorting(string $xpathSorting): void
    {
        $this->xpathSorting = $xpathSorting;
    }

    /**
     * @return int
     */
    public function getMandatory(): int
    {
        return $this->mandatory;
    }

    /**
     * @param int $mandatory
     */
    public function setMandatory(int $mandatory): void
    {
        $this->mandatory = $mandatory;
    }

}
