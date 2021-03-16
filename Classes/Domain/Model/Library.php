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
 * Library entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Library extends AbstractEntity
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
     * The library label
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

    /**
     * The library website
     *
     * @var string
     * @access protected
     */
    protected $website = '';

    /**
     * The library contact
     *
     * @var string
     * @access protected
     */
    protected $contact = '';

    /**
     * The library image
     *
     * @var string
     * @access protected
     */
    protected $image = '';

    /**
     * The library OAI label
     *
     * @var string
     * @access protected
     */
    protected $oaiLabel = '';

    /**
     * The library OAI base
     *
     * @var string
     * @access protected
     */
    protected $oaiBase = '';

    /**
     * The library OPAC label
     *
     * @var string
     * @access protected
     */
    protected $opacLabel = '';

    /**
     * The library OPAC base
     *
     * @var string
     * @access protected
     */
    protected $opacBase = '';

    /**
     * The library union label
     *
     * @var string
     * @access protected
     */
    protected $unionLabel = '';

    /**
     * The library union base
     *
     * @var string
     * @access protected
     */
    protected $unionBase = '';

    /**
     * Initializes the library entity.
     *
     * @access public
     * 
     * @param int $sysLanguageUid: The uid of system language
     * @param int $l18nParent: The language parent
     * @param int $l18nDiffSource: The language diff source
     * @param string $label: The library label
     * @param string $indexName: The index name
     * @param string $website: The library website
     * @param string $contact: The library contact
     * @param string $image: The library image
     * @param string $oaiLabel: The library OAI label
     * @param string $oaiBase: The library OAI base
     * @param string $opacLabel: The library OPAC label
     * @param string $opacBase: The library OPAC base
     * @param string $unionLabel: The library union label
     * @param string $unionBase: The library union base
     *
     * @return void
     */
    public function __construct(
        int $sysLanguageUid = 0,
        int $l18nParent = 0,
        int $l18nDiffSource = 0,
        string $label = '',
        string $indexName = '',
        string $website = '',
        string $contact = '',
        string $image = '',
        string $oaiLabel = '',
        string $oaiBase = '',
        string $opacLabel = '',
        string $opacBase = '',
        string $unionLabel = '',
        string $unionBase = '')
    {
        $this->setSysLanguageUid($sysLanguageUid);
        $this->setL18nParent($l18nParent);
        $this->setL18nDiffSource($l18nDiffSource);
        $this->setLabel($label);
        $this->setIndexName($indexName);
        $this->setWebsite($website);
        $this->setContact($contact);
        $this->setImage($image);
        $this->setOaiLabel($oaiLabel);
        $this->setOaiBase($oaiBase);
        $this->setOpacLabel($opacLabel);
        $this->setOpacBase($opacBase);
        $this->setUnionLabel($unionLabel);
        $this->setUnionBase($unionBase);
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
     * Get the library label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the library label
     *
     * @param string $label The library label
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
     * Get the library website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set the library website
     *
     * @param string $website The library website
     *
     * @return void
     */
    public function setWebsite(string $website)
    {
        $this->website = $website;
    }

    /**
     * Get the library contact
     *
     * @return string
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set the library contact
     *
     * @param string $contact The library contact
     *
     * @return void
     */
    public function setContact(string $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the library image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set the library image
     *
     * @param string $image The library image
     *
     * @return void
     */
    public function setImage(string $image)
    {
        $this->image = $image;
    }

    /**
     * Get the library OAI label
     *
     * @return string
     */
    public function getOaiLabel()
    {
        return $this->oaiLabel;
    }

    /**
     * Set the library OAI label
     *
     * @param string $oaiLabel The library OAI label
     *
     * @return void
     */
    public function setOaiLabel(string $oaiLabel)
    {
        $this->oaiLabel = $oaiLabel;
    }

    /**
     * Get the library OAI base
     *
     * @return string
     */
    public function getOaiBase()
    {
        return $this->oaiBase;
    }

    /**
     * Set the library OAI base
     *
     * @param string $oaiBase The library OAI base
     *
     * @return void
     */
    public function setOaiBase(string $oaiBase)
    {
        $this->oaiBase = $oaiBase;
    }

    /**
     * Get the library OPAC label
     *
     * @return string
     */
    public function getOpacLabel()
    {
        return $this->opacLabel;
    }

    /**
     * Set the library OPAC label
     *
     * @param string $opacLabel The library OPAC label
     *
     * @return void
     */
    public function setOpacLabel(string $opacLabel)
    {
        $this->opacLabel = $opacLabel;
    }

    /**
     * Get the library OPAC base
     *
     * @return string
     */
    public function getOpacBase()
    {
        return $this->opacBase;
    }

    /**
     * Set the library OPAC base
     *
     * @param string $opacBase The library OPAC base
     *
     * @return void
     */
    public function setOpacBase(string $opacBase)
    {
        $this->opacBase = $opacBase;
    }

    /**
     * Get the library union label
     *
     * @return string
     */
    public function getUnionLabel()
    {
        return $this->unionLabel;
    }

    /**
     * Set the library union label
     *
     * @param string $unionLabel The library union label
     *
     * @return void
     */
    public function setUnionLabel(string $unionLabel)
    {
        $this->unionLabel = $unionLabel;
    }

    /**
     * Get the library union base
     *
     * @return string
     */
    public function getUnionBase()
    {
        return $this->unionBase;
    }

    /**
     * Set the library union base
     *
     * @param string $unionBase The library union base
     *
     * @return void
     */
    public function setUnionBase(string $unionBase)
    {
        $this->unionBase = $unionBase;
    }
}
