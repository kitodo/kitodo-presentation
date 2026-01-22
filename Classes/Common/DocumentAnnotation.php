<?php

namespace Kitodo\Dlf\Common;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use DateTime;
use Kitodo\Dlf\Domain\Model\Annotation;
use Kitodo\Dlf\Domain\Model\Document;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\Logger;

/**
 * Implementation for displaying annotations from an annotation server to a document
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DocumentAnnotation
{
    /**
     * @var null|DocumentAnnotation
     */
    private static $instance;

    /**
     * @var mixed[]
     */
    protected array $annotationData;

    /**
     * @var Document
     */
    protected $document;

    /**
     * @access protected
     * @var Logger This holds the logger
     */
    protected Logger $logger;

    /**
     * @param mixed[] $annotationData
     * @param Document $document
     */
    private function __construct(array $annotationData, Document $document)
    {
        $this->annotationData = $annotationData;
        $this->document = $document;
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    /**
     * Returns all annotations with valid targets.
     *
     * @access public
     *
     * @return Annotation[]|array
     */
    public function getAnnotations(): array
    {
        if (empty($this->annotationData)) {
            return [];
        }
        $annotations = [];
        foreach ($this->annotationData as $item) {
            $annotation = new Annotation($item);
            $annotationTargets = $annotation->getTargets();
            $targetPages = [];
            foreach ($annotationTargets as $annotationTarget) {
                if ($annotationTarget->isValid()) {
                    if ($annotationTarget->getId()) {
                        if ($this->document->getCurrentDocument()->getFileLocation($annotationTarget->getId())) {
                            if ($this->document->getCurrentDocument() instanceof MetsDocument) {
                                if (
                                    $meiTargetPages = $this->getMeasurePagesByFileId(
                                        $annotationTarget->getId(), $annotationTarget->getRangeValue()
                                    )
                                ) {
                                    $targetPages[] = [
                                        'target' => $annotationTarget,
                                        'pages' => $meiTargetPages,
                                        'verovioRelevant' => true
                                    ];
                                } elseif (
                                    $audioTargetPages = $this->getAudioPagesByFileId(
                                        $annotationTarget->getId(), $annotationTarget->getRangeValue()
                                    )
                                ) {
                                    $targetPages[] = [
                                        'target' => $annotationTarget,
                                        'pages' => $audioTargetPages
                                    ];
                                } elseif ($fileIdTargetPages = $this->getPagesByFileId($annotationTarget->getId())) {
                                    $targetPages[] = [
                                        'target' => $annotationTarget,
                                        'pages' => $fileIdTargetPages
                                    ];
                                } else {
                                    $this->logger->warning(
                                        ' No target pages found! Annotation: "' . $annotation->getId() . '", '
                                        . 'Target: "' . $annotationTarget->getUrl() . '"'
                                    );
                                }
                            }
                        } elseif ($logicalTargetPages = $this->getPagesByLogicalId($annotationTarget->getId())) {
                            $targetPages[] = [
                                'target' => $annotationTarget,
                                'pages' => $logicalTargetPages
                            ];
                        } elseif ($physicalTargetPages = $this->getPagesByPhysicalId($annotationTarget->getId())) {
                            $targetPages[] = [
                                'target' => $annotationTarget,
                                'pages' => $physicalTargetPages
                            ];
                        } else {
                            $this->logger->warning(
                                ' No target pages found! Annotation: "' . $annotation->getId() . '", '
                                . 'Target: "' . $annotationTarget->getUrl() . '"'
                            );
                        }
                    } elseif ($annotationTarget->getObjectId()) {
                         $objectTargetPages = [];
                        foreach ($this->document->getCurrentDocument()->physicalStructureInfo as $physInfo) {
                             $order = $physInfo['order'];
                            if ($order) {
                                 $objectTargetPages[] = $order;
                            }
                        }
                        if ($objectTargetPages) {
                            $targetPages[] = [
                                'target' => $annotationTarget,
                                'pages' => $objectTargetPages
                            ];
                        }
                    } else {
                        $this->logger->warning(
                            ' No target pages found! Annotation: "' . $annotation->getId() . '", '
                            . 'Target: "' . $annotationTarget->getUrl() . '"'
                        );
                    }
                } else {
                    $this->logger->warning(
                        'Invalid target! Annotation: "' . $annotation->getId() . '", '
                        . 'Target: "' . $annotationTarget->getUrl() . '"'
                    );
                }
            }
            $annotation->setTargetPages($targetPages);
            $annotations[] = $annotation;
        }
        return $annotations;
    }

    /**
     * Gets the logicalId related page numbers
     *
     * @access protected
     *
     * @param string $logicalId
     *
     * @return mixed[]
     */
    protected function getPagesByLogicalId(string $logicalId): array
    {
        $pages = [];
        if (
            array_key_exists('l2p', $this->document->getCurrentDocument()->smLinks) &&
            array_key_exists($logicalId, $this->document->getCurrentDocument()->smLinks['l2p'])
        ) {
            $physicalIdentifiers = $this->document->getCurrentDocument()->smLinks['l2p'][$logicalId];
            foreach ($physicalIdentifiers as $physicalIdentifier) {
                if (array_key_exists($physicalIdentifier, $this->document->getCurrentDocument()->physicalStructureInfo)) {
                    $order = $this->document->getCurrentDocument()->physicalStructureInfo[$physicalIdentifier]['order'];
                    if (is_numeric($order)) {
                        $pages[] = $order;
                    }
                }
            }
        }
        return $pages;
    }

    /**
     * Gets the physicalId related page numbers
     *
     * @access protected
     *
     * @param string $physicalId
     *
     * @return mixed[]
     */
    protected function getPagesByPhysicalId(string $physicalId): array
    {
        $pages = [];
        foreach ($this->document->getCurrentDocument()->physicalStructureInfo as $physicalInfo) {
            $order = $physicalInfo['order'];
            if (is_numeric($order)) {
                $pages[] = $order;
            }
        }
        if (array_key_exists($physicalId, $this->document->getCurrentDocument()->physicalStructureInfo)) {
            if ($this->document->getCurrentDocument()->physicalStructureInfo[$physicalId]['type'] === 'physSequence') {
                return $pages;
            }
            return [$this->document->getCurrentDocument()->physicalStructureInfo[$physicalId]['order']];
        }
        return [];
    }

    /**
     * Gets the fileId related page numbers
     *
     * @access protected
     *
     * @param string $fileId
     *
     * @return mixed[]
     */
    protected function getPagesByFileId(string $fileId): array
    {
        $pages = [];
        foreach ($this->document->getCurrentDocument()->physicalStructureInfo as $physicalInfo) {
            if (
                array_key_exists('files', $physicalInfo) &&
                is_array($physicalInfo['files']) &&
                $physicalInfo['type'] !== 'physSequence'
            ) {
                foreach ($physicalInfo['files'] as $file) {
                    if ($file === $fileId) {
                        $pages[] = $physicalInfo['order'];
                    }
                }
            }
        }
        return $pages;
    }

    /**
     * Gets the fileId and audio related page numbers
     *
     * @access protected
     *
     * @param string $fileId
     * @param ?string $range
     *
     * @return mixed[]
     */
    protected function getAudioPagesByFileId(string $fileId, $range = null): array
    {
        $tracks = [];
        foreach ($this->document->getCurrentDocument()->physicalStructureInfo as $physicalInfo) {
            if (array_key_exists('tracks', $physicalInfo) && is_array($physicalInfo['tracks'])) {
                foreach ($physicalInfo['tracks'] as $track) {
                    if ($track['fileid'] === $fileId && $track['betype'] === 'TIME') {
                        $track['order'] = $physicalInfo['order'];
                        $tracks[] = $track;
                    }
                }
            }
        }
        if ($tracks && $range) {
            list($from, $to) = array_map('trim', explode(',', $range));
            $from = sprintf('%02.6f', (empty($from) ? "0" : $from));
            $intervalFrom = \DateTime::createFromFormat('U.u', $from);
            if (empty($to)) {
                $intervalTo = null;
            } else {
                $to = sprintf('%02.6f', $to);
                $intervalTo = \DateTime::createFromFormat('U.u', $to);
            }
            foreach ($tracks as $index => $track) {
                $begin = new DateTime("1970-01-01 " . $track['begin']);
                $extent = new DateTime("1970-01-01 " . $track['extent']);
                $diff = (new DateTime("1970-01-01 00:00:00"))->diff($extent);
                $end = (new DateTime("1970-01-01 " . $track['begin']))->add($diff);
                if (
                    !(
                        $intervalFrom < $end && (
                            $intervalTo === null || $intervalTo > $begin
                        )
                    )
                ) {
                    unset($tracks[$index]);
                }
            }
        }
        // Get the related page numbers
        $trackPages = [];
        foreach ($tracks as $track) {
            if ($track['order'] !== null) {
                $trackPages[] = $track['order'];
            }
        }
        return $trackPages;
    }

    /**
     * Gets the fileId and measure range related page numbers from the musical structMap
     *
     * @param string $fileId
     * @param ?string $range
     *
     * @return mixed[]
     */
    protected function getMeasurePagesByFileId(string $fileId, $range = null): array
    {
        // Get all measures referencing the fileid
        $measures = [];
        // Get the related page numbers
        $measurePages = [];
        $measureIndex = 1;
        $startOrder = 0;
        $endOrder = 0;
        if ($this->document->getCurrentDocument() instanceof MetsDocument) {
            foreach ($this->document->getCurrentDocument()->musicalStructureInfo as $key => $musicalInfo) {
                if ($musicalInfo['type'] === 'measure' && is_array($musicalInfo['files'])) {
                    foreach ($musicalInfo['files'] as $file) {
                        if ($file['fileid'] === $fileId && $file['type'] === 'IDREF') {
                            $measures[] = $musicalInfo;
                        }
                    }
                    if ($measureIndex === 1) {
                        $startOrder = $musicalInfo['order'];
                    }
                    $endOrder = $musicalInfo['order'];
                    $measureIndex += 1;
                }
            }
            // Filter measures by the given range of measure numbers
            if ($measures && $range && !preg_match("/\ball\b/", $range)) {
                $measureNumbers = [];
                $range = preg_replace("/\bend\b/", $endOrder, $range);
                $range = preg_replace("/\bstart\b/", $startOrder, $range);
                $ranges = array_map('trim', explode(',', $range));
                foreach ($ranges as $measureNumber) {
                    if (preg_match('/\d+-\d+/', $measureNumber)) {
                        list($from, $to) = array_map('trim', explode('-', $measureNumber));
                        $measureNumbers = array_merge($measureNumbers, range($from, $to));
                    } else {
                        $measureNumbers[] = (int) $measureNumber;
                    }
                }
                foreach ($measures as $key => $measure) {
                    if (!in_array($measure['order'], $measureNumbers)) {
                        unset($measures[$key]);
                    }
                }
            }
            foreach ($measures as $measure) {
                $measurePages[$measure['order']] = $this->document->getCurrentDocument()->musicalStructure[$measure['order']]['page'];
            }
        }
        return $measurePages;
    }

    /**
     * Returns the raw data of all annotations with a valid verovio target
     *
     * @access public
     *
     * @return mixed[]
     */
    public function getVerovioRelevantAnnotations(): array
    {
        $annotations = [];

        foreach ($this->getAnnotations() as $annotation) {
            if ($annotation->isVerovioRelevant()) {
                $annotations[] = $annotation->getRawData();
            }
        }
        return $annotations;
    }

    /**
     * Loads all annotation data from the annotation server
     *
     * @access protected
     *
     * @param Document $document
     *
     * @return mixed[]
     */
    protected static function loadData(Document $document): array
    {
        $annotationData = [];
        $conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');
        $apiBaseUrl = $conf['annotationServerUrl'] ?? null;
        if ($apiBaseUrl && $document->getCurrentDocument() instanceof MetsDocument) {
            $purl = $document->getCurrentDocument()->mets->xpath('//mods:mods/mods:identifier[@type="purl"]');
            if (count($purl) > 0) {
                $annotationRequest = new AnnotationRequest($apiBaseUrl);
                $annotationData = $annotationRequest->getAll((string) $purl[0]);
            }
        }
        return $annotationData;
    }

    /**
     * Returns the singleton instance of DocumentAnnotation
     *
     * @access public
     *
     * @param Document $document
     *
     * @return DocumentAnnotation|null
     */
    public static function getInstance(Document $document): ?DocumentAnnotation
    {
        if (self::$instance == null) {
            $annotationData = self::loadData($document);
            self::$instance = new DocumentAnnotation($annotationData, $document);
        }
        return self::$instance;
    }
}
