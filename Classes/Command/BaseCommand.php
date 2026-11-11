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

use Kitodo\Dlf\Domain\Model\SolrCore;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\LibraryRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Model\Library;
use Kitodo\Dlf\Service\IngestService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Base class for CLI Command classes.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class BaseCommand extends Command
{
    /**
     * @access protected
     * @var CollectionRepository
     */
    protected CollectionRepository $collectionRepository;

    /**
     * @access protected
     * @var DocumentRepository
     */
    protected DocumentRepository $documentRepository;

    /**
     * @access protected
     * @var LibraryRepository
     */
    protected LibraryRepository $libraryRepository;

    /**
     * @access protected
     * @var SolrCoreRepository
     */
    protected SolrCoreRepository $solrCoreRepository;

    /**
     * @access protected
     * @var StructureRepository
     */
    protected StructureRepository $structureRepository;

    /**
     * @access protected
     * @var int
     */
    protected int $storagePid;

    /**
     * @access protected
     * @var Library|null
     */
    protected ?Library $owner;

    /**
     * @access protected
     * @var mixed[]
     */
    protected array $extConf;

    public function __construct(
        CollectionRepository $collectionRepository,
        DocumentRepository $documentRepository,
        LibraryRepository $libraryRepository,
        SolrCoreRepository $solrCoreRepository,
        StructureRepository $structureRepository
    ) {
        parent::__construct();

        $this->collectionRepository = $collectionRepository;
        $this->documentRepository = $documentRepository;
        $this->libraryRepository = $libraryRepository;
        $this->solrCoreRepository = $solrCoreRepository;
        $this->structureRepository = $structureRepository;
    }

    /**
     * @access protected
     * @var IngestService
     */
    protected IngestService $ingestService;

    /**
     * @access public
     *
     * @param IngestService $ingestService
     *
     * @return void
     */
    public function injectIngestService(IngestService $ingestService): void
    {
        $this->ingestService = $ingestService;
    }

    /**
     * Initialize the extbase repository based on the given storagePid.
     *
     * @access protected
     *
     * @param int $storagePid The storage pid
     *
     * @return void
     */
    protected function initializeRepositories(int $storagePid): void
    {
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');
        $this->storagePid = MathUtility::forceIntegerInRange($storagePid, 0);
        $this->collectionRepository->useStoragePid($this->storagePid);
        $this->documentRepository->useStoragePid($this->storagePid);
        $this->libraryRepository->useStoragePid($this->storagePid);
        $this->structureRepository->useStoragePid($this->storagePid);
        $this->ingestService->initialize($this->storagePid);
    }

    /**
     * Return matching uid of Solr core depending on the input value.
     *
     * @access protected
     *
     * @param array<string, int> $solrCores array of the valid Solr cores
     * @param bool|string|null $inputSolrId possible uid or name of Solr core
     *
     * @return ?int matching uid of Solr core
     */
    protected function getSolrCoreUid(array $solrCores, bool|string|null $inputSolrId): ?int
    {
        if (MathUtility::canBeInterpretedAsInteger($inputSolrId)) {
            return MathUtility::forceIntegerInRange((int) $inputSolrId, 0);
        } elseif (is_string($inputSolrId) && array_key_exists($inputSolrId, $solrCores)) {
            return $solrCores[$inputSolrId];
        }
        return null;
    }

    /**
     * Fetches all Solr cores on given page.
     *
     * @access protected
     *
     * @param int $pageId The UID of the Solr core or 0 to disable indexing
     *
     * @return array<string, int> Array of valid Solr cores
     */
    protected function getSolrCores(int $pageId): array
    {
        $this->solrCoreRepository->useStoragePid($pageId);

        $solrCores = [];
        $cores = $this->solrCoreRepository->findAll();
        /** @var SolrCore $core */
        foreach ($cores as $core) {
            $solrCores[$core->getIndexName()] = $core->getUid();
        }

        return $solrCores;
    }

    /**
     * Set owner based on input value.
     *
     * @access protected
     *
     * @param bool|string|null $owner input owner value
     *
     * @return void
     */
    protected function setOwner(bool|string|null $owner): void
    {
        if (!empty($owner)) {
            if (MathUtility::canBeInterpretedAsInteger($owner)) {
                $this->owner = $this->libraryRepository->findByUid(MathUtility::forceIntegerInRange((int) $owner, 1));
            } else {
                $this->owner = $this->libraryRepository->findOneBy(['indexName' => (string) $owner]);
            }
        } else {
            $this->owner = null;
        }
    }

    /**
     * Update or insert document to database, see IngestService::saveToDatabase()
     *
     * @access protected
     *
     * @param Document $document The document instance
     * @param bool $softCommit If true, documents are just added to the index by a soft commit
     *
     * @return bool true on success, false otherwise
     */
    protected function saveToDatabase(Document $document, bool $softCommit = false): bool
    {
        $this->ingestService->initialize($this->storagePid, $this->owner);
        $isSaved = $this->ingestService->saveToDatabase($document, $softCommit);
        // The owner may have been determined from the metadata and is kept for subsequent documents.
        $this->owner = $this->ingestService->getOwner();
        return $isSaved;
    }

    /**
     * Handle PID error output.
     *
     * @access protected
     *
     * @param SymfonyStyle $io
     *
     * @return int
     */
    protected function handlePidError(SymfonyStyle $io): int
    {
        $io->error('No valid PID (' . $this->storagePid . ') given.');
        return Command::FAILURE;
    }

    /**
     * Handle Solr error output.
     *
     * @access protected
     *
     * @param array<string, int> $allSolrCores array of the valid Solr cores
     * @param mixed $solr input Solr core value
     * @param SymfonyStyle $io
     *
     * @return int
     */
    protected function handleSolrError(array $allSolrCores, mixed $solr, SymfonyStyle $io): int
    {
        $outputSolrCores = [];
        foreach ($allSolrCores as $indexName => $uid) {
            $outputSolrCores[] = $uid . ' : ' . $indexName;
        }
        if (empty($outputSolrCores)) {
            $io->error('No valid Solr core ("' . $solr . '") given. No valid cores found on PID ' . $this->storagePid . ".\n");
        } else {
            $io->error('No valid Solr core ("' . $solr . '") given. ' . "Valid cores are (<uid>:<index_name>):\n" . implode("\n", $outputSolrCores) . "\n");
        }
        return Command::FAILURE;
    }

    /**
     * Handle Solr missing output.
     *
     * @access protected
     *
     * @param SymfonyStyle $io
     *
     * @return int
     */
    protected function handleSolrMissingError(SymfonyStyle $io): int
    {
        $io->error('Required parameter --solr|-s is missing or array.');
        return Command::FAILURE;
    }

}
