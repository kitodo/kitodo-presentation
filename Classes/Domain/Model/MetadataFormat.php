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

class MetadataFormat extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var int
     */
    protected $parent_id;
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
    protected $xpath_sorting;
    /**
     * @var int
     */
    protected $mandatory;

    /**
     * @return int
     */
    public function getParentId(): int
    {
        return $this->parent_id;
    }

    /**
     * @param int $parent_id
     */
    public function setParentId(int $parent_id): void
    {
        $this->parent_id = $parent_id;
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
        return $this->xpath_sorting;
    }

    /**
     * @param string $xpath_sorting
     */
    public function setXpathSorting(string $xpath_sorting): void
    {
        $this->xpath_sorting = $xpath_sorting;
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
