<?php

namespace Kitodo\Dlf\Tests\Functional\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class MetadataRepsitoryTest extends FunctionalTestCase
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

        $this->importDataSet(__DIR__ . '/../../Fixtures/Repository/metadata.xml');
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
        $this->assertEquals(6, sizeof($metadataByLabel));
        $this->assertEquals(
            'Ort, Untertitel, Autor, Institution, Sammlungen, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(['is_listed' => true]);
        $this->assertEquals(3, sizeof($metadataByLabel));
        $this->assertEquals(
            'Autor, Institution, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings(['is_sortable' => true]);
        $this->assertEquals(4, sizeof($metadataByLabel));
        $this->assertEquals(
            'Ort, Untertitel, Autor, Titel',
            implode(', ', array_keys($metadataByLabel))
        );

        $metadataByLabel = $this->findBySettings([
            'is_sortable' => true,
            'is_listed' => true
        ]);
        $this->assertEquals(2, sizeof($metadataByLabel));
        $this->assertEquals(
            'Autor, Titel',
            implode(', ', array_keys($metadataByLabel))
        );
    }
}
