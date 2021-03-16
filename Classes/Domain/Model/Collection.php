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
 * Collection entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Collection extends AbstractEntity
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
     * The information if collection is hidden
     *
     * @var int
     * @access protected
     */
    protected $hidden = 0;

    /**
     * The collection frontend group
     *
     * @var string
     * @access protected
     */
    protected $feGroup = '';

    /**
     * The collection label
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
     * The index search
     *
     * @var string
     * @access protected
     */
    protected $indexSearch = '';

    /**
     * The collection OAI name
     *
     * @var string
     * @access protected
     */
    protected $oaiName = '';

    /**
     * The collection description
     *
     * @var string
     * @access protected
     */
    protected $description = '';

    /**
     * The collection thumbnail
     *
     * @var string
     * @access protected
     */
    protected $thumbnail = '';

    /**
     * The collection priority
     *
     * @var int
     * @access protected
     */
    protected $priority = 3;

    //TODO: relationship to documents
    /**
     * List? of documents
     *
     * @var string
     * @access protected
     */
    protected $documents = '';

    //TODO: relationship to libraries
    /**
     * The collection owner
     *
     * @var int
     * @access protected
     */
    protected $owner = 0;

    /**
     * The collection frontend user
     *
     * @var int
     * @access protected
     */
    protected $feCruserId = 0;

    /**
     * The collection frontend admin lock
     *
     * @var int
     * @access protected
     */
    protected $feAdminLock = 0;

    /**
     * The collection status
     *
     * @var int
     * @access protected
     */
    protected $status = 0;

    /**
     * Initializes the collection entity.
     *
     * @access public
     * 
     * @param int $sysLanguageUid: The uid of system language
     * @param int $l18nParent: The language parent
     * @param int $l18nDiffSource: The language diff source
     * @param int $hidden: The information if the collection is hidden
     * @param string $feGroup: The collection frontend group
     * @param string $label: The collection label
     * @param string $indexName: The index name
     * @param string $indexSearch: The index search
     * @param string $oaiName: The collection OAI name
     * @param string $description: The collection description
     * @param string $thumbnail: The collection thumbnail
     * @param int $priority: The collection priority
     * @param int $documents: The collection documents
     * @param int $owner: The collection owner
     * @param int $feCruserId: The collection frontend user
     * @param int $feAdminLock: The collection admin lock
     * @param int $status: The collection status
     *
     * @return void
     */
    public function __construct(
        int $sysLanguageUid = 0,
        int $l18nParent = 0,
        int $l18nDiffSource = 0,
        int $hidden = 0,
        string $feGroup = '',
        string $label = '',
        string $indexName = '',
        string $indexSearch = '',
        string $oaiName = '',
        string $description = '',
        string $thumbnail = '',
        int $priority = 3,
        int $documents = 0,
        int $owner = 0,
        int $feCruserId = 0,
        int $feAdminLock = 0,
        int $status = 0
        )
    {
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
     * Get the information if collection is hidden
     *
     * @return int
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the information if collection is hidden
     *
     * @param int $hidden The information if collection is hidden
     *
     * @return void
     */
    public function setHidden(int $hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the collection frontend group
     *
     * @return string
     */
    public function getFeGroup()
    {
        return $this->feGroup;
    }

    /**
     * Set the collection frontend group
     *
     * @param string $feGroup The collection frontend group
     *
     * @return void
     */
    public function setFeGroup(string $feGroup)
    {
        $this->feGroup = $feGroup;
    }

    /**
     * Get the collection label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the collection label
     *
     * @param string $label The collection label
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
     * Get the index search
     *
     * @return string
     */
    public function getIndexSearch()
    {
        return $this->indexSearch;
    }

    /**
     * Set the index search
     *
     * @param string $indexSearch The index search
     *
     * @return void
     */
    public function setIndexSearch(string $indexSearch)
    {
        $this->indexSearch = $indexSearch;
    }

    /**
     * Get the collection OAI name
     *
     * @return string
     */
    public function getOaiName()
    {
        return $this->oaiName;
    }

    /**
     * Set the collection OAI name
     *
     * @param string $oaiName The collection OAI name
     *
     * @return void
     */
    public function setOaiName(string $oaiName)
    {
        $this->oaiName = $oaiName;
    }

    /**
     * Get the collection description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the collection description
     *
     * @param string $description The collection description
     *
     * @return void
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Get the collection thumbnail
     *
     * @return string
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set the collection thumbnail
     *
     * @param string $thumbnail The collection thumbnail
     *
     * @return void
     */
    public function setThumbnail(string $thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Get the collection priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the collection priority
     *
     * @param int $priority The collection priority
     *
     * @return void
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * Get list? of documents
     *
     * @return string
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Set list? of documents
     *
     * @param string $documents List? of documents
     *
     * @return void
     */
    public function setDocuments(string $documents)
    {
        $this->documents = $documents;
    }

    /**
     * Get the collection owner
     *
     * @return int
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set the collection owner
     *
     * @param int $owner The collection owner
     *
     * @return void
     */
    public function setOwner(int $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get the collection frontend user
     *
     * @return int
     */
    public function getFeCruserId()
    {
        return $this->feCruserId;
    }

    /**
     * Set the collection frontend user
     *
     * @param int $feCruserId The collection frontend user
     *
     * @return void
     */
    public function setFeCruserId(int $feCruserId)
    {
        $this->feCruserId = $feCruserId;
    }

    /**
     * Get the collection frontend admin lock
     *
     * @return int
     */
    public function getFeAdminLock()
    {
        return $this->feAdminLock;
    }

    /**
     * Set the collection frontend admin lock
     *
     * @param int $feAdminLock The collection frontend admin lock
     *
     * @return void
     */
    public function setFeAdminLock(int $feAdminLock)
    {
        $this->feAdminLock = $feAdminLock;
    }

    /**
     * Get the collection status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the collection status
     *
     * @param int $status The collection status
     *
     * @return void
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
    }
}
