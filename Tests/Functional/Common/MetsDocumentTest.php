<?php

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class MetsDocumentTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/metadata.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/MetsDocument/metadata_mets.xml');
    }

    protected function doc(string $file)
    {
        $url = 'http://web:8001/Tests/Fixtures/MetsDocument/' . $file;
        $doc = Doc::getInstance($url);
        $this->assertNotNull($doc);
        return $doc;
    }

    /**
     * @test
     */
    public function canParseDmdAndAmdSec()
    {
        $doc = $this->doc('av_beispiel.xml');

        $titledata = $doc->getTitledata(20000);

        $this->assertEquals(['Odol-Mundwasser, 3 Werbespots'], $titledata['title']);
        $this->assertEquals(['24'], $titledata['frame_rate']);
        $this->assertEquals(['Sächsische Landesbibliothek - Staats- und Universitätsbibliothek Dresden'], $titledata['dvrights_owner']);
        $this->assertEquals(['https://katalog.slub-dresden.de/id/0-1703800435'], $titledata['dvlinks_reference']);

        $this->assertEquals([
            'DMDLOG_0000' => $doc->mdSec['DMDLOG_0000'],
        ], $doc->dmdSec);
    }

    /**
     * @test
     */
    public function canReadFileMetadata()
    {
        $doc = $this->doc('av_beispiel.xml');

        $thumbsMeta = $doc->getMetadata('FILE_0000_THUMBS', 20000);
        $this->assertEquals($thumbsMeta, []);

        $videoMeta = $doc->getMetadata('FILE_0000_DEFAULT_MOV', 20000);
        $this->assertArrayMatches([
            'frame_rate' => ['24'],
        ], $videoMeta);
    }

    /**
     * @test
     */
    public function canGetLogicalStructure()
    {
        $doc = $this->doc('av_beispiel.xml');

        $toc = $doc->tableOfContents[0] ?? [];

        $this->assertArrayMatches([
            'dmdId' => 'DMDLOG_0000',
            'admId' => 'AMD',
            'videoChapter' => [
                'fileId' => 'FILE_0000_DEFAULT_MOV',
                'timecode' => 0,
            ],
            'children' => [
                [
                    'id' => 'LOG_0001',
                    'dmdId' => '',
                    'admId' => '',
                    'videoChapter' => [
                        'fileId' => 'FILE_0000_DEFAULT_MOV',
                        'timecode' => 0,
                    ],
                ],
                [
                    'id' => 'LOG_0002',
                    'dmdId' => '',
                    'admId' => '',
                    'videoChapter' => [
                        'fileId' => 'FILE_0000_DEFAULT_MOV',
                        'timecode' => 20,
                    ],
                ],
                [
                    'id' => 'LOG_0003',
                    'dmdId' => '',
                    'admId' => '',
                    'videoChapter' => [
                        'fileId' => 'FILE_0000_DEFAULT_MOV',
                        'timecode' => 40,
                    ],
                ],
                [
                    'id' => 'LOG_0004',
                    'dmdId' => '',
                    'admId' => '',
                    'videoChapter' => [
                        'fileId' => 'FILE_0000_DEFAULT_MOV',
                        'timecode' => 60,
                    ],
                ],
            ],
        ], $toc, 'Expected TOC to contain the specified values');
    }

    /**
     * @test
     */
    public function canReadChapterArea()
    {
        $doc = $this->doc('av_beispiel.xml');

        $ch3_page = $doc->physicalStructureInfo[$doc->physicalStructure[3]];
        $this->assertArrayMatches([
            'DEFAULT' => 'FILE_0000_DEFAULT_MP4',
        ], $ch3_page['files']);
        $this->assertArrayMatches([
            'DEFAULT' => [
                'FILE_0000_DEFAULT_MOV',
                'FILE_0000_DEFAULT_MP4',
            ],
        ], $ch3_page['all_files']);
        $this->assertArrayMatches([
            'FILE_0000_DEFAULT_MP4' => [
                'area' => [
                    'begin' => '00:00:40',
                    'betype' => 'TIME',
                    'extent' => '00:00:20',
                    'exttype' => 'TIME',
                ],
            ],
        ], $ch3_page['fileInfos']);
    }

    /**
     * @test
     */
    public function doesNotOverwriteFirstDmdSec()
    {
        $doc = $this->doc('two_dmdsec.xml');

        $titledata = $doc->getTitledata(20000);
        $toc = $doc->tableOfContents[0] ?? [];

        $this->assertEquals('DMDLOG_0000 DMDLOG_0000b', $toc['dmdId']); // TODO: Do we want the raw (non-split) value here?
        $this->assertEquals('Test Value in DMDLOG_0000', $titledata['test_value'][0]);
    }

    /**
     * @test
     */
    public function returnsEmptyMetadataWhenNoDmdSec()
    {
        $doc = $this->doc('two_dmdsec.xml');

        // DMD and AMD works
        $metadata = $doc->getMetadata('LOG_0000', 20000);
        $this->assertEquals('Test Value in DMDLOG_0000', $metadata['test_value'][0]);

        // DMD only works
        $metadata = $doc->getMetadata('LOG_0001', 20000);
        $this->assertEquals(['Test Value in DMDLOG_0000b'], $metadata['test_value']);

        // AMD only does not work
        $metadata = $doc->getMetadata('LOG_0002', 20000);
        $this->assertEquals([], $metadata);
    }
}
