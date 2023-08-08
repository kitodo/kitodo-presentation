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
 * This specifies a way how a metadatum (``tx_dlf_metadata``) may be encoded in a specific data format (``tx_dlf_format``).
 *
 * For instance, the title of a document may be obtained from either the MODS
 * title field, or from the TEIHDR caption. This is modeled as two ``tx_dlf_metadaformat``
 * that refer to the same ``tx_dlf_metadata`` but different ``tx_dlf_format``.
 *
 * This contains the xpath expressions on the model 'Metadata'.
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class MetadataSubentry extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \Kitodo\Dlf\Domain\Model\MetadataSubentry
     */
    protected $l18nParent;

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
     * XPath/JSONPath expression to extract the metadatum (relative to the data format root).
     * TODO
     *
     * @var string
     */
    protected $xpath;

    /**
     * @var string
     */
    protected $defaultValue;

    /**
     * @var string
     */
    protected $wrap;

    /**
     * @return \Kitodo\Dlf\Domain\Model\MetadataSubentry
     */
    public function getL18nParent(): MetadataSubentry
    {
        return $this->l18nParent;
    }

    /**
     * @param \Kitodo\Dlf\Domain\Model\MetadataSubentry $l18nParent
     */
    public function setL18nParent(MetadataSubentry $l18nParent): void
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
}
