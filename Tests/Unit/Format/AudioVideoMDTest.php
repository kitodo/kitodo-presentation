<?php

namespace Kitodo\Dlf\Tests\Unit\Format;

use Kitodo\Dlf\Format\AudioVideoMD;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AudioVideoMDTest extends UnitTestCase
{
    protected $metadata = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->metadata = [
            'duration' => [],
            'video_duration' => [],
            'audio_duration' => []
        ];
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function canExtractDuration(): void
    {
        $xml = simplexml_load_file( __DIR__ . '/../../Fixtures/Format/audioVideo.xml');
        $audioVideoMD = new AudioVideoMD();

        $videoXml = $xml->xpath('//mets:xmlData')[0];

        $audioVideoMD->extractMetadata($videoXml,$this->metadata);

        $this->assertEquals(
            [
                'duration' => ["00:01:30.07"],
                'video_duration' => ["00:01:30.07"],
                'audio_duration' => ["01:10:35.08"]
            ],
            $this->metadata
        );
    }
}
