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

namespace Kitodo\Dlf\Format;

use Kitodo\Dlf\Common\MetadataInterface;

/**
 * Process AudioMD and VideoMD metadata.
 *
 * The technical reason for handling both formats here is that this makes it slightly more
 * straightforward to extract `duration` as either video duration or audio duration.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class AudioVideoMD implements MetadataInterface
{
    /**
     * Extract some essential AudioMD/VideoMD metadata.
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml The XML to extract the metadata from
     * @param mixed[] &$metadata The metadata array to fill
     * @param bool $useExternalApis true if external APIs should be called, false otherwise
     *
     * @return void
     */
    public function extractMetadata(\SimpleXMLElement $xml, array &$metadata, bool $useExternalApis = false): void
    {
        $xml->registerXPathNamespace('audiomd', 'http://www.loc.gov/audioMD/');
        $xml->registerXPathNamespace('videomd', 'http://www.loc.gov/videoMD/');

        $audioDuration = $xml->xpath('./audiomd:audioInfo/audiomd:duration');
        if (!empty($audioDuration) && !empty($audioDuration[0])) {
            $metadata['audio_duration'] = [(string) $audioDuration[0]];
        }

        $videoDuration = $xml->xpath('./videomd:videoInfo/videomd:duration');
        if (!empty($videoDuration) && !empty($videoDuration[0])) {
            $metadata['video_duration'] = [(string) $videoDuration[0]];
        }

        $metadata['duration'] = $metadata['video_duration'] ?: $metadata['audio_duration'] ?: [];

        $videoFrameRate = $xml->xpath('./videomd:fileData/videomd:frameRate[@mode="Fixed"]');
        if (!empty($videoFrameRate) && !empty($videoFrameRate[0])) {
            $metadata['video_frame_rate'] = [(string) $videoFrameRate[0]];
        }

        if ($useExternalApis) {
            // TODO?
        }
    }
}
