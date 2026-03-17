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

namespace Kitodo\Dlf\Controller;

use Psr\Http\Message\ResponseInterface;
use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Plugin MediaPlayer for the 'dlf' extension
 *
 * @author Kajetan Dvoracek <kajetan.dvoracek@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class MediaPlayerController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissingOrEmpty()) {
            // Quit without doing anything if required variables are not set.
            return $this->htmlResponse();
        } else {
            $this->setPage();
            $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
        }

        $doc = $this->document->getCurrentDocument();
        $pageNo = $this->requestData['page'];
        $media = $this->getMediaplayerInfo($doc, $pageNo);
        if ($media === null) {
            return $this->htmlResponse();
        }

        $this->addPlayerAssets();

        $this->view->assign('media', $media);

        return $this->htmlResponse();
    }

    /**
     * Build Mediaplayer info to be passed to the player template.
     *
     * @param AbstractDocument $doc
     * @param int $pageNo
     *
     * @return ?mixed[] The Mediaplayer data, or `null` if no audio/video-media source is found
     */
    protected function getMediaplayerInfo(AbstractDocument $doc, int $pageNo): ?array
    {
        // Get audio file use groups
        $audioUseGroups = $this->useGroupsConfiguration->getAudio();
        // Get video file use groups
        $videoUseGroups = $this->useGroupsConfiguration->getVideo();
        $mainVideoUseGroup = $videoUseGroups[0] ?? '';
        // Merge audio and video file use groups without duplicates
        $mediaplayerUseGroups = array_unique([...$audioUseGroups, ...$videoUseGroups]);

        // Get thumbnail file use groups
        $thumbnailUseGroups = $this->useGroupsConfiguration->getThumbnail();
        // Get waveform file use groups
        $waveformUseGroups = $this->useGroupsConfiguration->getWaveform();
        // Get image file use groups
        $imageUseGroups = $this->useGroupsConfiguration->getImage();

        // Collect audio/video-media file source URLs
        // TODO: This is for multiple sources (MPD, HLS, MP3, ...) - revisit, make sure it's ordered by preference!
        $mediaplayerSources = $this->collectMediaSources($doc, $pageNo, $mediaplayerUseGroups);
        if (empty($mediaplayerSources)) {
            return null;
        }

        // List all chapters for chapter markers
        $mediaChapters = $this->collectMediaChapters($doc);

        // Get additional audio/video-media URLs
        $mediaUrl = $this->collectAdditionalMediaUrls($doc, $pageNo, $thumbnailUseGroups, $waveformUseGroups, $imageUseGroups);

        return [
            'start' => $mediaChapters[$pageNo - 1]['timecode'] ?? '',
            'mode' => $this->determineInitialMode($mediaplayerSources, $mainVideoUseGroup),
            'chapters' => $mediaChapters,
            'metadata' => $doc->getToplevelMetadata(),
            'sources' => $mediaplayerSources,
            'url' => $mediaUrl,
        ];
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
     * Determine the initial mode (video or audio) based on the provided audio/video-media sources and the main video use group.
     *
     * @param mixed[] $mediaplayerSources An array of media sources with details like MIME type, URL, file ID, and frame rate
     * @param string $mainVideoUseGroup The main video use group to prioritize
     *
     * @return string The initial mode ('video' or 'audio')
     */
    private function determineInitialMode(array $mediaplayerSources, string $mainVideoUseGroup): string
    {
        foreach ($mediaplayerSources as $mediaplayerSource) {
            // TODO: Better guess of initial mode?
            //       Perhaps we could look for VIDEOMD/AUDIOMD in METS
            if ($mediaplayerSource['fileGrp'] === $mainVideoUseGroup || str_starts_with($mediaplayerSource['mimeType'], 'video/')) {
                return 'video';
            }
        }
        return 'audio';
    }

    /**
     * Collects all audio/video-media chapters for chapter markers from the given AbstractDocument.
     *
     * @param AbstractDocument $doc The AbstractDocument object to collect media chapters from
     *
     * @return mixed[] An array of media chapters with details like file IDs, page numbers, titles, and timecodes
     */
    private function collectMediaChapters(AbstractDocument $doc): array
    {
        $mediaChapters = [];
        foreach ($doc->tableOfContents as $entry) {
            $this->recurseChapters($entry, $mediaChapters);
        }
        return $mediaChapters;
    }

    /**
     * Collects additional audio/video-media URLs like poster and waveform for a given document, page number, thumb file use groups, and waveform file use groups.
     *
     * @param AbstractDocument $doc The document object
     * @param int $pageNo The page number
     * @param string[] $thumbnailUseGroups An array of thumb file use groups
     * @param string[] $waveformUseGroups An array of waveform file use groups
     * @param string[] $imageUseGroups An array of image file use groups
     *
     * @return mixed[] An array containing additional audio/video-media URLs like poster and waveform
     */
    private function collectAdditionalMediaUrls(AbstractDocument $doc, int $pageNo, array $thumbnailUseGroups, array $waveformUseGroups, array $imageUseGroups): array
    {
        $mediaUrl = [];

        $showPoster = $this->settings['constants']['showPoster'] ?? null;
        $thumbFiles = $this->findFiles($doc, 0, $thumbnailUseGroups); // 0 = for whole video (not just chapter)
        if (!empty($thumbFiles) && (int) $showPoster === 1) {
            $mediaUrl['poster'] = $thumbFiles[0];
        }

        $waveformFiles = $this->findFiles($doc, $pageNo, $waveformUseGroups);
        if (!empty($waveformFiles)) {
            $mediaUrl['waveform'] = $waveformFiles[0];
        }

        $showAudioLabelImage = $this->settings['constants']['showAudioLabelImage'] ?? null;
        $audioLabelImageFiles = $this->findFiles($doc, $pageNo, $imageUseGroups);
        if (!empty($audioLabelImageFiles)
            && (int) $showAudioLabelImage === 1
            && Helper::filterFilesByMimeType($audioLabelImageFiles[0], ['image'], ['JPG'], 'mimeType')
        ) {
            $mediaUrl['audioLabelImage'] = $audioLabelImageFiles[0];
        }

        return $mediaUrl;
    }

    /**
     * Find files of the given file use groups that are referenced on a page.
     *
     * @param AbstractDocument $doc
     * @param int $pageNo
     * @param string[] $fileGrps
     *
     * @return array{fileGrp: string, fileId: string, url: string, mimeType: string}[]
     */
    protected function findFiles(AbstractDocument $doc, int $pageNo, array $fileGrps): array
    {
        $pagePhysKey = $doc->physicalStructure[$pageNo];
        $pageFiles = $doc->physicalStructureInfo[$pagePhysKey]['all_files'] ?? [];
        $filesInGrp = array_intersect_key($pageFiles, array_flip($fileGrps));

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
     * Recursively push chapters in given logical structure to $outChapters.
     *
     * @access protected
     *
     * @param mixed[] $logInfo The logical structure entry to process
     * @param mixed[] $outChapters The output array to collect chapters
     *
     * @return void
     */
    protected function recurseChapters(array $logInfo, array &$outChapters): void
    {
        if (empty($logInfo['children']) && isset($logInfo['videoChapter'])) {
            $outChapters[] = [
                'fileIds' => $logInfo['videoChapter']['fileIds'],
                'fileIdsJoin' => $logInfo['videoChapter']['fileIdsJoin'],
                'pageNo' => $logInfo['points'],
                'title' => $logInfo['label'] ?? '',
                'timecode' => $logInfo['videoChapter']['timecode'],
            ];
        }

        foreach ($logInfo['children'] ?? [] as $child) {
            $this->recurseChapters($child, $outChapters);
        }
    }

    /**
     * Check if the given MIME type corresponds to a media file.
     *
     * @param string $mimeType The MIME type to check
     *
     * @return bool True if the MIME type corresponds to a media file, false otherwise
     */
    protected function isMediaMime(string $mimeType): bool
    {
        return (
            str_starts_with($mimeType, 'audio/')
            || str_starts_with($mimeType, 'video/')
            || $mimeType == 'application/dash+xml'
            || $mimeType == 'application/x-mpegurl'
            || $mimeType == 'application/vnd.apple.mpegurl'
        );
    }

    /**
     * Adds Mediaplayer javascript and css assets
     *
     * @access protected
     *
     * @return void
     */
    protected function addPlayerAssets(): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $assetCollector->addStyleSheet(
            'DlfMediaVendorCss',
            'EXT:dlf/Resources/Public/Css/DlfMediaVendor.css',
            ['type' => 'text/css', 'media' => 'all']
        );
        $assetCollector->addStyleSheet(
            'DlfMediaPlayerStylesCss',
            'EXT:dlf/Resources/Public/Css/DlfMediaPlayerStyles.css',
            ['type' => 'text/css', 'media' => 'all']
        );
        $assetCollector->addJavaScript(
            'DlfMediaPlayerJs',
            'EXT:dlf/Resources/Public/JavaScript/DlfMediaPlayer/DlfMediaPlayer.js',
            ['type' => 'text/javascript']
        );
        $assetCollector->addJavaScript(
            'DlfMediaVendorJs',
            'EXT:dlf/Resources/Public/JavaScript/DlfMediaPlayer/DlfMediaVendor.js',
            ['type' => 'text/javascript']
        );
    }
}
