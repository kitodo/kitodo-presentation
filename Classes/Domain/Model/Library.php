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

class Library extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
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
    protected $website;

    /**
     * @var string
     */
    protected $contact;

    /**
     * @var string
     */
    protected $image;

    /**
     * @var string
     */
    protected $oai_label;

    /**
     * @var string
     */
    protected $oai_base;

    /**
     * @var string
     */
    protected $opac_label;

    /**
     * @var string
     */
    protected $opac_base;

    /**
     * @var string
     */
    protected $union_label;

    /**
     * @var string
     */
    protected $union_base;

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
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getOaiLabel(): string
    {
        return $this->oai_label;
    }

    /**
     * @param string $oai_label
     */
    public function setOaiLabel(string $oai_label): void
    {
        $this->oai_label = $oai_label;
    }

    /**
     * @return string
     */
    public function getOaiBase(): string
    {
        return $this->oai_base;
    }

    /**
     * @param string $oai_base
     */
    public function setOaiBase(string $oai_base): void
    {
        $this->oai_base = $oai_base;
    }

    /**
     * @return string
     */
    public function getOpacLabel(): string
    {
        return $this->opac_label;
    }

    /**
     * @param string $opac_label
     */
    public function setOpacLabel(string $opac_label): void
    {
        $this->opac_label = $opac_label;
    }

    /**
     * @return string
     */
    public function getOpacBase(): string
    {
        return $this->opac_base;
    }

    /**
     * @param string $opac_base
     */
    public function setOpacBase(string $opac_base): void
    {
        $this->opac_base = $opac_base;
    }

    /**
     * @return string
     */
    public function getUnionLabel(): string
    {
        return $this->union_label;
    }

    /**
     * @param string $union_label
     */
    public function setUnionLabel(string $union_label): void
    {
        $this->union_label = $union_label;
    }

    /**
     * @return string
     */
    public function getUnionBase(): string
    {
        return $this->union_base;
    }

    /**
     * @param string $union_base
     */
    public function setUnionBase(string $union_base): void
    {
        $this->union_base = $union_base;
    }

}
