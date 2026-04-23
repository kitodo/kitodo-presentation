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
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
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
        $settings = ['show_userdefined' => false, 'storagePid' => 20000];
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
