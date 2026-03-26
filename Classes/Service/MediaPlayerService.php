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

namespace Kitodo\Dlf\Service;

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Configuration\UseGroupsConfiguration;

class MediaPlayerService
{
    /**
     * @var UseGroupsConfiguration
     */
    protected UseGroupsConfiguration $useGroupsConfiguration;

    public function __construct()
    {
        $this->useGroupsConfiguration = UseGroupsConfiguration::getInstance();
    }

    /**
     * Check if the document has audio files in configured audio use groups.
     *
     * @param AbstractDocument $doc The document object.
     * @param int $pageNo The page number.
     *
     * @return bool True if audio sources are found, false otherwise.
     */
    public function hasAudioSources(AbstractDocument $doc, int $pageNo): bool
    {
        $audioUseGroups = $this->useGroupsConfiguration->getAudio();
        $sources = $this->getAudioSources($doc, $pageNo);

        return !empty($this->filterSources($sources, $audioUseGroups, 'audio'));
    }

    /**
     * Check if the document has video files in configured video use groups.
     *
     * @param AbstractDocument $doc The document object.
     * @param int $pageNo The page number.
     *
     * @return bool True if video sources are found, false otherwise.
     */
    public function hasVideoSources(AbstractDocument $doc, int $pageNo): bool
    {
        $videoUseGroups = $this->useGroupsConfiguration->getVideo();
        $sources = $this->getVideoSources($doc, $pageNo);

        return !empty($this->filterSources($sources, $videoUseGroups, 'video'));
    }

    /**
     * Returns playable media sources (audio and video) for a given document page.
     *
     * Combines configured audio and video use groups (without duplicates)
     *
     * @param AbstractDocument $doc The document object.
     * @param int $pageNo The page number.
     *
     * @return mixed[] An array of audio and video mediaplayer sources with details like MIME type, URL, file ID, and frame rate
     */
    public function getMediaplayerSources(AbstractDocument $doc, int $pageNo): array
    {
        $audioUseGroups = $this->useGroupsConfiguration->getAudio();
        $videoUseGroups = $this->useGroupsConfiguration->getVideo();
        $mediaplayerUseGroups = array_unique([...$audioUseGroups, ...$videoUseGroups]);

        return $this->collectMediaSources($doc, $pageNo, $mediaplayerUseGroups);
    }

    /**
     * Get audio sources for the given document and page.
     *
     * @param AbstractDocument $doc The document object.
     * @param int $pageNo The page number.
     *
     * @return mixed[] An array of audio sources with details like MIME type, URL, file ID, and frame rate
     */
    protected function getAudioSources(AbstractDocument $doc, int $pageNo): array
    {
        $audioUseGroups = $this->useGroupsConfiguration->getAudio();

        return $this->collectMediaSources($doc, $pageNo, $audioUseGroups);
    }

    /**
     * Get video sources for the given document and page.
     *
     * @param AbstractDocument $doc The document object.
     * @param int $pageNo The page number.
     *
     * @return mixed[] An array of video sources with details like MIME type, URL, file ID, and frame rate
     */
    protected function getVideoSources(AbstractDocument $doc, int $pageNo): array
    {
        $videoUseGroups = $this->useGroupsConfiguration->getVideo();

        return $this->collectMediaSources($doc, $pageNo, $videoUseGroups);
    }

    /**
     * Determine the initial mode (video or audio) based on the provided audio/video-media sources and the main video use group.
     *
     * @param mixed[] $mediaplayerSources An array of media sources with details like MIME type, URL, file ID, and frame rate
     * @param string $mainVideoUseGroup The main video use group to prioritize
     *
     * @return string The initial mode ('video' or 'audio')
     */
    public function determineInitialMode(array $mediaplayerSources, string $mainVideoUseGroup): string
    {
        foreach ($mediaplayerSources as $mediaplayerSource) {
            // TODO: Better guess of initial mode?
            //       Perhaps we could look for VIDEOMD/AUDIOMD in METS
            if ($mediaplayerSource['fileGrp'] === $mainVideoUseGroup || $this->determineMediaTypeFromMime($mediaplayerSource['mimeType']) === 'video') {
                return 'video';
            }
        }
        return 'audio';
    }

    /**
     * Find files of the given file use groups that are referenced on a page.
     *
     * @param AbstractDocument $doc
     * @param int $pageNo
     * @param string[] $useGroups
     *
     * @return array{fileGrp: string, fileId: string, url: string, mimeType: string}[]
     */
    public static function findFiles(AbstractDocument $doc, int $pageNo, array $useGroups): array
    {
        $pagePhysKey = $doc->physicalStructure[$pageNo] ?? null;

        // Return early if the page doesn't exist
        if (!$pagePhysKey || !isset($doc->physicalStructureInfo[$pagePhysKey])) {
            return [];
        }

        $pageFiles = $doc->physicalStructureInfo[$pagePhysKey]['all_files'] ?? [];
        $filesInGrp = array_intersect_key($pageFiles, array_flip($useGroups));

        $result = [];
        foreach ($filesInGrp as $fileGrp => $fileIds) {
            foreach ($fileIds as $fileId) {
                $result[] = [
                    'fileGrp' => $fileGrp,
                    'fileId' => $fileId,
                    'url' => $doc->getFileLocation($fileId),
                    'mimeType' => $doc->getFileMimeType($fileId),
                ];
            }
        }

        return $result;
    }

    /**
     * Collects Audio/Video-Media sources for the given document.
     *
     * @param AbstractDocument $doc The document object to collect media sources from
     * @param int $pageNo The page number to collect media sources for
     * @param string[] $mediaplayerUseGroups The array of mediaplayer use groups to search for media sources
     *
     * @return mixed[] An array of media sources with details like MIME type, URL, file ID, and frame rate
     */
    private function collectMediaSources(AbstractDocument $doc, int $pageNo, array $mediaplayerUseGroups): array
    {
        $mediaplayerSources = [];
        $mediaFiles = $this->findFiles($doc, $pageNo, $mediaplayerUseGroups);
        foreach ($mediaFiles as $mediaFile) {
            if ($this->isMediaMime($mediaFile['mimeType'])) {
                $fileMetadata = $doc->getMetadata($mediaFile['fileId']);

                $mediaplayerSources[] = [
                    'fileGrp' => $mediaFile['fileGrp'],
                    'mimeType' => $mediaFile['mimeType'],
                    'url' => $mediaFile['url'],
                    'fileId' => $mediaFile['fileId'],
                    'frameRate' => $fileMetadata['video_frame_rate'][0] ?? '',
                ];
            }
        }

        return $mediaplayerSources;
    }

    /**
     * Check if the given MIME type corresponds to a media file.
     *
     * @param string $mimeType The MIME type to check
     *
     * @return bool True if the MIME type corresponds to a media file, false otherwise
     */
    private function isMediaMime(string $mimeType): bool
    {
        return (
            str_starts_with($mimeType, 'audio/')
            || str_starts_with($mimeType, 'video/')
            || $mimeType === 'application/dash+xml'
            || $mimeType === 'application/x-mpegurl'
            || $mimeType === 'application/vnd.apple.mpegurl'
        );
    }

    /**
     * Filters sources based on useGroups configuration and media type.
     *
     * @param mixed[] $sources
     * @param string[] $useGroups
     * @param string $mediaType 'audio' or 'video'
     *
     * @return mixed[] The filtered array of sources matching the media type and use group criteria
     */
    private function filterSources(array $sources, array $useGroups, string $mediaType): array
    {
        return array_filter($sources, function ($source) use ($useGroups, $mediaType) {
            if ($this->isDefaultUseGroupScenario($useGroups, $source['fileGrp'])) {
                return $this->determineMediaTypeFromMime($source['mimeType']) === $mediaType;
            }

            return true;
        });
    }

    /**
     * Quick determine media type based on MIME type.
     * No Helper::filterFilesByMimeType() needed, because only MIME types from @see isMediaMime() are allowed in the array
     *
     * @param string $mimeType
     * @return string|null 'audio', 'video', or null if unknown
     */
    private function determineMediaTypeFromMime(string $mimeType): ?string
    {
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        if (str_starts_with($mimeType, 'video/') || str_starts_with($mimeType, 'application/')) {
            return 'video';
        }

        return null;
    }

    /**
     * Determines whether the given file group matches the default use group scenario.
     *
     * A default scenario is met when 'DEFAULT' exists in the configured use groups
     * and the provided file group is also 'DEFAULT'.
     *
     * @param string[] $configuredUseGroups List of configured use group identifiers.
     * @param string $fileGrp The file group to check.
     *
     * @return bool True if the default use group scenario applies, otherwise false.
     */
    private function isDefaultUseGroupScenario(array $configuredUseGroups, string $fileGrp): bool
    {
        return in_array('DEFAULT', $configuredUseGroups, true) && $fileGrp === 'DEFAULT';
    }
}