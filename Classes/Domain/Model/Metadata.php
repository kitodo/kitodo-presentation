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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Metadata entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Metadata extends AbstractEntity
{
    /**
     * The uid of the system language
     *
     * @var int
     * @access protected
     */
    protected $sysLanguageUid = 0;

    /**
     * The language parent
     *
     * @var string
     * @access protected
     */
    protected $l18nParent = 0;

    //TODO: check what is 'type' => 'passthrough'
    /**
     * The language diff source
     *
     * @var string
     * @access protected
     */
    protected $l18nDiffSource = 0;

    /**
     * The information if the metadata is hidden
     *
     * @var int
     * @access protected
     */
    protected $hidden = 0;

    /**
     * The metadata label
     *
     * @var string
     * @access protected
     */
    protected $label = '';

    /**
     * The index name
     *
     * @var string
     * @access protected
     */
    protected $indexName = '';

    //TODO: relationship to metadataformat
    /**
     * The metadata format
     *
     * @var int
     * @access protected
     */
    protected $format = 0;

    /**
     * The metadata default value
     *
     * @var string
     * @access protected
     */
    protected $defaultValue = '';

    /**
     * The metadata wrapper
     *
     * @var string
     * @access protected
     */
    protected $wrap = '';

    /**
     * The metadata tokenized index
     *
     * @var int
     * @access protected
     */
    protected $indexTokenized = 0;

    /**
     * The metadata stored index
     *
     * @var int
     * @access protected
     */
    protected $indexStored = 0;

    /**
     * The metadata indexed index
     *
     * @var int
     * @access protected
     */
    protected $indexIndexed = 1;

    /**
     * The metadata boost index
     *
     * @var float
     * @access protected
     */
    protected $indexBoost = 1.0;

    /**
     * The information if metadata is sortable
     *
     * @var int
     * @access protected
     */
    protected $isSortable = 0;

    /**
     * The information if the metadata is facet
     *
     * @var int
     * @access protected
     */
    protected $isFacet = 0;

    /**
     * The information if the metadata is listed
     *
     * @var int
     * @access protected
     */
    protected $isListed = 0;

    /**
     * The metadata autocomplete index
     *
     * @var int
     * @access protected
     */
    protected $indexAutocomplete = 0;

    /**
     * The metadata status
     *
     * @var int
     * @access protected
     */
    protected $status = 0;

    /**
     * Initializes the metadata entity.
     *
     * @access public
     * 
     * @param int $sysLanguageUid: The uid of system language
     * @param int $l18nParent: The language parent
     * @param int $l18nDiffSource: The language diff source
     * @param int $hidden: The information if the metadata is hidden
     * @param string $label: The metadata label
     * @param string $indexName: The index name
     * @param int $format: The metadata format
     * @param string $defaultValue: The metadata default value
     * @param string $wrap: The metadata wrapper
     * @param int $indexTokenized: The metadata tokenized index
     * @param int $indexStored: The metadata stored index
     * @param int $indexIndexed: The metadata indexed index
     * @param float $indexBoost: The metadata index boost
     * @param int $isSortable: The information if metadata is sortable
     * @param int $isFacet: The information if the metadata is facet
     * @param int $isListed: The information if the metadata is listed
     * @param int $indexAutocomplete: The metadata index autocomplete
     * @param int $status: The metadata status
     *
     * @return void
     */
    public function __construct(
        int $sysLanguageUid = 0,
        int $l18nParent = 0,
        int $l18nDiffSource = 0,
        int $hidden = 0,
        string $label = '',
        string $indexName = '',
        int $format = 0,
        string $defaultValue = '',
        string $wrap = '',
        int $indexTokenized = 0,
        int $indexStored = 0,
        int $indexIndexed = 1,
        float $indexBoost = 1.0,
        int $isSortable = 0,
        int $isFacet = 0,
        int $isListed = 0,
        int $indexAutocomplete = 0,
        int $status
        )
    {
        $this->setSysLanguageUid($sysLanguageUid);
        $this->setL18nParent($l18nParent);
        $this->setL18nDiffSource($l18nDiffSource);
        $this->setHidden($hidden);
        $this->setLabel($label);
        $this->setIndexName($indexName);
        $this->setFormat($format);
        $this->setDefaultValue($defaultValue);
        $this->setWrap($wrap);
        $this->setIndexTokenized($indexTokenized);
        $this->setIndexStored($indexStored);
        $this->setIndexIndexed($indexIndexed);
        $this->setIndexBoost($indexBoost);
        $this->setIsSortable($isSortable);
        $this->setIsFacet($isFacet);
        $this->setIsListed($isListed);
        $this->setIndexAutocomplete($indexAutocomplete);
        $this->setStatus($status);
    }


    /**
     * Get the uid of the system language
     *
     * @return int
     */
    public function getSysLanguageUid()
    {
        return $this->sysLanguageUid;
    }

    /**
     * Set the uid of the system language
     *
     * @param int $sysLanguageUid The uid of the system language
     *
     * @return void
     */
    public function setSysLanguageUid(int $sysLanguageUid)
    {
        $this->sysLanguageUid = $sysLanguageUid;
    }

    /**
     * Get the language parent
     *
     * @return string
     */
    public function getL18nParent()
    {
        return $this->l18nParent;
    }

    /**
     * Set the language parent
     *
     * @param string $l18nParent The language parent
     *
     * @return void
     */
    public function setL18nParent(string $l18nParent)
    {
        $this->l18nParent = $l18nParent;
    }

    /**
     * Get the language diff source
     *
     * @return string
     */
    public function getL18nDiffSource()
    {
        return $this->l18nDiffSource;
    }

    /**
     * Set the language diff source
     *
     * @param string $l18nDiffSource The language diff source
     *
     * @return void
     */
    public function setL18nDiffSource(string $l18nDiffSource)
    {
        $this->l18nDiffSource = $l18nDiffSource;
    }

    /**
     * Get the information if the metadata is hidden
     *
     * @return int
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the information if the metadata is hidden
     *
     * @param int $hidden The information if the metadata is hidden
     *
     * @return void
     */
    public function setHidden(int $hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the metadata label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the metadata label
     *
     * @param string $label The metadata label
     *
     * @return void
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the index name
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * Set the index name
     *
     * @param string $indexName The index name
     *
     * @return void
     */
    public function setIndexName(string $indexName)
    {
        $this->indexName = $indexName;
    }

    /**
     * Get the metadata format
     *
     * @return int
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the metadata format
     *
     * @param int $format The metadata format
     *
     * @return void
     */
    public function setFormat(int $format)
    {
        $this->format = $format;
    }

    /**
     * Get the metadata default value
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set the metadata default value
     *
     * @param string $defaultValue The metadata default value
     *
     * @return void
     */
    public function setDefaultValue(string $defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Get the metadata wrapper
     *
     * @return string
     */
    public function getWrap()
    {
        return $this->wrap;
    }

    /**
     * Set the metadata wrapper
     *
     * @param string $wrap The metadata wrapper
     *
     * @return void
     */
    public function setWrap(string $wrap)
    {
        $this->wrap = $wrap;
    }

    /**
     * Get the metadata tokenized index
     *
     * @return int
     */
    public function getIndexTokenized()
    {
        return $this->indexTokenized;
    }

    /**
     * Set the metadata tokenized index
     *
     * @param int $indexTokenized The metadata tokenized index
     *
     * @return void
     */
    public function setIndexTokenized(int $indexTokenized)
    {
        $this->indexTokenized = $indexTokenized;
    }

    /**
     * Get the metadata stored index
     *
     * @return int
     */
    public function getIndexStored()
    {
        return $this->indexStored;
    }

    /**
     * Set the metadata stored index
     *
     * @param int $indexStored The metadata stored index
     *
     * @return void
     */
    public function setIndexStored(int $indexStored)
    {
        $this->indexStored = $indexStored;
    }

    /**
     * Get the metadata indexed index
     *
     * @return int
     */
    public function getIndexIndexed()
    {
        return $this->indexIndexed;
    }

    /**
     * Set the metadata indexed index
     *
     * @param int $indexIndexed The metadata indexed index
     *
     * @return void
     */
    public function setIndexIndexed(int $indexIndexed)
    {
        $this->indexIndexed = $indexIndexed;
    }

    /**
     * Get the metadata boost index
     *
     * @return float
     */
    public function getIndexBoost()
    {
        return $this->indexBoost;
    }

    /**
     * Set the metadata boost index
     *
     * @param float $indexBoost The metadata boost index
     *
     * @return void
     */
    public function setIndexBoost(float $indexBoost)
    {
        $this->indexBoost = $indexBoost;
    }

    /**
     * Get the information if metadata is sortable
     *
     * @return int
     */
    public function getIsSortable()
    {
        return $this->isSortable;
    }

    /**
     * Set the information if metadata is sortable
     *
     * @param int $isSortable The information if metadata is sortable
     *
     * @return void
     */
    public function setIsSortable(int $isSortable)
    {
        $this->isSortable = $isSortable;
    }

    /**
     * Get the information if the metadata is facet
     *
     * @return int
     */
    public function getIsFacet()
    {
        return $this->isFacet;
    }

    /**
     * Set the information if the metadata is facet
     *
     * @param int $isFacet The information if the metadata is facet
     *
     * @return void
     */
    public function setIsFacet(int $isFacet)
    {
        $this->isFacet = $isFacet;
    }

    /**
     * Get the information if the metadata is listed
     *
     * @return int
     */
    public function getIsListed()
    {
        return $this->isListed;
    }

    /**
     * Set the information if the metadata is listed
     *
     * @param int $isListed The information if the metadata is listed
     *
     * @return void
     */
    public function setIsListed(int $isListed)
    {
        $this->isListed = $isListed;
    }

    /**
     * Get the metadata autocomplete index
     *
     * @return int
     */
    public function getIndexAutocomplete()
    {
        return $this->indexAutocomplete;
    }

    /**
     * Set the metadata autocomplete index
     *
     * @param int $indexAutocomplete The metadata autocomplete index
     *
     * @return void
     */
    public function setIndexAutocomplete(int $indexAutocomplete)
    {
        $this->indexAutocomplete = $indexAutocomplete;
    }

    /**
     * Get the metadata status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the metadata status
     *
     * @param int $status The metadata status
     *
     * @return void
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
    }
}
