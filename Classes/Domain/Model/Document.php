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

use Kitodo\Dlf\Common\AbstractDocument;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Domain model of the 'Document'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Document extends AbstractEntity
{
    /**
     * @access protected
     * @var \DateTime
     */
    protected $crdate;

    /**
     * @access protected
     * @var \DateTime
     */
    protected $tstamp;

    /**
     * @access protected
     * @var AbstractDocument|null This contains the representative of the raw XML / IIIF data of the document.
     */
    protected $currentDocument = null;

    /**
     * @access protected
     * @var string
     */
    protected $title;

    /**
     * @access protected
     * @var string
     */
    protected $prodId;

    /**
     * @access protected
     * @var string
     */
    protected $location;

    /**
     * @access protected
     * @var string
     */
    protected $recordId;

    /**
     * @access protected
     * @var string
     */
    protected $opacId;

    /**
     * @access protected
     * @var string
     */
    protected $unionId;

    /**
     * @access protected
     * @var string
     */
    protected $urn;

    /**
     * @access protected
     * @var string
     */
    protected $purl;

    /**
     * @access protected
     * @var string
     */
    protected $titleSorting;

    /**
     * @access protected
     * @var string
     */
    protected $author;

    /**
     * @access protected
     * @var string
     */
    protected $year;

    /**
     * @access protected
     * @var string
     */
    protected $place;

    /**
     * @access protected
     * @var string
     */
    protected $thumbnail;

    /**
     * @access protected
     * @var Structure
     */
    protected $structure;

    /**
     * @access protected
     * @var int
     */
    protected $partof = 0;

    /**
     * @access protected
     * @var string
     */
    protected $volume;

    /**
     * @access protected
     * @var string
     */
    protected $volumeSorting;

    /**
     * @access protected
     * @var string
     */
    protected $license;

    /**
     * @access protected
     * @var string
     */
    protected $terms;

    /**
     * @access protected
     * @var string
     */
    protected $restrictions;

    /**
     * @access protected
     * @var string
     */
    protected $outOfPrint;

    /**
     * @access protected
     * @var string
     */
    protected $rightsInfo;

    /**
     * @access protected
     * @var ObjectStorage<Collection>
     * @Extbase\ORM\Lazy
     */
    protected $collections = null;

    /**
     * @access protected
     * @var string
     */
    protected $metsLabel;

    /**
     * @access protected
     * @var string
     */
    protected $metsOrderlabel;

    /**
     * @access protected
     * @var Library
     * @Extbase\ORM\Lazy
     */
    protected $owner;

    /**
     * @access protected
     * @var int
     */
    protected $solrcore;

    /**
     * @access protected
     * @var int
     */
    protected $status;

    /**
     * @access protected
     * @var string
     */
    protected $documentFormat;

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
        $this->collections = new ObjectStorage();
    }

    /**
     * @return AbstractDocument
     */
    public function getCurrentDocument(): ?AbstractDocument
    {
        return $this->currentDocument;
    }

    /**
     * @param AbstractDocument $currentDocument
     */
    public function setCurrentDocument(AbstractDocument $currentDocument): void
    {
        $this->currentDocument = $currentDocument;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getProdId(): string
    {
        return $this->prodId;
    }

    /**
     * @param string $prodId
     */
    public function setProdId(string $prodId): void
    {
        $this->prodId = $prodId;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getRecordId(): string
    {
        return $this->recordId;
    }

    /**
     * @param string $recordId
     */
    public function setRecordId(string $recordId): void
    {
        $this->recordId = $recordId;
    }

    /**
     * @return string
     */
    public function getOpacId(): string
    {
        return $this->opacId;
    }

    /**
     * @param string $opacId
     */
    public function setOpacId(string $opacId): void
    {
        $this->opacId = $opacId;
    }

    /**
     * @return string
     */
    public function getUnionId(): string
    {
        return $this->unionId;
    }

    /**
     * @param string $unionId
     */
    public function setUnionId(string $unionId): void
    {
        $this->unionId = $unionId;
    }

    /**
     * @return string
     */
    public function getUrn(): string
    {
        return $this->urn;
    }

    /**
     * @param string $urn
     */
    public function setUrn(string $urn): void
    {
        $this->urn = $urn;
    }

    /**
     * @return string
     */
    public function getPurl(): string
    {
        return $this->purl;
    }

    /**
     * @param string $purl
     */
    public function setPurl(string $purl): void
    {
        $this->purl = $purl;
    }

    /**
     * @return string
     */
    public function getTitleSorting(): string
    {
        return $this->titleSorting;
    }

    /**
     * @param string $titleSorting
     */
    public function setTitleSorting(string $titleSorting): void
    {
        $this->titleSorting = $titleSorting;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getYear(): string
    {
        return $this->year;
    }

    /**
     * @param string $year
     */
    public function setYear(string $year): void
    {
        $this->year = $year;
    }

    /**
     * @return string
     */
    public function getPlace(): string
    {
        return $this->place;
    }

    /**
     * @param string $place
     */
    public function setPlace(string $place): void
    {
        $this->place = $place;
    }

    /**
     * @return string
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     */
    public function setThumbnail(string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return Structure
     */
    public function getStructure(): Structure
    {
        return $this->structure;
    }

    /**
     * @param Structure $structure
     */
    public function setStructure(Structure $structure): void
    {
        $this->structure = $structure;
    }

    /**
     * @return int
     */
    public function getPartof(): int
    {
        return $this->partof;
    }

    /**
     * @param int $partof
     */
    public function setPartof(int $partof): void
    {
        $this->partof = $partof;
    }

    /**
     * @return string
     */
    public function getVolume(): string
    {
        return $this->volume;
    }

    /**
     * @param string $volume
     */
    public function setVolume(string $volume): void
    {
        $this->volume = $volume;
    }

    /**
     * @return string
     */
    public function getVolumeSorting(): string
    {
        return $this->volumeSorting;
    }

    /**
     * @param string $volumeSorting
     */
    public function setVolumeSorting(string $volumeSorting): void
    {
        $this->volumeSorting = $volumeSorting;
    }

    /**
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * @param string $license
     */
    public function setLicense(string $license): void
    {
        $this->license = $license;
    }

    /**
     * @return string
     */
    public function getTerms(): string
    {
        return $this->terms;
    }

    /**
     * @param string $terms
     */
    public function setTerms(string $terms): void
    {
        $this->terms = $terms;
    }

    /**
     * @return string
     */
    public function getRestrictions(): string
    {
        return $this->restrictions;
    }

    /**
     * @param string $restrictions
     */
    public function setRestrictions(string $restrictions): void
    {
        $this->restrictions = $restrictions;
    }

    /**
     * @return string
     */
    public function getOutOfPrint(): string
    {
        return $this->outOfPrint;
    }

    /**
     * @param string $outOfPrint
     */
    public function setOutOfPrint(string $outOfPrint): void
    {
        $this->outOfPrint = $outOfPrint;
    }

    /**
     * @return string
     */
    public function getRightsInfo(): string
    {
        return $this->rightsInfo;
    }

    /**
     * @param string $rightsInfo
     */
    public function setRightsInfo(string $rightsInfo): void
    {
        $this->rightsInfo = $rightsInfo;
    }


    /**
     * Returns the collections
     *
     * @return ObjectStorage<Collection> $collections
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * @param ObjectStorage<Collection> $collections
     */
    public function setCollections(?ObjectStorage $collections): void
    {
        $this->collections = $collections;
    }

    /**
     * Adds a collection
     *
     * @param Collection $collection
     */
    public function addCollection(Collection $collection): void
    {
        $this->collections->attach($collection);
    }

    /**
     * Removes a collection
     *
     * @param Collection $collection
     *
     * @return void
     */
    public function removeCollection(Collection $collection): void
    {
        $this->collections->detach($collection);
    }

    /**
     * @return string
     */
    public function getMetsLabel(): string
    {
        return $this->metsLabel;
    }

    /**
     * @param string $metsLabel
     */
    public function setMetsLabel(string $metsLabel): void
    {
        $this->metsLabel = $metsLabel;
    }

    /**
     * @return string
     */
    public function getMetsOrderlabel(): string
    {
        return $this->metsOrderlabel;
    }

    /**
     * @param string $metsOrderlabel
     */
    public function setMetsOrderlabel(string $metsOrderlabel): void
    {
        $this->metsOrderlabel = $metsOrderlabel;
    }

    /**
     * @return Library|null
     */
    public function getOwner(): ?Library
    {
        return $this->owner instanceof LazyLoadingProxy
            ? $this->owner->_loadRealInstance()
            : $this->owner;
    }

    /**
     * @param Library $owner
     */
    public function setOwner(Library $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return int
     */
    public function getSolrcore(): int
    {
        return $this->solrcore;
    }

    /**
     * @param int $solrcore
     */
    public function setSolrcore(int $solrcore): void
    {
        $this->solrcore = $solrcore;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getDocumentFormat(): string
    {
        return $this->documentFormat;
    }

    /**
     * @param string $documentFormat
     */
    public function setDocumentFormat(string $documentFormat): void
    {
        $this->documentFormat = $documentFormat;
    }

    /**
     * Returns the timestamp
     *
     * @return \DateTime
     */
    public function getTstamp(): \DateTime
    {
        return $this->tstamp;
    }

    /**
     * Sets the timestamp
     *
     * @param \DateTime $tstamp
     */
    public function setTstamp($tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    /**
     * Returns the creation date
     *
     * @return \DateTime
     */
    public function getCrdate(): \DateTime
    {
        return $this->crdate;
    }

    /**
     * Sets the creation date
     *
     * @param \DateTime $crdate
     */
    public function setCrdate($crdate): void
    {
        $this->crdate = $crdate;
    }

}
