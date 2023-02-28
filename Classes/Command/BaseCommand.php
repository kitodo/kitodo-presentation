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

namespace Kitodo\Dlf\Command;

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\LibraryRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Model\Library;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Base class for CLI Command classes.
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class BaseCommand extends Command
{
    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @var DocumentRepository
     */
    protected $documentRepository;

    /**
     * @var LibraryRepository
     */
    protected $libraryRepository;

    /**
     * @var StructureRepository
     */
    protected $structureRepository;

    /**
     * @var int
     */
    protected $storagePid;

    /**
     * @var \Kitodo\Dlf\Domain\Model\Library
     */
    protected $owner;

    /**
     * @var array
     * @access protected
     */
    protected $extConf;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    public function __construct(CollectionRepository $collectionRepository,
                                DocumentRepository   $documentRepository,
                                LibraryRepository    $libraryRepository,
                                StructureRepository  $structureRepository,
                                ConfigurationManager $configurationManager)
    {
        parent::__construct();

        $this->collectionRepository = $collectionRepository;
        $this->documentRepository = $documentRepository;
        $this->libraryRepository = $libraryRepository;
        $this->structureRepository = $structureRepository;
        $this->configurationManager = $configurationManager;
    }

    /**
     * Initialize the extbase repository based on the given storagePid.
     *
     * TYPO3 10+: Find a better solution e.g. based on Symfonie Dependency Injection.
     *
     * @param int $storagePid The storage pid
     *
     * @return bool
     */
    protected function initializeRepositories($storagePid)
    {
        if (MathUtility::canBeInterpretedAsInteger($storagePid)) {
            $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            $frameworkConfiguration['persistence']['storagePid'] = MathUtility::forceIntegerInRange((int) $storagePid, 0);
            $this->configurationManager->setConfiguration($frameworkConfiguration);

            // Get extension configuration.
            $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');
        } else {
            return false;
        }
        $this->storagePid = MathUtility::forceIntegerInRange((int) $storagePid, 0);

        return true;
    }

    /**
     * Return matching uid of Solr core depending on the input value.
     *
     * @param array $solrCores array of the valid Solr cores
     * @param string|bool|null $inputSolrId possible uid or name of Solr core
     *
     * @return int matching uid of Solr core
     */
    protected function getSolrCoreUid(array $solrCores, $inputSolrId): int
    {
        if (MathUtility::canBeInterpretedAsInteger($inputSolrId)) {
            $solrCoreUid = MathUtility::forceIntegerInRange((int) $inputSolrId, 0);
        } else {
            $solrCoreUid = $solrCores[$inputSolrId];
        }
        return $solrCoreUid;
    }

    /**
     * Fetches all Solr cores on given page.
     *
     * @param int $pageId The UID of the Solr core or 0 to disable indexing
     *
     * @return array Array of valid Solr cores
     */
    protected function getSolrCores(int $pageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_solrcores');

        $solrCores = [];
        $result = $queryBuilder
            ->select('uid', 'index_name')
            ->from('tx_dlf_solrcores')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter((int) $pageId, Connection::PARAM_INT)
                )
            )
            ->execute();

        while ($record = $result->fetch()) {
            $solrCores[$record['index_name']] = $record['uid'];
        }

        return $solrCores;
    }

    /**
     * Update or insert document to database
     *
     * @param int|string $doc The document uid from DB OR the location of a mets document.
     *
     * @return bool true on success
     */
    protected function saveToDatabase(Document $document)
    {
        $success = false;

        $doc = $document->getDoc();
        if ($doc === null) {
            return $success;
        }
        $doc->cPid = $this->storagePid;

        $metadata = $doc->getTitledata($this->storagePid);

        // set title data
        $document->setTitle($metadata['title'][0] ? : '');
        $document->setTitleSorting($metadata['title_sorting'][0]);
        $document->setPlace(implode('; ', $metadata['place']));
        $document->setYear(implode('; ', $metadata['year']));

        // Remove appended "valueURI" from authors' names for storing in database.
        foreach ($metadata['author'] as $i => $author) {
            $splitName = explode(chr(31), $author);
            $metadata['author'][$i] = $splitName[0];
        }
        $document->setAuthor(implode('; ', $metadata['author']));
        $document->setThumbnail($doc->thumbnail ? : '');
        $document->setMetsLabel($metadata['mets_label'][0] ? : '');
        $document->setMetsOrderlabel($metadata['mets_orderlabel'][0] ? : '');

        $structure = $this->structureRepository->findOneByIndexName($metadata['type'][0], 'tx_dlf_structures');
        $document->setStructure($structure);

        if (is_array($metadata['collection'])) {
            foreach ($metadata['collection'] as $collection) {
                $documentCollection = $this->collectionRepository->findOneByIndexName($collection);
                if (!$documentCollection) {
                    // create new Collection object
                    $documentCollection = GeneralUtility::makeInstance(Collection::class);
                    $documentCollection->setIndexName($collection);
                    $documentCollection->setLabel($collection);
                    $documentCollection->setOaiName((!empty($this->extConf['publishNewCollections']) ? Helper::getCleanString($collection) : ''));
                    $documentCollection->setIndexSearch('');
                    $documentCollection->setDescription('');
                    // add to CollectionRepository
                    $this->collectionRepository->add($documentCollection);
                }
                // add to document
                $document->addCollection($documentCollection);
            }
        }

        // set identifiers
        $document->setProdId($metadata['prod_id'][0] ? : '');
        $document->setOpacId($metadata['opac_id'][0] ? : '');
        $document->setUnionId($metadata['union_id'][0] ? : '');

        $document->setRecordId($metadata['record_id'][0] ? : ''); // (?) $doc->recordId
        $document->setUrn($metadata['urn'][0] ? : '');
        $document->setPurl($metadata['purl'][0] ? : '');
        $document->setDocumentFormat($metadata['document_format'][0] ? : '');

        // set access
        $document->setLicense($metadata['license'][0] ? : '');
        $document->setTerms($metadata['terms'][0] ? : '');
        $document->setRestrictions($metadata['restrictions'][0] ? : '');
        $document->setOutOfPrint($metadata['out_of_print'][0] ? : '');
        $document->setRightsInfo($metadata['rights_info'][0] ? : '');
        $document->setStatus(0);

        if ($this->owner) {
            // library / owner is set by parameter --> take it.
            $document->setOwner($this->owner);
        } else {
            // owner is not set set but found by metadata --> take it or take default library
            $owner = $metadata['owner'][0] ? : 'default';
            $this->owner = $this->libraryRepository->findOneByIndexName($owner);
            if ($this->owner) {
                $document->setOwner($this->owner);
            } else {
                // create library
                $this->owner = GeneralUtility::makeInstance(Library::class);

                $this->owner->setLabel($owner);
                $this->owner->setIndexName($owner);
                $this->libraryRepository->add($this->owner);
                $document->setOwner($this->owner);
            }
        }

        // to be still (re-) implemented
        // 'volume' => $metadata['volume'][0],
        // 'volume_sorting' => $metadata['volume_sorting'][0],

        // Get UID of parent document.
        if ($document->getDocumentFormat() === 'METS') {
            $document->setPartof($this->getParentDocumentUidForSaving($document));
        }

        if ($document->getUid() === null) {
            // new document
            $this->documentRepository->add($document);
        } else {
            // update of existing document
            $this->documentRepository->update($document);
        }

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $persistenceManager->persistAll();

        $success = true;

        return $success;
    }

    /**
     * Get the ID of the parent document if the current document has one.
     * Currently only applies to METS documents.
     *
     * @access protected
     *
     * @return int The parent document's id.
     */
    protected function getParentDocumentUidForSaving(Document $document)
    {
        $doc = $document->getDoc();

        if ($doc !== null) {
            // Same as MetsDocument::parentHref (TODO: Use it)
            // Get the closest ancestor of the current document which has a MPTR child.
            $parentMptr = $doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $doc->toplevelId . '"]/ancestor::mets:div[./mets:mptr][1]/mets:mptr');
            if (!empty($parentMptr)) {
                $parentLocation = (string) $parentMptr[0]->attributes('http://www.w3.org/1999/xlink')->href;

                // find document object by record_id of parent
                $parentDoc = Doc::getInstance($parentLocation, ['storagePid' => $this->storagePid]);

                if ($parentDoc->recordId) {
                    $parentDocument = $this->documentRepository->findOneByRecordId($parentDoc->recordId);

                    if ($parentDocument === null) {
                        // create new Document object
                        $parentDocument = GeneralUtility::makeInstance(Document::class);
                    }

                    $parentDocument->setOwner($this->owner);
                    $parentDocument->setDoc($parentDoc);
                    $parentDocument->setLocation($parentLocation);
                    $parentDocument->setSolrcore($document->getSolrcore());

                    $success = $this->saveToDatabase($parentDocument);

                    if ($success === true) {
                        // add to index
                        Indexer::add($parentDocument);
                        return $parentDocument->getUid();
                    }
                }
            }
        }

        return 0;
    }

}
