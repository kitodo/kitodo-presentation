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

namespace Kitodo\Dlf\Tests\Functional\Repository;

use Doctrine\DBAL\Result;
use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Common\MetsDocument;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Model\Structure;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;

class DocumentRepositoryTest extends FunctionalTestCase
{
    /**
     * @var DocumentRepository
     */
    protected DocumentRepository $documentRepository;

    /**
     * Sets up the test environment.
     *
     * @access public
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = $this->initializeRepository(DocumentRepository::class, 20000);

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/documents_1.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/libraries.csv');
    }

    /**
     * @test
     */
    public function canRetrieveDocument(): void
    {
        $document = $this->documentRepository->findByUid(1001);
        self::assertNotNull($document);
        self::assertEquals('METS', $document->getDocumentFormat());
        self::assertNotEmpty($document->getTitle());
        self::assertEquals('Default Library', $document->getOwner()->getLabel());

        $doc = AbstractDocument::getInstance($document->getLocation());
        self::assertInstanceOf(MetsDocument::class, $doc);
    }

    /**
     * @test
     */
    public function canFindOneByParametersWithIdReturnsDocument(): void
    {
        $result = $this->documentRepository->findOneByParameters(['id' => '1001']);
        self::assertInstanceOf(Document::class, $result);
        self::assertEquals(1001, $result->getUid());
    }

    /**
     * @test
     */
    public function canFindOneByParametersWithRecordIdReturnsDocument(): void
    {
        $result = $this->documentRepository->findOneByParameters(['recordId' => 'oai:de:slub-dresden:db:id-476251419']);
        self::assertInstanceOf(Document::class, $result);
        self::assertEquals('oai:de:slub-dresden:db:id-476251419', $result->getRecordId());
    }

    /**
     * @test
     */
    public function canFindOneByParametersWithLocationReturnsDocumentWithLocation(): void
    {
        $location = 'https://digital.slub-dresden.de/data/kitodo/10Kepi_476251419/10Kepi_476251419_mets.xml';
        $result = $this->documentRepository->findOneByParameters(['location' => $location]);
        self::assertInstanceOf(Document::class, $result);
        self::assertEquals($location, $result->getLocation());
    }

    /**
     * @test
     */
    public function canGetChildrenOfYearAnchor(): void
    {
        // Create an empty Structure instance; repository should return a QueryResultInterface (possibly empty)
        $structure = GeneralUtility::makeInstance(Structure::class);

        $result = $this->documentRepository->getChildrenOfYearAnchor(null, $structure);

        // The method may return either an array or a QueryResultInterface; accept both
        self::assertTrue(is_array($result) || $result instanceof QueryResultInterface);
    }

    /**
     * @test
     */
    public function canFindOneByIdAndSettings(): void
    {
        $document = $this->documentRepository->findOneByIdAndSettings(1001);
        self::assertInstanceOf(Document::class, $document);
        self::assertEquals(1001, $document->getUid());
    }

    /**
     * @test
     */
    public function canFindDocumentsBySettings(): void
    {
        $settings = ['documentSets' => '1001,1002'];
        $result = $this->documentRepository->findDocumentsBySettings($settings);

        if (is_array($result)) {
            self::assertNotEmpty($result);
            $uids = array_map(static fn($first) => $first->getUid(), $result);
            self::assertContains(1001, $uids);
        } else {
            // QueryResultInterface
            $first = $result->getFirst();
            self::assertInstanceOf(Document::class, $first);
            self::assertEquals(1001, $first->getUid());
        }
    }

    /**
     * @test
     */
    public function canFindAllByCollectionsLimited(): void
    {
        $result = $this->documentRepository->findAllByCollectionsLimited([1101], 1, 2);

        // should return a QueryResultInterface or an array
        self::assertTrue(is_array($result) || $result instanceof QueryResultInterface);

        if (is_array($result)) {
            self::assertNotEmpty($result);
            $uids = array_map(static fn($first) => $first->getUid(), $result);
            self::assertContains(1002, $uids);
        } else {
            // QueryResultInterface
            $first = $result->getFirst();
            self::assertInstanceOf(Document::class, $first);
            self::assertEquals(1002, $first->getUid());
        }
    }

    /**
     * @test
     */
    public function canFindOldestDocument(): void
    {
        $document = $this->documentRepository->findOldestDocument();
        self::assertNotNull($document);
        self::assertEquals(1002, $document->getUid());
    }

    /**
     * @test
     */
    public function canGetOaiRecord(): void
    {
        $settings = ['showUserDefined' => false, 'storagePid' => 20000];
        $parameters = ['identifier' => 'oai:de:slub-dresden:db:id-476251419'];

        $record = $this->documentRepository->getOaiRecord($settings, $parameters);

        self::assertIsArray($record);
        self::assertEquals('oai:de:slub-dresden:db:id-476251419', $record['record_id']);
        self::assertNotEmpty($record['location']);
        self::assertArrayHasKey('collections', $record);

        $collections = explode(' ', $record['collections']);
        self::assertContains('music', $collections);
        self::assertContains('collection-with-single-document', $collections);
    }

    /**
     * @test
     */
    public function canGetOaiDocumentList(): void
    {
        $documents = $this->documentRepository->getOaiDocumentList([1001, 1002]);

        self::assertIsArray($documents);
        self::assertCount(2, $documents);

        $found = array_column($documents, null, 'record_id');
        self::assertArrayHasKey('oai:de:slub-dresden:db:id-476251419', $found);
        self::assertArrayHasKey('oai:de:slub-dresden:db:id-476248086', $found);

        $first = $found['oai:de:slub-dresden:db:id-476251419'];
        self::assertNotEmpty($first['location']);
        $collections = explode(' ', $first['collections']);
        self::assertContains('music', $collections);
    }

    /**
     * @test
     */
    public function canGetCollectionsOfDocument(): void
    {
        $document = $this->documentRepository->findByUid(1001);
        $collections = $document->getCollections();
        self::assertInstanceOf(LazyObjectStorage::class, $collections);

        $collectionsByLabel = [];
        foreach ($collections as $collection) {
            $collectionsByLabel[$collection->getLabel()] = $collection;
        }

        self::assertArrayHasKey('Musik', $collectionsByLabel);
    }
}
