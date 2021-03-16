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
 * Structure entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Structure extends AbstractEntity
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
     * The information if the structure is hidden
     *
     * @var int
     * @access protected
     */
    protected $hidden = 0;

    /**
     * The information if the structure is the top level structure
     *
     * @var int
     * @access protected
     */
    protected $topLevel = 0;

    /**
     * The structure label
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
     * The structure OAI-PMH mapping
     *
     * @var string
     * @access protected
     */
    protected $oaiName = '';

    /**
     * The structure thumbnail
     *
     * @var int
     * @access protected
     */
    protected $thumbnail = 0;

    /**
     * The structure status
     *
     * @var int
     * @access protected
     */
    protected $status = 0;

    /**
     * Initializes the structure entity.
     *
     * @access public
     * 
     * @param int $sysLanguageUid: The uid of system language
     * @param int $l18nParent: The language parent
     * @param int $l18nDiffSource: The language diff source
     * @param int $hidden: The information if the structure is hidden
     * @param int $topLevel: The information if the structure is the top level structure
     * @param string $label: The SOLR core label
     * @param string $indexName: The index name
     * @param string $oaiName: The structure OAI-PMH mapping
     * @param int $thumbnail: The structure thumbnail
     * @param int $status: The structure status
     *
     * @return void
     */
    public function __construct(
        int $sysLanguageUid = 0,
        int $l18nParent = 0,
        int $l18nDiffSource = 0,
        int $hidden = 0,
        int $topLevel = 0,
        string $label = '',
        string $indexName = '',
        string $oaiName = '',
        int $thumbnail = 0,
        int $status = 0)
    {
        $this->setSysLanguageUid($sysLanguageUid);
        $this->setL18nParent($l18nParent);
        $this->setL18nDiffSource($l18nDiffSource);
        $this->setHidden($hidden);

        $this->setLabel($label);
        $this->setIndexName($indexName);
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
     * Get the information if the structure is hidden
     *
     * @return int
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the information if the structure is hidden
     *
     * @param int $hidden The information if the structure is hidden
     *
     * @return void
     */
    public function setHidden(int $hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the information if the structure is the top level structure
     *
     * @return int
     */
    public function getTopLevel()
    {
        return $this->topLevel;
    }

    /**
     * Set the information if the structure is the top level structure
     *
     * @param int $topLevel The information if the structure is the top level structure
     *
     * @return void
     */
    public function setTopLevel(int $topLevel)
    {
        $this->topLevel = $topLevel;
    }

    /**
     * Get the structure label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the structure label
     *
     * @param string $label The structure label
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
     * Get the structure OAI-PMH mapping
     *
     * @return string
     */
    public function getOaiName()
    {
        return $this->oaiName;
    }

    /**
     * Set the structure OAI-PMH mapping
     *
     * @param string $oaiName The structure OAI-PMH mapping
     *
     * @return void
     */
    public function setOaiName(string $oaiName)
    {
        $this->oaiName = $oaiName;
    }

    /**
     * Get the structure thumbnail
     *
     * @return int
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set the structure thumbnail
     *
     * @param int $thumbnail The structure thumbnail
     *
     * @return void
     */
    public function setThumbnail(int $thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Get the structure status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the structure status
     *
     * @param int $status The structure status
     *
     * @return void
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
    }
}
