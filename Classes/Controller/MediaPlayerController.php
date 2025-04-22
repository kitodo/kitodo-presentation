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
        $video = $this->getVideoInfo($doc, $pageNo);
        if ($video === null) {
            return $this->htmlResponse();
        }

        $this->addPlayerAssets();

        $this->view->assign('video', $video);

        return $this->htmlResponse();
    }

    /**
     * Build video info to be passed to the player template.
     *
     * @param AbstractDocument $doc
     * @param int $pageNo
     * @return ?array The video data, or `null` if no video source is found
     */
    protected function getVideoInfo(AbstractDocument $doc, int $pageNo): ?array
    {
        // Get video file use groups
        $videoUseGroups = $this->useGroupsConfiguration->getVideo();
        $mainVideoUseGroup = $videoUseGroups[0] ?? '';

        // Get thumbnail file use groups
        $thumbnailUseGroups = $this->useGroupsConfiguration->getThumbnail();

        // Get waveform file use groups
        $waveformUseGroups = $this->useGroupsConfiguration->getWaveform();

        // Collect video file source URLs
        // TODO: This is for multiple sources (MPD, HLS, MP3, ...) - revisit, make sure it's ordered by preference!
        $videoSources = $this->collectVideoSources($doc, $pageNo, $videoUseGroups);
        if (empty($videoSources)) {
            return null;
        }

        // List all chapters for chapter markers
        $videoChapters = $this->collectVideoChapters($doc);

        // Get additional video URLs
        $videoUrl = $this->collectAdditionalVideoUrls($doc, $pageNo, $thumbnailUseGroups, $waveformUseGroups);

        return [
            'start' => $videoChapters[$pageNo - 1]['timecode'] ?? '',
            'mode' => $this->determineInitialMode($videoSources, $mainVideoUseGroup),
            'chapters' => $videoChapters,
            'metadata' => $doc->getToplevelMetadata($this->settings['storagePid']),
            'sources' => $videoSources,
            'url' => $videoUrl,
        ];
    }

    /**
     * Collects video sources for the given document.
     *
     * @param AbstractDocument $doc The document object to collect video sources from
     * @param int $pageNo The page number to collect video sources for
     * @param array $videoUseGroups The array of video use groups to search for video sources
     * @return array An array of video sources with details like MIME type, URL, file ID, and frame rate
     */
    private function collectVideoSources(AbstractDocument $doc, int $pageNo, array $videoUseGroups): array
    {
        $videoSources = [];
        $videoFiles = $this->findFiles($doc, $pageNo, $videoUseGroups);
        foreach ($videoFiles as $videoFile) {
            if ($this->isMediaMime($videoFile['mimeType'])) {
                $fileMetadata = $doc->getMetadata($videoFile['fileId'], $this->settings['storagePid']);

                $videoSources[] = [
                    'fileGrp' => $videoFile['fileGrp'],
                    'mimeType' => $videoFile['mimeType'],
                    'url' => $videoFile['url'],
                    'fileId' => $videoFile['fileId'],
                    'frameRate' => $fileMetadata['video_frame_rate'][0] ?? '',
                ];
            }
        }
        return $videoSources;
    }

    /**
     * Determine the initial mode (video or audio) based on the provided video sources and the main video use group.
     *
     * @param array $videoSources An array of video sources with details like MIME type, URL, file ID, and frame rate
     * @param string $mainVideoUseGroup The main video use group to prioritize
     * @return string The initial mode ('video' or 'audio')
     */
    private function determineInitialMode(array $videoSources, string $mainVideoUseGroup): string
    {
        foreach ($videoSources as $videoSource) {
            // TODO: Better guess of initial mode?
            //       Perhaps we could look for VIDEOMD/AUDIOMD in METS
            if ($videoSource['fileGrp'] === $mainVideoUseGroup || strpos($videoSource['mimeType'], 'video/') === 0) {
                return 'video';
            }
        }
        return 'audio';
    }

    /**
     * Collects all video chapters for chapter markers from the given AbstractDocument.
     *
     * @param AbstractDocument $doc The AbstractDocument object to collect video chapters from
     * @return array An array of video chapters with details like file IDs, page numbers, titles, and timecodes
     */
    private function collectVideoChapters(AbstractDocument $doc): array
    {
        $videoChapters = [];
        foreach ($doc->tableOfContents as $entry) {
            $this->recurseChapters($entry, $videoChapters);
        }
        return $videoChapters;
    }

    /**
     * Collects additional video URLs like poster and waveform for a given document, page number, thumb file use groups, and waveform file use groups.
     *
     * @param AbstractDocument $doc The document object
     * @param int $pageNo The page number
     * @param array $thumbnailUseGroups An array of thumb file use groups
     * @param array $waveformUseGroups An array of waveform file use groups
     * @return array An array containing additional video URLs like poster and waveform
     */
    private function collectAdditionalVideoUrls(AbstractDocument $doc, int $pageNo, array $thumbnailUseGroups, array $waveformUseGroups): array
    {
        $videoUrl = [];
        $thumbFiles = $this->findFiles($doc, 0, $thumbnailUseGroups); // 0 = for whole video (not just chapter)
        if (!empty($thumbFiles) && $this->settings['constants']['showPoster'] == 1) {
            $videoUrl['poster'] = $thumbFiles[0];
        }
        $waveformFiles = $this->findFiles($doc, $pageNo, $waveformUseGroups);
        if (!empty($waveformFiles)) {
            $videoUrl['waveform'] = $waveformFiles[0];
        }
        return $videoUrl;
    }

    /**
     * Find files of the given file use groups that are referenced on a page.
     *
     * @param AbstractDocument $doc
     * @param int $pageNo
     * @param string[] $fileGrps
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
     */
    protected function recurseChapters(array $logInfo, array &$outChapters)
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
     * @return bool True if the MIME type corresponds to a media file, false otherwise
     */
    protected function isMediaMime(string $mimeType)
    {
        return (
            strpos($mimeType, 'audio/') === 0
            || strpos($mimeType, 'video/') === 0
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
