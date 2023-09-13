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
 * A library institution with the following use cases:
 *
 * - Each ``tx_dlf_document`` is *owned* by exactly one ``tx_dlf_library``. The
 *   owner is set on indexing, and it is shown in the metadata plugin. If no
 *   library is configured, the fallback library is named 'default'.
 *
 * - The OAI-PMH plugin has a configuration option ``library`` that is used to
 *   identify the OAI repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Library extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
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
    protected $website;

    /**
     * Contact email address of the library (used as ``adminEmail`` in responses
     * to OAI ``Identify`` requests).
     *
     * @var string
     */
    protected $contact;

    /**
     * image
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    protected $image;

    /**
     * The label that is used as ``repositoryName`` in responses to OAI
     * ``Identify`` requests.
     *
     * @var string
     */
    protected $oaiLabel;

    /**
     * OAI base URL used when harvesting the library via ``kitodo:harvest``.
     *
     * @var string
     */
    protected $oaiBase;

    /**
     * @var string
     */
    protected $opacLabel;

    /**
     * @var string
     */
    protected $opacBase;

    /**
     * @var string
     */
    protected $unionLabel;

    /**
     * @var string
     */
    protected $unionBase;

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
    public function getWebsite(): string
    {
        return $this->website;
    }

    /**
     * @param string $website
     */
    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    /**
     * @return string
     */
    public function getContact(): string
    {
        return $this->contact;
    }

    /**
     * @param string $contact
     */
    public function setContact(string $contact): void
    {
        $this->contact = $contact;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    public function getImage(): \TYPO3\CMS\Extbase\Domain\Model\FileReference
    {
        return $this->image;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $image
     */
    public function setImage(\TYPO3\CMS\Extbase\Domain\Model\FileReference $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getOaiLabel(): string
    {
        return $this->oaiLabel;
    }

    /**
     * @param string $oaiLabel
     */
    public function setOaiLabel(string $oaiLabel): void
    {
        $this->oaiLabel = $oaiLabel;
    }

    /**
     * @return string
     */
    public function getOaiBase(): string
    {
        return $this->oaiBase;
    }

    /**
     * @param string $oaiBase
     */
    public function setOaiBase(string $oaiBase): void
    {
        $this->oaiBase = $oaiBase;
    }

    /**
     * @return string
     */
    public function getOpacLabel(): string
    {
        return $this->opacLabel;
    }

    /**
     * @param string $opacLabel
     */
    public function setOpacLabel(string $opacLabel): void
    {
        $this->opacLabel = $opacLabel;
    }

    /**
     * @return string
     */
    public function getOpacBase(): string
    {
        return $this->opacBase;
    }

    /**
     * @param string $opacBase
     */
    public function setOpacBase(string $opacBase): void
    {
        $this->opacBase = $opacBase;
    }

    /**
     * @return string
     */
    public function getUnionLabel(): string
    {
        return $this->unionLabel;
    }

    /**
     * @param string $unionLabel
     */
    public function setUnionLabel(string $unionLabel): void
    {
        $this->unionLabel = $unionLabel;
    }

    /**
     * @return string
     */
    public function getUnionBase(): string
    {
        return $this->unionBase;
    }

    /**
     * @param string $unionBase
     */
    public function setUnionBase(string $unionBase): void
    {
        $this->unionBase = $unionBase;
    }

}
