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
    protected $metadataRepository;

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
     * @param $settings
     * @return array
     */
    protected function findBySettings($settings)
    {
        $metadata = $this->metadataRepository->findBySettings($settings);
        $this->assertNotNull($metadata);
        $this->assertInstanceOf(QueryResult::class, $metadata);

        $metadataByLabel = [];
        foreach ($metadata as $mdata) {
            $metadataByLabel[$mdata->getLabel()] = $mdata;
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
        $this->assertCount(6, $metadataByLabel);
        $this->assertEquals(
            'Ort, Untertitel, Autor, Institution, Sammlungen, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(['is_listed' => true]);
        $this->assertCount(3, $metadataByLabel);
        $this->assertEquals(
            'Autor, Institution, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(['is_sortable' => true]);
        $this->assertCount(4, $metadataByLabel);
        $this->assertEquals(
            'Ort, Untertitel, Autor, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(
            [
                'is_sortable' => true,
                'is_listed' => true
            ]
        );
        $this->assertCount(2, $metadataByLabel);
        $this->assertEquals(
            'Autor, Titel',
            implode(', ', array_keys($metadataByLabel))
        );
    }
}
