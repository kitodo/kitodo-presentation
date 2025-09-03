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
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class MetadataRepositoryTest extends FunctionalTestCase
{
    /**
     * @var MetadataRepository
     */
    protected MetadataRepository $metadataRepository;

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

        $this->metadataRepository = $this->initializeRepository(
            MetadataRepository::class,
            20000
        );

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Repository/metadata.csv');
    }


    /**
     * Finds metadata by given settings.
     *
     * @param array $settings
     *
     * @return array
     */
    protected function findBySettings(array $settings): array
    {
        $metadata = $this->metadataRepository->findBySettings($settings);
        self::assertNotNull($metadata);
        self::assertInstanceOf(QueryResult::class, $metadata);

        $metadataByLabel = [];
        foreach ($metadata as $data) {
            $metadataByLabel[$data->getLabel()] = $data;
        }

        return $metadataByLabel;
    }

    /**
     * @test
     * @group find
     */
    public function canFindBySettings(): void
    {
        $metadataByLabel = $this->findBySettings([]);
        self::assertCount(6, $metadataByLabel);
        self::assertEquals(
            'Ort, Untertitel, Autor, Institution, Sammlungen, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(['is_listed' => true]);
        self::assertCount(3, $metadataByLabel);
        self::assertEquals(
            'Autor, Institution, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(['is_sortable' => true]);
        self::assertCount(4, $metadataByLabel);
        self::assertEquals(
            'Ort, Untertitel, Autor, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(
            [
                'is_sortable' => true,
                'is_listed' => true
            ]
        );
        self::assertCount(2, $metadataByLabel);
        self::assertEquals(
            'Autor, Titel',
            implode(', ', array_keys($metadataByLabel))
        );
    }
}
