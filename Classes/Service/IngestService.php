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

namespace Kitodo\Dlf\Service;

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Model\Library;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\LibraryRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Validation\DocumentValidator;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Saves documents (including their collections, owner and parent document) to the database.
 *
 * Used by the CLI commands and the demo bootstrap.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class IngestService
{
    /**
     * @access protected
     * @var int
     */
    protected int $storagePid = 0;

    /**
     * @access protected
     * @var Library|null
     */
    protected ?Library $owner = null;

    /**
     * @access protected
     * @var mixed[]
     */
    protected array $extConf = [];

    public function __construct(
        protected readonly CollectionRepository $collectionRepository,
        protected readonly DocumentRepository $documentRepository,
        protected readonly LibraryRepository $libraryRepository,
        protected readonly StructureRepository $structureRepository
    ) {
    }

    /**
     * Initialize the service for the given storage folder and owner.
     *
     * @access public
     *
     * @param int $storagePid The storage pid
     * @param Library|null $owner The owner of the documents, or null to determine it from the metadata
     *
     * @return void
     */
    public function initialize(int $storagePid, ?Library $owner = null): void
    {
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');
        $this->storagePid = $storagePid;
        $this->owner = $owner;
        $this->collectionRepository->useStoragePid($storagePid);
        $this->documentRepository->useStoragePid($storagePid);
        $this->libraryRepository->useStoragePid($storagePid);
        $this->structureRepository->useStoragePid($storagePid);
    }

    /**
     * Get the owner, which may have been determined from the metadata of a saved document.
     *
     * @access public
     *
     * @return Library|null
     */
    public function getOwner(): ?Library
    {
        return $this->owner;
    }

    /**
     * Get the existing document with the record id or location of the given document, or a new one.
     *
     * @access public
     *
     * @param AbstractDocument $doc The loaded document
     * @param string $url The location of the document
     *
     * @return Document
     */
    public function getDocumentFromUrl(AbstractDocument $doc, string $url): Document
    {
        $document = null;

        if (!empty($doc->recordId)) {
            $document = $this->documentRepository->findOneBy(['recordId' => $doc->recordId]);
        } else {
            $document = $this->documentRepository->findOneBy(['location' => $url]);
        }

        if ($document === null) {
            // create new Document object
            $document = GeneralUtility::makeInstance(Document::class);
        }

        $document->setLocation($url);

        return $document;
    }

    /**
     * Update or insert document to database
     *
     * @access public
     *
     * @param Document $document The document instance
     * @param bool $softCommit If true, documents are just added to the index by a soft commit
     *
     * @return bool true on success, false otherwise
     */
    public function saveToDatabase(Document $document, bool $softCommit = false): bool
    {
        $doc = $document->getCurrentDocument();
        if ($doc === null) {
            return false;
        }

        $doc->configPid = $this->storagePid;

        $metadata = $doc->getToplevelMetadata();
        $validator = new DocumentValidator($metadata, explode(',', $this->extConf['general']['requiredMetadataFields']));

        if ($validator->hasAllMandatoryMetadataFields()) {
            // set title data
            $document->setTitle($metadata['title'][0] ?? '');
            $document->setTitleSorting($metadata['title_sorting'][0] ?? '');
            $document->setPlace($this->shortenValue($metadata['place'] ?? []));
            $document->setYear(implode('; ', $metadata['year'] ?? []));
            $document->setAuthor($this->getAuthors($metadata['author'] ?? []));
            $document->setThumbnail($doc->thumbnail);
            $document->setMetsLabel($metadata['mets_label'][0] ?? '');
            $document->setMetsOrderlabel($metadata['mets_orderlabel'][0] ?? '');

            $structure = $this->structureRepository->findOneBy(['indexName' => $metadata['type'][0]]);
            if ($structure !== null) {
                $document->setStructure($structure);
            }

            if (is_array($metadata['collection'])) {
                $this->addCollections($document, $metadata['collection']);
            }

            // set identifiers
            $document->setProdId($metadata['prod_id'][0] ?? '');
            $document->setOpacId($metadata['opac_id'][0] ?? '');
            $document->setUnionId($metadata['union_id'][0] ?? '');

            $document->setRecordId($metadata['record_id'][0] ?? '');
            $document->setUrn($metadata['urn'][0] ?? '');
            $document->setPurl($metadata['purl'][0] ?? '');
            $document->setDocumentFormat($metadata['document_format'][0] ?? '');

            // set access
            $document->setLicense($metadata['license'][0] ?? '');
            $document->setTerms($metadata['terms'][0] ?? '');
            $document->setRestrictions($metadata['restrictions'][0] ?? '');
            $document->setOutOfPrint($metadata['out_of_print'][0] ?? '');
            $document->setRightsInfo($metadata['rights_info'][0] ?? '');
            $document->setStatus(0);

            $this->setNewOwner($metadata['owner'][0] ?? '');
            $document->setOwner($this->owner);

            // set volume data
            $document->setVolume($metadata['volume'][0] ?? '');
            $document->setVolumeSorting($metadata['volume_sorting'][0] ?? $metadata['mets_order'][0] ?? '');

            // Get UID of parent document.
            if ($document->getDocumentFormat() === 'METS') {
                $document->setPartof($this->getParentDocumentUidForSaving($document, $softCommit));
            }

            if ($document->getUid() === null) {
                // new document
                $this->documentRepository->add($document);
            } else {
                // update of existing document
                $this->documentRepository->update($document);
            }

            $this->documentRepository->persistAll();

            return true;
        }

        return false;
    }

    /**
     * Get the ID of the parent document if the current document has one.
     * Currently only applies to METS documents.
     *
     * @access protected
     *
     * @param Document $document for which parent UID should be taken
     * @param bool $softCommit If true, documents are just added to the index by a soft commit
     *
     * @return int The parent document's id.
     */
    protected function getParentDocumentUidForSaving(Document $document, bool $softCommit = false): int
    {
        $doc = $document->getCurrentDocument();

        if ($doc !== null && !empty($doc->parentHref)) {
            // find document object by record_id of parent
            $parent = AbstractDocument::getInstance($doc->parentHref, ['storagePid' => $this->storagePid], true, isTrustedLocation: true);

            if ($parent->recordId) {
                $parentDocument = $this->documentRepository->findOneBy(['recordId' => $parent->recordId]);

                if ($parentDocument === null) {
                    // create new Document object
                    $parentDocument = GeneralUtility::makeInstance(Document::class);
                }

                $parentDocument->setOwner($this->owner);
                $parentDocument->setCurrentDocument($parent);
                $parentDocument->setLocation($doc->parentHref);
                $parentDocument->setSolrcore($document->getSolrcore());

                $success = $this->saveToDatabase($parentDocument, $softCommit);

                if ($success === true) {
                    // add to index
                    Indexer::add($parentDocument, $this->documentRepository, $softCommit);
                    return $parentDocument->getUid();
                }
            }
        }

        return 0;
    }

    /**
     * Add collections.
     *
     * @access private
     *
     * @param Document &$document
     * @param array<string> $collections
     *
     * @return void
     */
    private function addCollections(Document &$document, array $collections): void
    {
        foreach ($collections as $collection) {
            $documentCollection = $this->collectionRepository->findOneBy(['indexName' => $collection]);
            if (!$documentCollection) {
                // create new Collection object
                $documentCollection = GeneralUtility::makeInstance(Collection::class);
                $documentCollection->setIndexName($collection);
                $documentCollection->setLabel($collection);
                $setSpec = '';
                if (!empty($this->extConf['general']['publishNewCollections'])) {
                    // setSpec only allows unreserved characters (rfc2396).
                    // alnum | "-" | "_" | "." | "!" | "~" | "*" | "'" | "(" | ")"
                    // Here we are a little bit more restrictive because
                    // some more unreserved characters are not allowed.
                    // Convert whitespaces to dash.
                    $setSpec = $collection;
                    $setSpec = preg_replace('/\s/', '-', $setSpec);
                    // Remove multiple dashes.
                    $setSpec = preg_replace('/-{2,}/', '-', $setSpec);
                    // Remove undesired characters.
                    $setSpec = preg_replace('/[^\w:-]/', '', $setSpec);
                    // A hierarchical setSpec consists of two or more
                    // normal setSpec which are separated by colons.
                    // Remove colons which don't separate hierarchical setSpec entries.
                    $setSpec = trim($setSpec, ':');
                    $setSpec = preg_replace('/:{2,}/', ':', $setSpec);
                }
                $documentCollection->setOaiName($setSpec);
                $documentCollection->setIndexSearch('');
                $documentCollection->setDescription('');
                // add to CollectionRepository
                $this->collectionRepository->add($documentCollection);
                // persist collection to prevent duplicates
                $this->collectionRepository->persistAll();
            }
            // add to document
            $document->addCollection($documentCollection);
        }
    }

    /**
     * Get authors considering that database field can't accept
     * more than 255 characters.
     *
     * @access private
     *
     * @param array<int, mixed> $metadataAuthor
     *
     * @return string
     */
    private function getAuthors(array $metadataAuthor): string
    {
        // Get only authors' names for storing in database.
        foreach ($metadataAuthor as $i => $author) {
            if (is_array($author)) {
                $metadataAuthor[$i] = $author['name'];
            }
        }

        return $this->shortenValue($metadataAuthor);
    }

    /**
     * Shorten the value to a maximum length, adding an ellipsis if necessary.
     *
     * @access private
     *
     * @param array<int, string> $values The values to shorten
     *
     * @return string The shortened value
     */
    private function shortenValue(array $values): string
    {
        $value = '';
        $delimiter = '; ';
        $ellipsis = 'et al.';

        $count = count($values);

        for ($i = 0; $i < $count; $i++) {
            // Build the next part to add
            $nextPart = ($i === 0 ? '' : $delimiter) . $values[$i];
            // Check if adding this part and ellipsis in future would exceed the character limit
            if (strlen($value . $nextPart . $delimiter . $ellipsis) > 255) {
                // Add ellipsis and stop adding more values
                $value .= $delimiter . $ellipsis;
                break;
            }
            // Add the part to the main string
            $value .= $nextPart;
        }
        return $value;
    }

    /**
     * If owner is not set but found by metadata, take it or take default library, if nothing found in database then create new owner.
     *
     * @access private
     *
     * @param string $owner
     *
     * @return void
     */
    private function setNewOwner(string $owner): void
    {
        if (empty($this->owner)) {
            // owner is not set but found by metadata --> take it or take default library
            $owner = $owner ?: 'default';
            $this->owner = $this->libraryRepository->findOneBy(['indexName' => $owner]);
            if (empty($this->owner)) {
                // create library
                $this->owner = GeneralUtility::makeInstance(Library::class);
                $this->owner->setLabel($owner);
                $this->owner->setIndexName($owner);
                $this->libraryRepository->add($this->owner);
            }
        }
    }
}
