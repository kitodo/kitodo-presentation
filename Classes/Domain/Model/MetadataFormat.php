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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * This specifies a way how a metadata (``tx_dlf_metadata``) may be encoded in a specific data format (``tx_dlf_format``).
 *
 * For instance, the title of a document may be obtained from either the MODS
 * title field, or from the TEIHDR caption. This is modeled as two ``tx_dlf_metadaformat``
 * that refer to the same ``tx_dlf_metadata`` but different ``tx_dlf_format``.
 *
 * This contains the xpath expressions on the model 'Metadata'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class MetadataFormat extends AbstractEntity
{
    /**
     * @access protected
     * @var int UID of the ``tx_dlf_metadata`` that is encoded by this metadata entry.
     */
    protected $parentId;

    /**
     * @access protected
     * @var int UID of the ``tx_dlf_format`` in which this metadata entry is encoded.
     */
    protected $encoded;

    /**
     * @access protected
     * @var string XPath/JSONPath expression to extract the metadata (relative to the data format root).
     */
    protected $xpath;

    /**
     * @access protected
     * @var string XPath/JSONPath expression to extract sorting variant (suffixed ``_sorting``) of the metadata.
     */
    protected $xpathSorting;

    /**
     * Collection of ``tx_dlf_metadatasubentries`` specified with this metadata entry.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Kitodo\Dlf\Domain\Model\MetadataSubentry>
     * @Extbase\ORM\Lazy
     * @Extbase\ORM\Cascade("remove")
     */
    protected $subentries;

    /**
     * Whether or not the field is mandatory. Not used at the moment (originally planned to be used in METS validator).
     *
     * @var int
     * @access protected
     */
    protected $mandatory;

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
        $this->subentries = new ObjectStorage();
    }

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

    public function getSubentries()
    {
        return $this->subentries;
    }

    public function setSubentries(ObjectStorage $subentries): void
    {
        $this->subentries = $subentries;
    }

    /**
     * Adds a Subentry
     *
     * @param \Kitodo\Dlf\Domain\Model\MetadataSubentry $subentry
     *
     * @return void
     */
    public function addSubentry(MetadataSubentry $subentry)
    {
        $this->subentries->attach($subentry);
    }

    /**
     * Removes a Subentry
     *
     * @param \Kitodo\Dlf\Domain\Model\MetadataSubentry $subentryToRemove
     *
     * @return void
     */
    public function removeSubentry(MetadataSubentry $subentryToRemove)
    {
        $this->subentries->detach($subentryToRemove);
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
