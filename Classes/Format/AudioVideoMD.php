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

/**
 * Process AudioMD and VideoMD metadata.
 *
 * The technical reason for handling both formats here is that this makes it slightly more
 * straightforward to extract `duration` as either video duration or audio duration.
 *
 * @author Kajetan Dvoracek <kajetan.dvoracek@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class AudioVideoMD implements \Kitodo\Dlf\Common\MetadataInterface
{
    /**
     * Extract some essential AudioMD/VideoMD metadata.
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml: The XML to extract the metadata from
     * @param array &$metadata: The metadata array to fill
     *
     * @return void
     */
    public function extractMetadata(\SimpleXMLElement $xml, array &$metadata)
    {
        $xml->registerXPathNamespace('audiomd', 'http://www.loc.gov/audioMD/');
        $xml->registerXPathNamespace('videomd', 'http://www.loc.gov/videoMD/');

        if (!empty($audioDuration = (string) $xml->xpath('./audiomd:audioInfo/audiomd:duration')[0])) {
            $metadata['audio_duration'] = [$audioDuration];
        }

        if (!empty($videoDuration = (string) $xml->xpath('./videomd:videoInfo/videomd:duration')[0])) {
            $metadata['video_duration'] = [$videoDuration];
        }

        $metadata['duration'] = $metadata['video_duration'] ?: $metadata['audio_duration'] ?: [];
    }
}
