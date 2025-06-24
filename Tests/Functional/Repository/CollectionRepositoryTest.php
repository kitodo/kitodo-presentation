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

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class CollectionRepositoryTest extends FunctionalTestCase
{
    /**
     * @var CollectionRepository
     */
    protected CollectionRepository $collectionRepository;

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

        $this->collectionRepository = $this->initializeRepository(
            CollectionRepository::class,
            20000
        );

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Repository/collections.csv');
    }

    /**
     *
     * @group find
     */
    public function canFindAllByUids(): void
    {
        $collections = $this->collectionRepository->findAllByUids([1101, 1102]);
        self::assertNotNull($collections);
        self::assertInstanceOf(QueryResult::class, $collections);

        $collectionsByLabel = [];
        foreach ($collections as $collection) {
            $collectionsByLabel[$collection->getLabel()] = $collection;
        }

        self::assertArrayHasKey('Musik', $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);
    }

    /**
     * @param $settings
     * @return array
     */
    protected function findCollectionsBySettings($settings): array
    {
        $collections = $this->collectionRepository->findCollectionsBySettings($settings);
        self::assertNotNull($collections);
        self::assertInstanceOf(QueryResult::class, $collections);

        $collectionsByLabel = [];
        foreach ($collections as $collection) {
            $collectionsByLabel[$collection->getLabel()] = $collection;
        }

        return $collectionsByLabel;
    }

    /**
     * @test
     * @group find
     */
    public function canFindCollectionsBySettings(): void
    {
        $collectionsByLabel = $this->findCollectionsBySettings(['collections' => '1101, 1102']);
        self::assertCount(2, $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);
        self::assertArrayHasKey('Musik', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'index_name' => ['Geschichte', 'collection-with-single-document'],
                'show_userdefined' => true
            ]
        );
        self::assertCount(2, $collectionsByLabel);
        self::assertArrayHasKey('Geschichte', $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(['show_userdefined' => true]);
        self::assertCount(4, $collectionsByLabel);
        self::assertArrayHasKey('Musik', $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);
        self::assertArrayHasKey('Geschichte', $collectionsByLabel);
        self::assertArrayHasKey('Bildende Kunst', $collectionsByLabel);
        self::assertEquals(
            'Bildende Kunst, Collection with single document, Geschichte, Musik',
            implode(', ', array_keys($collectionsByLabel))
        );

        $collectionsByLabel = $this->findCollectionsBySettings(['show_userdefined' => false]);
        self::assertCount(2, $collectionsByLabel);
        self::assertArrayHasKey('Musik', $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(['hideEmptyOaiNames' => true]);
        self::assertCount(2, $collectionsByLabel);
        self::assertArrayHasKey('Musik', $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'hideEmptyOaiNames' => true,
                'show_userdefined' => true
            ]
        );
        self::assertCount(3, $collectionsByLabel);
        self::assertArrayHasKey('Musik', $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);
        self::assertArrayHasKey('Geschichte', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'hideEmptyOaiNames' => false,
                'show_userdefined' => true
            ]
        );
        self::assertCount(4, $collectionsByLabel);
        self::assertArrayHasKey('Musik', $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);
        self::assertArrayHasKey('Geschichte', $collectionsByLabel);
        self::assertArrayHasKey('Bildende Kunst', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'collections' => '1101, 1102, 1103, 1104',
                'show_userdefined' => true,
                'hideEmptyOaiNames' => false,
                'index_name' => ['Geschichte', 'collection-with-single-document']
            ]
        );

        self::assertCount(2, $collectionsByLabel);
        self::assertArrayHasKey('Collection with single document', $collectionsByLabel);
        self::assertArrayHasKey('Geschichte', $collectionsByLabel);
    }

    /**
     * @test
     * @group find
     */
    public function canGetIndexNameForSolr(): void
    {
        $indexName = $this->collectionRepository->getIndexNameForSolr(
            ['show_userdefined' => true, 'storagePid' => '20000'], 'history'
        );
        $result = $indexName->fetchAllAssociative();
        self::assertEquals(1, $indexName->rowCount());
        self::assertEquals('Geschichte', $result[0]['index_name']);
        self::assertEquals('*:*', $result[0]['index_query']);
        self::assertEquals('1103', $result[0]['uid']);

        $indexName = $this->collectionRepository->getIndexNameForSolr(
            ['show_userdefined' => false, 'storagePid' => '20000'], 'history'
        );
        self::assertEquals(0, $indexName->rowCount());

        $indexName = $this->collectionRepository->getIndexNameForSolr(
            ['show_userdefined' => false, 'storagePid' => '20000'], 'collection-with-single-document'
        );
        self::assertEquals(1, $indexName->rowCount());
        self::assertEquals('collection-with-single-document', $indexName->fetchOne());
    }
}
