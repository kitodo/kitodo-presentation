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

class Document extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $prod_id;

    /**
     * @var string
     */
    protected $location;

    /**
     * @var string
     */
    protected $record_id;

    /**
     * @var string
     */
    protected $opac_id;

    /**
     * @var string
     */
    protected $union_id;

    /**
     * @var string
     */
    protected $urn;

    /**
     * @var string
     */
    protected $purl;

    /**
     * @var string
     */
    protected $title_sorting;

    /**
     * @var string
     */
    protected $author;

    /**
     * @var string
     */
    protected $year;

    /**
     * @var string
     */
    protected $place;

    /**
     * @var string
     */
    protected $thumbnail;

    /**
     * @var string
     */
    protected $metadata;

    /**
     * @var string
     */
    protected $metadata_sorting;

    /**
     * @var int
     */
    protected $structure;

    /**
     * @var int
     */
    protected $partof;

    /**
     * @var string
     */
    protected $volume;

    /**
     * @var string
     */
    protected $volume_sorting;

    /**
     * @var string
     */
    protected $license;

    /**
     * @var string
     */
    protected $terms;

    /**
     * @var string
     */
    protected $restrictions;

    /**
     * @var string
     */
    protected $out_of_print;

    /**
     * @var string
     */
    protected $rights_info;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Kitodo\Dlf\Domain\Model\Collection>
     */
    protected $collections = null;

    /**
     * @var string
     */
    protected $mets_label;

    /**
     * @var string
     */
    protected $mets_orderlabel;

    /**
     * @var int
     */
    protected $owner;

    /**
     * @var int
     */
    protected $solrcore;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $document_format;

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
        return $this->prod_id;
    }

    /**
     * @param string $prod_id
     */
    public function setProdId(string $prod_id): void
    {
        $this->prod_id = $prod_id;
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
        return $this->record_id;
    }

    /**
     * @param string $record_id
     */
    public function setRecordId(string $record_id): void
    {
        $this->record_id = $record_id;
    }

    /**
     * @return string
     */
    public function getOpacId(): string
    {
        return $this->opac_id;
    }

    /**
     * @param string $opac_id
     */
    public function setOpacId(string $opac_id): void
    {
        $this->opac_id = $opac_id;
    }

    /**
     * @return string
     */
    public function getUnionId(): string
    {
        return $this->union_id;
    }

    /**
     * @param string $union_id
     */
    public function setUnionId(string $union_id): void
    {
        $this->union_id = $union_id;
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
        return $this->title_sorting;
    }

    /**
     * @param string $title_sorting
     */
    public function setTitleSorting(string $title_sorting): void
    {
        $this->title_sorting = $title_sorting;
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
     * @return string
     */
    public function getMetadata(): string
    {
        return $this->metadata;
    }

    /**
     * @param string $metadata
     */
    public function setMetadata(string $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function getMetadataSorting(): string
    {
        return $this->metadata_sorting;
    }

    /**
     * @param string $metadata_sorting
     */
    public function setMetadataSorting(string $metadata_sorting): void
    {
        $this->metadata_sorting = $metadata_sorting;
    }

    /**
     * @return int
     */
    public function getStructure(): int
    {
        return $this->structure;
    }

    /**
     * @param int $structure
     */
    public function setStructure(int $structure): void
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
        return $this->volume_sorting;
    }

    /**
     * @param string $volume_sorting
     */
    public function setVolumeSorting(string $volume_sorting): void
    {
        $this->volume_sorting = $volume_sorting;
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
        return $this->out_of_print;
    }

    /**
     * @param string $out_of_print
     */
    public function setOutOfPrint(string $out_of_print): void
    {
        $this->out_of_print = $out_of_print;
    }

    /**
     * @return string
     */
    public function getRightsInfo(): string
    {
        return $this->rights_info;
    }

    /**
     * @param string $rights_info
     */
    public function setRightsInfo(string $rights_info): void
    {
        $this->rights_info = $rights_info;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getCollections(): ?\TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->collections;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $collections
     */
    public function setCollections(?\TYPO3\CMS\Extbase\Persistence\ObjectStorage $collections): void
    {
        $this->collections = $collections;
    }

    /**
     * @return string
     */
    public function getMetsLabel(): string
    {
        return $this->mets_label;
    }

    /**
     * @param string $mets_label
     */
    public function setMetsLabel(string $mets_label): void
    {
        $this->mets_label = $mets_label;
    }

    /**
     * @return string
     */
    public function getMetsOrderlabel(): string
    {
        return $this->mets_orderlabel;
    }

    /**
     * @param string $mets_orderlabel
     */
    public function setMetsOrderlabel(string $mets_orderlabel): void
    {
        $this->mets_orderlabel = $mets_orderlabel;
    }

    /**
     * @return int
     */
    public function getOwner(): int
    {
        return $this->owner;
    }

    /**
     * @param int $owner
     */
    public function setOwner(int $owner): void
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
        return $this->document_format;
    }

    /**
     * @param string $document_format
     */
    public function setDocumentFormat(string $document_format): void
    {
        $this->document_format = $document_format;
    }

}
