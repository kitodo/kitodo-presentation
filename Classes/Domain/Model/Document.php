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
 * Document entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Document extends AbstractEntity
{
    /**
     * The information if document is hidden
     *
     * @var int
     * @access protected
     */
    protected $hidden = 0;

    /**
     * The document start time
     *
     * @var int
     * @access protected
     */
    protected $startTime = 0;

    /**
     * The document end time
     *
     * @var int
     * @access protected
     */
    protected $endTime = 0;

    /**
     * The document frontend group
     *
     * @var string
     * @access protected
     */
    protected $feGroup = '';

    /**
     * The document production id
     *
     * @var string
     * @access protected
     */
    protected $prodId = '';

    /**
     * The document location of METS file / IIIF manifest (URI)
     *
     * @var string
     * @access protected
     */
    protected $location = '';

    /**
     * The document record id
     *
     * @var string
     * @access protected
     */
    protected $recordId = '';

    /**
     * The document OPAC/Local id
     *
     * @var string
     * @access protected
     */
    protected $opacId = '';

    /**
     * The document Union Catalog/Foreign id
     *
     * @var string
     * @access protected
     */
    protected $unionId = '';

    /**
     * The document URN
     *
     * @var string
     * @access protected
     */
    protected $urn = '';

    /**
     * The document PURL
     *
     * @var string
     * @access protected
     */
    protected $purl = '';

    /**
     * The document title
     *
     * @var string
     * @access protected
     */
    protected $title = '';

    /**
     * The document title for sorting
     *
     * @var string
     * @access protected
     */
    protected $titleSorting = '';

    /**
     * The document author
     *
     * @var string
     * @access protected
     */
    protected $author = '';

    /**
     * The document year of publication
     *
     * @var string
     * @access protected
     */
    protected $year = '';

    /**
     * The document place of publication
     *
     * @var string
     * @access protected
     */
    protected $place = '';

    /**
     * The document thumbnail
     *
     * @var string
     * @access protected
     */
    protected $thumbnail = '';

    /**
     * The document metadata
     *
     * @var string
     * @access protected
     */
    protected $metadata = '';

    /**
     * The document metadata for sorting
     *
     * @var string
     * @access protected
     */
    protected $metadataSorting = '';

    // TODO: relation to structure entity
    /**
     * The type of document
     *
     * @var int
     * @access protected
     */
    protected $structure = 0;

    /**
     * The document part of property
     *
     * @var int
     * @access protected
     */
    protected $partOf = 0;

    /**
     * The document number of volume
     *
     * @var string
     * @access protected
     */
    protected $volume = '';

    /**
     * The document number of volume for sorting
     *
     * @var string
     * @access protected
     */
    protected $volumeSorting = '';

    /**
     * The document license
     *
     * @var string
     * @access protected
     */
    protected $license = '';

    /**
     * The document terms
     *
     * @var string
     * @access protected
     */
    protected $terms = '';

    /**
     * The document restrictions on access
     *
     * @var string
     * @access protected
     */
    protected $restrictions = '';

    /**
     * The document out of print property
     *
     * @var string
     * @access protected
     */
    protected $outOfPrint = '';

    /**
     * The document rights info
     *
     * @var string
     * @access protected
     */
    protected $rightsInfo = '';

    /**
     * The document METS label
     *
     * @var string
     * @access protected
     */
    protected $metsLabel = '';

    /**
     * The document METS order label
     *
     * @var string
     * @access protected
     */
    protected $metsOrderLabel = '';

    //TODO: relationship to collections
    /**
     * The document METS label
     *
     * @var int
     * @access protected
     */
    protected $collections = 0;

    //TODO: relationship to libraries
    /**
     * The document owner
     *
     * @var int
     * @access protected
     */
    protected $owner = 0;

    /**
     * The document SOLR Core
     *
     * @var int
     * @access protected
     */
    protected $solrCore = 0;

    /**
     * The document status
     *
     * @var int
     * @access protected
     */
    protected $status = 0;

    /**
     * The document format
     *
     * @var int
     * @access protected
     */
    protected $documentFormat = '';

    /**
     * Initializes the document entity.
     *
     * @access public
     * 
     * @param int $hidden: The information if document is hidden
     * @param int $startTime: The document start time
     * @param int $endTime: The document end time
     * @param string $feGroup: The document frontend group
     * @param string $prodId: The document production id
     * @param string $location: The document location of METS file / IIIF manifest (URI)
     * @param string $recordId: The document record id
     * @param string $opacId: The document OPAC/Local id
     * @param string $unionId: The document Union Catalog/Foreign id
     * @param string $urn: The document URN
     * @param string $purl: The document PURL
     * @param string $title: The document title
     * @param string $titleSorting: The document title for sorting
     * @param string $author: The document author
     * @param string $year: The document year of publication
     * @param string $place: The document place of publication
     * @param string $thumbnail: The document thumbnail
     * @param string $metadata: The document metadata
     * @param string $metadataSorting: The document metadata for sorting
     * @param int $structure: The type of document
     * @param int $partOf: The document part of
     * @param string $volume: The document number of volume
     * @param string $volumeSorting: The document number of volume for sorting
     * @param string $license: The document license
     * @param string $terms: The document terms
     * @param string $restrictions: The document restrictions on access
     * @param string $outOfPrint: The document out of print property
     * @param string $rightsInfo: The document rights information
     * @param string $metsLabel: The document METS label
     * @param string $metsOrderLabel: The document METS order label
     * @param int $collections: The document collections
     * @param int $owner: The document owner
     * @param int $solrCore: The document SOLR core
     * @param int $status: The document format
     * @param string $documentFormat: The document format
     *
     * @return void
     */
    public function __construct(
        int $hidden = 0,
        int $startTime = 0,
        int $endTime = 0,
        string $feGroup = '',
        string $prodId = '',
        string $location = '',
        string $recordId = '',
        string $opacId = '',
        string $unionId = '',
        string $urn = '',
        string $purl = '',
        string $title = '',
        string $titleSorting = '',
        string $author = '',
        string $year = '',
        string $place = '',
        string $thumbnail = '',
        string $metadata = '',
        string $metadataSorting = '',
        int $structure = 0,
        int $partOf = 0,
        string $volume = '',
        string $volumeSorting = '',
        string $license = '',
        string $terms = '',
        string $restrictions = '',
        string $outOfPrint = '',
        string $rightsInfo = '',
        string $metsLabel = '',
        string $metsOrderLabel = '',
        int $collections = 0,
        int $owner = 0,
        int $solrCore = 0,
        int $status = 0,
        string $documentFormat = '')
    {
        $this->setHidden($hidden);
        $this->setStartTime($startTime);
        $this->setEndTime($endTime);
        $this->setFeGroup($feGroup);
        $this->setProdId($prodId);
        $this->setLocation($location);
        $this->setRecordId($recordId);
        $this->setOpacId($opacId);
        $this->setUnionId($unionId);
        $this->setUrn($urn);
        $this->setPurl($purl);
        $this->setTitle($title);
        $this->setTitleSorting($titleSorting);
        $this->setAuthor($author);
        $this->setYear($year);
        $this->setPlace($place);
        $this->setThumbnail($thumbnail);
        $this->setMetadata($metadata);
        $this->setMetadataSorting($metadataSorting);
        $this->setStructure($structure);
        $this->setPartOf($partOf);
        $this->setVolume($volume);
        $this->setVolumeSorting($volumeSorting);
        $this->setLicense($license);
        $this->setTerms($terms);
        $this->setRestrictions($restriction);
        $this->setOutOfPrint($outOfPrint);
        $this->setRightsInfo($rightsInfo);
        $this->setMetsLabel($metsLabel);
        $this->setMetsOrderLabel($metsOrderLabel);
        $this->setCollections($collections);
        $this->setOwner($owner);
        $this->setSolrCore($solrCore);
        $this->setStatus($status);
        $this->setDocumentFormat($documentFormat);
    }

    /**
     * Get the information if document is hidden
     *
     * @return int
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the information if document is hidden
     *
     * @param int $hidden The information if document is hidden
     *
     * @return void
     */
    public function setHidden(int $hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the document start time
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set the document start time
     *
     * @param int $startTime The document start time
     *
     * @return void
     */
    public function setStartTime(int $startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * Get the document end time
     *
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set the document end time
     *
     * @param int $endTime The document end time
     *
     * @return void
     */
    public function setEndTime(int $endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * Get the document frontend group
     *
     * @return string
     */
    public function getFeGroup()
    {
        return $this->feGroup;
    }

    /**
     * Set the document frontend group
     *
     * @param string $feGroup The document frontend group
     *
     * @return void
     */
    public function setFeGroup(string $feGroup)
    {
        $this->feGroup = $feGroup;
    }

    /**
     * Get the document production id
     *
     * @return string
     */
    public function getProdId()
    {
        return $this->prodId;
    }

    /**
     * Set the document production id
     *
     * @param string $prodId The document production id
     *
     * @return void
     */
    public function setProdId(string $prodId)
    {
        $this->prodId = $prodId;
    }

    /**
     * Get the document location of METS file / IIIF manifest (URI)
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the document location of METS file / IIIF manifest (URI)
     *
     * @param string $location The document location of METS file / IIIF manifest (URI)
     *
     * @return void
     */
    public function setLocation(string $location)
    {
        $this->location = $location;
    }

    /**
     * Get the document record id
     *
     * @return string
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set the document record id
     *
     * @param string $recordId The document record id
     *
     * @return void
     */
    public function setRecordId(string $recordId)
    {
        $this->recordId = $recordId;
    }

    /**
     * Get the document OPAC/Local id
     *
     * @return string
     */
    public function getOpacId()
    {
        return $this->opacId;
    }

    /**
     * Set the document OPAC/Local id
     *
     * @param string $opacId The document OPAC/Local id
     *
     * @return void
     */
    public function setOpacId(string $opacId)
    {
        $this->opacId = $opacId;
    }

    /**
     * Get the document Union Catalog/Foreign id
     *
     * @return string
     */
    public function getUnionId()
    {
        return $this->unionId;
    }

    /**
     * Set the document Union Catalog/Foreign id
     *
     * @param string $unionId The document Union Catalog/Foreign id
     *
     * @return void
     */
    public function setUnionId(string $unionId)
    {
        $this->unionId = $unionId;
    }

    /**
     * Get the document URN
     *
     * @return string
     */
    public function getUrn()
    {
        return $this->urn;
    }

    /**
     * Set the document URN
     *
     * @param string $urn The document URN
     *
     * @return void
     */
    public function setUrn(string $urn)
    {
        $this->urn = $urn;
    }

    /**
     * Get the document PURL
     *
     * @return string
     */
    public function getPurl()
    {
        return $this->purl;
    }

    /**
     * Set the document PURL
     *
     * @param string $purl The document PURL
     *
     * @return void
     */
    public function setPurl(string $purl)
    {
        $this->purl = $purl;
    }

    /**
     * Get the document title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the document title
     *
     * @param string $title The document title
     *
     * @return void
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Get the document title for sorting
     *
     * @return string
     */
    public function getTitleSorting()
    {
        return $this->titleSorting;
    }

    /**
     * Set the document title for sorting
     *
     * @param string $titleSorting The document title for sorting
     *
     * @return void
     */
    public function setTitleSorting(string $titleSorting)
    {
        $this->titleSorting = $titleSorting;
    }

    /**
     * Get the document author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the document author
     *
     * @param string $author The document author
     *
     * @return void
     */
    public function setAuthor(string $author)
    {
        $this->author = $author;
    }

    /**
     * Get the document year of publication
     *
     * @return string
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set the document year of publication
     *
     * @param string $year The document year of publication
     *
     * @return void
     */
    public function setYear(string $year)
    {
        $this->year = $year;
    }

    /**
     * Get the document place of publication
     *
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set the document place of publication
     *
     * @param string $place The document place of publication
     *
     * @return void
     */
    public function setPlace(string $place)
    {
        $this->place = $place;
    }

    /**
     * Get the document thumbnail
     *
     * @return string
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set the document thumbnail
     *
     * @param string $thumbnail The document thumbnail
     *
     * @return void
     */
    public function setThumbnail(string $thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Get the document metadata
     *
     * @return string
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set the document metadata
     *
     * @param string $metadata The document metadata
     *
     * @return void
     */
    public function setMetadata(string $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get the document metadata for sorting
     *
     * @return string
     */
    public function getMetadataSorting()
    {
        return $this->metadataSorting;
    }

    /**
     * Set the document metadata for sorting
     *
     * @param string $metadataSorting The document metadata for sorting
     *
     * @return void
     */
    public function setMetadataSorting(string $metadataSorting)
    {
        $this->metadataSorting = $metadataSorting;
    }

    /**
     * Get the type of document
     *
     * @return int
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Set the type of document
     *
     * @param int $structure The type of document
     *
     * @return void
     */
    public function setStructure(int $structure)
    {
        $this->structure = $structure;
    }

    /**
     * Get the document part of property
     *
     * @return int
     */
    public function getPartOf()
    {
        return $this->partOf;
    }

    /**
     * Set the document part of property
     *
     * @param int $partOf The document part of property
     *
     * @return void
     */
    public function setPartOf(int $partOf)
    {
        $this->partOf = $partOf;
    }

    /**
     * Get the document number of volume
     *
     * @return string
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Set the document number of volume
     *
     * @param string $volume The document number of volume
     *
     * @return void
     */
    public function setVolume(string $volume)
    {
        $this->volume = $volume;
    }

    /**
     * Get the document number of volume for sorting
     *
     * @return string
     */
    public function getVolumeSorting()
    {
        return $this->volumeSorting;
    }

    /**
     * Set the document number of volume for sorting
     *
     * @param string $volumeSorting The document number of volume for sorting
     *
     * @return void
     */
    public function setVolumeSorting(string $volumeSorting)
    {
        $this->volumeSorting = $volumeSorting;
    }

    /**
     * Get the document license
     *
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * Set the document license
     *
     * @param string $license The document license
     *
     * @return void
     */
    public function setLicense(string $license)
    {
        $this->license = $license;
    }

    /**
     * Get the document terms of use
     *
     * @return string
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Set the document terms of use
     *
     * @param string $terms The document terms of use
     *
     * @return void
     */
    public function setTerms(string $terms)
    {
        $this->terms = $terms;
    }

    /**
     * Get the document restrictions on access
     *
     * @return string
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

    /**
     * Set the document restrictions on access
     *
     * @param string $restrictions The document restrictions on access
     *
     * @return void
     */
    public function setRestrictions(string $restrictions)
    {
        $this->restrictions = $restrictions;
    }

    /**
     * Get the document out of print property
     *
     * @return string
     */
    public function getOutOfPrint()
    {
        return $this->outOfPrint;
    }

    /**
     * Set the document out of print property
     *
     * @param string $outOfPrint The document out of print property
     *
     * @return void
     */
    public function setOutOfPrint(string $outOfPrint)
    {
        $this->outOfPrint = $outOfPrint;
    }

    /**
     * Get the document rights info
     *
     * @return string
     */
    public function getRightsInfo()
    {
        return $this->rightsInfo;
    }

    /**
     * Set the document rights info
     *
     * @param string $rightsInfo The document rights info
     *
     * @return void
     */
    public function setRightsInfo(string $rightsInfo)
    {
        $this->rightsInfo = $rightsInfo;
    }

    /**
     * Get the document METS label
     *
     * @return string
     */
    public function getMetsLabel()
    {
        return $this->metsLabel;
    }

    /**
     * Set the document METS label
     *
     * @param string $metsLabel The document METS label
     *
     * @return void
     */
    public function setMetsLabel(string $metsLabel)
    {
        $this->metsLabel = $metsLabel;
    }

    /**
     * Get the document METS order label
     *
     * @return string
     */
    public function getMetsOrderLabel()
    {
        return $this->metsOrderLabel;
    }

    /**
     * Set the document METS order label
     *
     * @param string $metsOrderLabel The document METS order label
     *
     * @return void
     */
    public function setMetsOrderLabel(string $metsOrderLabel)
    {
        $this->metsOrderLabel = $metsOrderLabel;
    }

    /**
     * Get the document collections
     *
     * @return int
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * Set the document collections
     *
     * @param int $collections The document collections
     *
     * @return void
     */
    public function setCollections(int $collections)
    {
        $this->collections = $collections;
    }

    /**
     * Get the document owner
     *
     * @return int
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set the document owner
     *
     * @param int $owner The document owner
     *
     * @return void
     */
    public function setOwner(int $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get the document SOLR Core
     *
     * @return int
     */
    public function getSolrCore()
    {
        return $this->solrCore;
    }

    /**
     * Set the document SOLR Core
     *
     * @param int $solrCore The document SOLR Core
     *
     * @return void
     */
    public function setSolrCore(int $solrCore)
    {
        $this->solrCore = $solrCore;
    }

    /**
     * Get the document status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the document status
     *
     * @param int $status The document status
     *
     * @return void
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    /**
     * Get the value of document format
     *
     * @return mixed
     */
    public function getDocumentFormat()
    {
        return $this->documentFormat;
    }

    /**
     * Set the value of document format
     *
     * @param mixed $documentFormat 
     *
     * @return void
     */
    public function setDocumentFormat($documentFormat)
    {
        $this->documentFormat = $documentFormat;
    }
}
