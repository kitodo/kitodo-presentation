<?php

namespace Kitodo\Dlf\Tests\Unit\Format;

use Kitodo\Dlf\Format\AudioVideoMD;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AudioVideaoMDTest extends UnitTestCase
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
    public function canExtractVideoDuration(): void
    {
        $xml = simplexml_load_file( __DIR__ . '/../../Fixtures/Format/audioVideo.xml');
        $audioVideoMD = new AudioVideoMD();

        $videoXml = $xml->xpath('//videomd:VIDEOMD')[0];

        $audioVideoMD->extractMetadata($videoXml,$this->metadata);

        $this->assertEquals(
            ["00:01:30.07"],
            $this->metadata['duration']
        );

        $this->assertEquals(
            ["00:01:30.07"],
            $this->metadata['video_duration']
        );

        $this->assertEquals(
            [],
            $this->metadata['audio_duration']
        );
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function canExtractAudioDuration(): void
    {
        $xml = simplexml_load_file( __DIR__ . '/../../Fixtures/Format/audioVideo.xml');
        $audioVideoMD = new AudioVideoMD();

        $audioXml = $xml->xpath('//audiomd:AUDIOMD')[0];

        $audioVideoMD->extractMetadata($audioXml,$this->metadata);

        $this->assertEquals(
            ["01:10:35.08"],
            $this->metadata['duration']
        );

        $this->assertEquals(
            [],
            $this->metadata['video_duration']
        );

        $this->assertEquals(
            ["01:10:35.08"],
            $this->metadata['audio_duration']
        );
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function noDuration(): void
    {
        $xml = simplexml_load_file( __DIR__ . '/../../Fixtures/Format/audioVideo.xml');
        $audioVideoMD = new AudioVideoMD();

        $videoXml = $xml->xpath('//audiomd:AUDIOMD')[1];

        $audioVideoMD->extractMetadata($videoXml,$this->metadata);

        $this->assertEquals(
            [],
            $this->metadata['duration']
        );

        $this->assertEquals(
            [],
            $this->metadata['audio_duration']
        );

        $this->assertEquals(
            [],
            $this->metadata['video_duration']
        );
    }
}
