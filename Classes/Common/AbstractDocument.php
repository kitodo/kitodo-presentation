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

namespace Kitodo\Dlf\Common;

use Kitodo\Dlf\Configuration\UseGroupsConfiguration;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Ubl\Iiif\Presentation\Common\Model\Resources\IiifResourceInterface;
use Ubl\Iiif\Tools\IiifHelper;

/**
 * Document class for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @abstract
 *
 * @property int $configPid this holds the PID for the configuration
 * @property-read array $formats this holds the configuration for all supported metadata encodings
 * @property bool $formatsLoaded flag with information if the available metadata formats are loaded
 * @property-read bool $hasFulltext flag with information if there are any fulltext files available
 * @property array $lastSearchedPhysicalPage the last searched logical and physical page
 * @property array $logicalUnits this holds the logical units
 * @property-read array $metadataArray this holds the documents' parsed metadata array
 * @property bool $metadataArrayLoaded flag with information if the metadata array is loaded
 * @property-read int $numPages the holds the total number of pages
 * @property-read int $parentId this holds the UID of the parent document or zero if not multi-volumed
 * @property-read array $physicalStructure this holds the physical structure
 * @property-read array $physicalStructureInfo this holds the physical structure metadata
 * @property bool $physicalStructureLoaded flag with information if the physical structure is loaded
 * @property array $rawTextArray this holds the documents' raw text pages with their corresponding structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
 * @property-read bool $ready Is the document instantiated successfully?
 * @property-read string $recordId the METS file's / IIIF manifest's record identifier
 * @property-read int $rootId this holds the UID of the root document or zero if not multi-volumed
 * @property-read array $smLinks this holds the smLinks between logical and physical structMap
 * @property bool $smLinksLoaded flag with information if the smLinks are loaded
 * @property-read array $tableOfContents this holds the logical structure
 * @property bool $tableOfContentsLoaded flag with information if the table of contents is loaded
 * @property-read string $thumbnail this holds the document's thumbnail location
 * @property bool $thumbnailLoaded flag with information if the thumbnail is loaded
 * @property \SimpleXMLElement $xml this holds the whole XML file as \SimpleXMLElement object
 */
abstract class AbstractDocument
{
    /**
     * @access protected
     * @var Logger This holds the logger
     */
    protected Logger $logger;

    /**
     * @access protected
     * @var int This holds the PID for the configuration
     */
    protected int $configPid = 0;

    /**
     * @access public
     * @static
     * @var string The extension key
     */
    public static string $extKey = 'dlf';

    /**
     * @access protected
     * @var array Additional information about files (e.g., ADMID), indexed by ID.
     */
    protected array $fileInfos = [];

    /**
     * @access protected
     * @var array This holds the configuration for all supported metadata encodings
     *
     * @see loadFormats()
     */
    protected array $formats = [
        'OAI' => [
            'rootElement' => 'OAI-PMH',
            'namespaceURI' => 'http://www.openarchives.org/OAI/2.0/',
        ],
        'METS' => [
            'rootElement' => 'mets',
            'namespaceURI' => 'http://www.loc.gov/METS/',
        ],
        'XLINK' => [
            'rootElement' => 'xlink',
            'namespaceURI' => 'http://www.w3.org/1999/xlink',
        ]
    ];

    /**
     * @access protected
     * @var bool Are the available metadata formats loaded?
     *
     * @see $formats
     */
    protected bool $formatsLoaded = false;

    /**
     * Are there any fulltext files available? This also includes IIIF text annotations
     * with motivation 'painting' if Kitodo.Presentation is configured to store text
     * annotations as fulltext.
     *
     * @access protected
     * @var bool
     */
    protected bool $hasFulltext = false;

    /**
     * @access protected
     * @var array Last searched logical and physical page
     */
    protected array $lastSearchedPhysicalPage = ['logicalPage' => null, 'physicalPage' => null];

    /**
     * @access protected
     * @var array This holds the logical units
     */
    protected array $logicalUnits = [];

    /**
     * This holds the documents' parsed metadata array with their corresponding
     * structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
     *
     * @access protected
     * @var array
     */
    protected array $metadataArray = [];

    /**
     * @access protected
     * @var bool Is the metadata array loaded?
     *
     * @see $metadataArray
     */
    protected bool $metadataArrayLoaded = false;

    /**
     * @access protected
     * @var int The holds the total number of pages
     */
    protected int $numPages = 0;

    /**
     * @access protected
     * @var int This holds the UID of the parent document or zero if not multi-volumed
     */
    protected int $parentId = 0;

    /**
     * @access protected
     * @var array This holds the physical structure
     */
    protected array $physicalStructure = [];

    /**
     * @access protected
     * @var array This holds the physical structure metadata
     */
    protected array $physicalStructureInfo = [];

    /**
     * @access protected
     * @var bool Is the physical structure loaded?
     *
     * @see $physicalStructure
     */
    protected bool $physicalStructureLoaded = false;

    /**
     * @access protected
     * @var int This holds the PID of the document or zero if not in database
     */
    protected int $pid = 0;

    /**
     * This holds the documents' raw text pages with their corresponding
     * structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
     *
     * @access protected
     * @var array
     */
    protected array $rawTextArray = [];

    /**
     * @access protected
     * @var bool Is the document instantiated successfully?
     */
    protected bool $ready = false;

    /**
     * @access protected
     * @var string The METS file's / IIIF manifest's record identifier
     */
    protected string $recordId = '';

    /**
     * @access protected
     * @var int This holds the UID of the root document or zero if not multi-volumed
     */
    protected int $rootId = 0;

    /**
     * @access protected
     * @var bool Is the root id loaded?
     *
     * @see $rootId
     */
    protected bool $rootIdLoaded = false;

    /**
     * @access protected
     * @var array This holds the smLinks between logical and physical structMap
     */
    protected array $smLinks = ['l2p' => [], 'p2l' => []];

    /**
     * @access protected
     * @var bool Are the smLinks loaded?
     *
     * @see $smLinks
     */
    protected bool $smLinksLoaded = false;

    /**
     * This holds the logical structure
     *
     * @access protected
     * @var array
     */
    protected array $tableOfContents = [];

    /**
     * @access protected
     * @var bool Is the table of contents loaded?
     *
     * @see $tableOfContents
     */
    protected bool $tableOfContentsLoaded = false;

    /**
     * @access protected
     * @var string This holds the document's thumbnail location
     */
    protected string $thumbnail = '';

    /**
     * @access protected
     * @var bool Is the document's thumbnail location loaded?
     *
     * @see $thumbnail
     */
    protected bool $thumbnailLoaded = false;

    /**
     * @access protected
     * @var string This holds the toplevel structure's "@ID" (METS) or the manifest's "@id" (IIIF)
     */
    protected string $toplevelId = '';

    /**
     * Holds the configured useGroups as array.
     *
     * @access protected
     * @var \Kitodo\Dlf\Configuration\UseGroupsConfiguration
     */
    protected UseGroupsConfiguration $useGroupsConfiguration;

    /**
     * @access protected
     * @var \SimpleXMLElement This holds the whole XML file as \SimpleXMLElement object
     */
    protected \SimpleXMLElement $xml;

    /**
     * This gets the location of a downloadable file for a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the file node (METS) or the "@id" property of the IIIF resource
     *
     * @return string The file's location as URL
     */
    abstract public function getDownloadLocation(string $id): string;

    /**
     * This gets all file information stored in single array.
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the file node (METS) or the "@id" property of the IIIF resource
     *
     * @return array|null The set of file information
     */
    abstract public function getFileInfo($id): ?array;

    /**
     * This gets the location of a file representing a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the file node (METS) or the "@id" property of the IIIF resource
     *
     * @return string The file's location as URL
     */
    abstract public function getFileLocation(string $id): string;

    /**
     * This gets the location of a file representing a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the file node (METS) or the "@id" property of the IIIF resource
     *
     * @param string $useGroup The "@USE" attribute of the fileGrp node (METS)
     *
     * @return string The file's location as URL
     */
    abstract public function getFileLocationInUsegroup(string $id, string $useGroup): string;

    /**
     * This gets the MIME type of a file representing a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the file node
     *
     * @return string The file's MIME type
     */
    abstract public function getFileMimeType(string $id): string;

    /**
     * This extracts the OCR full text for a physical structure node / IIIF Manifest / Canvas. Text might be
     * given as ALTO for METS or as annotations or ALTO for IIIF resources.
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the physical structure node (METS) or the "@id" property
     * of the Manifest / Range (IIIF)
     *
     * @return string The OCR full text
     */
    abstract public function getFullText(string $id): string;

    /**
     * This gets details about a logical structure element
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the logical structure node (METS) or
     * the "@id" property of the Manifest / Range (IIIF)
     * @param bool $recursive Whether to include the child elements / resources
     *
     * @return array Array of the element's id, label, type and physical page indexes/mptr link
     */
    abstract public function getLogicalStructure(string $id, bool $recursive = false): array;

    /**
     * This extracts all the metadata for a logical structure node
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id The "@ID" attribute of the logical structure node (METS) or the "@id" property
     * of the Manifest / Range (IIIF)
     *
     * @return array The logical structure node's / the IIIF resource's parsed metadata array
     */
    abstract public function getMetadata(string $id): array;

    /**
     * Analyze the document if it contains any fulltext that needs to be indexed.
     *
     * @access protected
     *
     * @abstract
     *
     * @return void
     */
    abstract protected function ensureHasFulltextIsSet(): void;

    /**
     * This ensures that the recordId, if existent, is retrieved from the document
     *
     * @access protected
     *
     * @abstract
     *
     * @param int $pid ID of the configuration page with the recordId config
     *
     * @return void
     */
    abstract protected function establishRecordId(int $pid): void;

    /**
     * Source document PHP object which is represented by a Document instance
     *
     * @access protected
     *
     * @abstract
     *
     * @return \SimpleXMLElement|IiifResourceInterface A PHP object representation of
     * the current document. SimpleXMLElement for METS, IiifResourceInterface for IIIF
     */
    abstract protected function getDocument();

    /**
     * This builds an array of the document's physical structure
     *
     * @access protected
     *
     * @abstract
     *
     * @return array Array of physical elements' id, type, label and file representations ordered
     * by "@ORDER" attribute / IIIF Sequence's Canvases
     */
    abstract protected function magicGetPhysicalStructure(): array;

    /**
     * This returns the smLinks between logical and physical structMap (METS) and models the
     * relation between IIIF Canvases and Manifests / Ranges in the same way
     *
     * @access protected
     *
     * @abstract
     *
     * @return array The links between logical and physical nodes / Range, Manifest and Canvas
     */
    abstract protected function magicGetSmLinks(): array;

    /**
     * This returns the document's thumbnail location
     *
     * @access protected
     *
     * @abstract
     *
     * @return string The document's thumbnail location
     */
    abstract protected function magicGetThumbnail(): string;

    /**
     * This returns the ID of the toplevel logical structure node
     *
     * @access public
     *
     * @abstract
     *
     * @return string The logical structure node's ID
     */
    abstract public function getToplevelId(): string;

    /**
     * This sets some basic class properties
     *
     * @access protected
     *
     * @abstract
     *
     * @param string $location The location URL of the XML file to parse
     * @param array $settings The extension settings
     *
     * @return void
     */
    abstract protected function init(string $location, array $settings): void;

    /**
     * Format specific part of building the document's metadata array
     *
     * @access protected
     *
     * @abstract
     *
     * @return void
     */
    abstract protected function prepareMetadataArray(): void;

    /**
     * Reuse any document object that might have been already loaded to determine whether document is METS or IIIF
     *
     * @access protected
     *
     * @abstract
     *
     * @param \SimpleXMLElement|IiifResourceInterface $preloadedDocument any instance that has already been loaded
     *
     * @return bool true if $preloadedDocument can actually be reused, false if it has to be loaded again
     */
    abstract protected function setPreloadedDocument($preloadedDocument): bool;

    /**
     * Get information about all files contained in the document, or null if this information is not available.
     *
     * Returns an associative array of the following form:
     *
     * ```php
     * [
     *     '#FILE_ID' => [
     *         'url' => '...',
     *         'mimetype' => '...',
     *     ],
     *     // ...
     * ]
     * ```
     *
     * @access public
     *
     * @return array
     */
    public abstract function getAllFiles();

    /**
     * This is a singleton class, thus an instance must be created by this method
     *
     * @access public
     *
     * @static
     *
     * @param string $location The URL of XML file or the IRI of the IIIF resource
     * @param array $settings
     * @param bool $forceReload Force reloading the document instead of returning the cached instance
     *
     * @return AbstractDocument|null Instance of this class, either MetsDocument or IiifManifest
     */
    public static function &getInstance(string $location, array $settings = [], bool $forceReload = false)
    {
        // Create new instance depending on format (METS or IIIF) ...
        $documentFormat = null;
        $xml = null;
        $iiif = null;

        if (!$forceReload) {
            $instance = GeneralUtility::makeInstance(DocumentCacheManager::class)->get($location);
            if ($instance !== false) {
                return $instance;
            }
        }

        GeneralUtility::makeInstance(DocumentCacheManager::class)->remove($location);
        $instance = null;

        // Try to load a file from the url
        if (GeneralUtility::isValidUrl($location)) {
            $content = Helper::getUrl($location);
            if ($content !== false) {
                $xml = Helper::getXmlFileAsString($content);
                if ($xml !== false) {
                    $xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');
                    $xpathResult = $xml->xpath('//mets:mets');
                    $documentFormat = !empty($xpathResult) ? 'METS' : null;
                } else {
                    // Try to load file as IIIF resource instead.
                    $contentAsJsonArray = json_decode($content, true);
                    if ($contentAsJsonArray !== null) {
                        $iiif = self::loadIiifResource($contentAsJsonArray);
                    }
                }
            }
        } else {
            Helper::error('Invalid file location "' . $location . '" for document loading');
        }

        if ($documentFormat == 'METS') {
            $instance = new MetsDocument($location, $xml, $settings);
        } elseif ($iiif instanceof IiifResourceInterface) {
            $instance = new IiifManifest($location, $iiif, $settings);
        }

        if ($instance !== null) {
            GeneralUtility::makeInstance(DocumentCacheManager::class)->set($location, $instance);
        }

        return $instance;
    }

    /**
     * This returns the first corresponding physical page number of a given logical page label
     *
     * @access public
     *
     * @param string $logicalPage The label (or a part of the label) of the logical page
     *
     * @return int The physical page number
     */
    public function getPhysicalPage(string $logicalPage): int
    {
        if (
            !empty($this->lastSearchedPhysicalPage['logicalPage'])
            && $this->lastSearchedPhysicalPage['logicalPage'] == $logicalPage
        ) {
            return $this->lastSearchedPhysicalPage['physicalPage'];
        } else {
            $physicalPage = 0;
            foreach ($this->physicalStructureInfo as $page) {
                if (str_contains($page['orderlabel'], $logicalPage)) {
                    $this->lastSearchedPhysicalPage['logicalPage'] = $logicalPage;
                    $this->lastSearchedPhysicalPage['physicalPage'] = $physicalPage;
                    return $physicalPage;
                }
                $physicalPage++;
            }
        }
        return 1;
    }

    /**
     * This determines a title for the given document
     *
     * @access public
     *
     * @static
     *
     * @param int $uid The UID of the document
     * @param bool $recursive Search superior documents for a title, too?
     *
     * @return string The title of the document itself or a parent document
     */
    public static function getTitle(int $uid, bool $recursive = false): string
    {
        $title = '';
        // Sanitize input.
        $uid = max($uid, 0);
        if ($uid) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $result = $queryBuilder
                ->select(
                    'title',
                    'partof'
                )
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('uid', $uid),
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->setMaxResults(1)
                ->execute();

            $resArray = $result->fetchAssociative();
            if ($resArray) {
                // Get title information.
                $title = $resArray['title'];
                $partof = $resArray['partof'];
                // Search parent documents recursively for a title?
                if (
                    $recursive
                    && empty($title)
                    && (int) $partof
                    && $partof != $uid
                ) {
                    $title = self::getTitle($partof, true);
                }
            } else {
                Helper::warning('No document with UID ' . $uid . ' found or document not accessible');
            }
        } else {
            Helper::error('Invalid UID ' . $uid . ' for document');
        }
        return $title;
    }

    /**
     * This extracts all the metadata for the toplevel logical structure node / resource
     *
     * @access public
     *
     * @return array The logical structure node's / resource's parsed metadata array
     */
    public function getToplevelMetadata(): array
    {
        $toplevelMetadata = $this->getMetadata($this->getToplevelId());
        // Add information from METS structural map to toplevel metadata array.
        if ($this instanceof MetsDocument) {
            $this->addMetadataFromMets($toplevelMetadata, $this->getToplevelId());
        }
        // Set record identifier for METS file / IIIF manifest if not present.
        if (array_key_exists('record_id', $toplevelMetadata)) {
            if (
                !empty($this->recordId)
                && !in_array($this->recordId, $toplevelMetadata['record_id'])
            ) {
                array_unshift($toplevelMetadata['record_id'], $this->recordId);
            }
        }
        return $toplevelMetadata;
    }

    /**
     * Traverse a logical (sub-) structure tree to find the structure with the requested logical id and return its depth.
     *
     * @access protected
     *
     * @param array $structure logical structure array
     * @param int $depth current tree depth
     * @param string $logId ID of the logical structure whose depth is requested
     *
     * @return int|bool false if structure with $logId is not a child of this substructure,
     * or the actual depth.
     */
    protected function getTreeDepth(array $structure, int $depth, string $logId)
    {
        foreach ($structure as $element) {
            if ($element['id'] == $logId) {
                return $depth;
            } elseif (array_key_exists('children', $element)) {
                $foundInChildren = $this->getTreeDepth($element['children'], $depth + 1, $logId);
                if ($foundInChildren !== false) {
                    return $foundInChildren;
                }
            }
        }
        return false;
    }

    /**
     * Get the tree depth of a logical structure element within the table of content
     *
     * @access public
     *
     * @param string $logId The id of the logical structure element whose depth is requested
     *
     * @return int|bool tree depth as integer or false if no element with $logId exists within the TOC.
     */
    public function getStructureDepth(string $logId)
    {
        return $this->getTreeDepth($this->magicGetTableOfContents(), 1, $logId);
    }

    /**
     * Register all available data formats
     *
     * @access protected
     *
     * @return void
     */
    protected function loadFormats(): void
    {
        if (!$this->formatsLoaded && $this->configPid > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_formats');

            // Get available data formats from database.
            $result = $queryBuilder
                ->select(
                    'type',
                    'root',
                    'namespace',
                    'class'
                )
                ->from('tx_dlf_formats')
                ->where(
                    $queryBuilder->expr()->eq('pid', $this->configPid)
                )
                ->execute();

            while ($resArray = $result->fetchAssociative()) {
                // Update format registry.
                $this->formats[$resArray['type']] = [
                    'rootElement' => $resArray['root'],
                    'namespaceURI' => $resArray['namespace'],
                    'class' => $resArray['class']
                ];
            }
            $this->formatsLoaded = true;
        }
    }

    /**
     * Load IIIF resource from resource.
     *
     * @access protected
     *
     * @static
     *
     * @param string|array $resource IIIF resource. Can be an IRI, the JSON document as string
     * or a dictionary in form of a PHP associative array
     *
     * @return NULL|\Ubl\Iiif\Presentation\Common\Model\AbstractIiifEntity An instance of the IIIF resource
     */
    protected static function loadIiifResource($resource): mixed
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey, 'iiif');
        IiifHelper::setUrlReader(IiifUrlReader::getInstance());
        IiifHelper::setMaxThumbnailHeight($extConf['thumbnailHeight']);
        IiifHelper::setMaxThumbnailWidth($extConf['thumbnailWidth']);
        return IiifHelper::loadIiifResource($resource);
    }

    /**
     * Register all available namespaces for a \SimpleXMLElement object
     *
     * @access public
     *
     * @param \SimpleXMLElement|\DOMXPath &$obj \SimpleXMLElement or \DOMXPath object
     *
     * @return void
     */
    public function registerNamespaces(&$obj): void
    {
        // TODO Check usage. XML specific method does not seem to be used anywhere outside this class within the project, but it is public and may be used by extensions.
        $this->loadFormats();
        // Do we have a \SimpleXMLElement or \DOMXPath object?
        if ($obj instanceof \SimpleXMLElement) {
            $method = 'registerXPathNamespace';
        } elseif ($obj instanceof \DOMXPath) {
            $method = 'registerNamespace';
        } else {
            $this->logger->error('Given object is neither a SimpleXMLElement nor a DOMXPath instance');
            return;
        }
        // Register metadata format's namespaces.
        foreach ($this->formats as $enc => $conf) {
            $obj->$method(strtolower($enc), $conf['namespaceURI']);
        }
    }

    /**
     * Initialize metadata array with empty values.
     *
     * @access protected
     *
     * @param string $format of the document eg. METS
     * @param bool $isAdministrative If true, the metadata is for administrative purposes and needs to have record_id
     *
     * @return array
     */
    protected function initializeMetadata(string $format, bool $isAdministrative = false): array
    {
        return [
            'title' => [],
            'title_sorting' => [],
            'description' => [],
            'author' => [],
            'holder' => [],
            'place' => [],
            'year' => [],
            'prod_id' => [],
            'record_id' => [],
            'opac_id' => [],
            'union_id' => [],
            'urn' => [],
            'purl' => [],
            'type' => [],
            'volume' => [],
            'volume_sorting' => [],
            'date' => [],
            'license' => [],
            'terms' => [],
            'restrictions' => [],
            'out_of_print' => [],
            'rights_info' => [],
            'collection' => [],
            'owner' => [],
            'mets_label' => [],
            'mets_orderlabel' => [],
            'document_format' => [$format],
            'is_administrative' => [$isAdministrative]
        ];
    }

    /**
     * This returns $this->configPid via __get()
     *
     * @access protected
     *
     * @return int The PID of the metadata definitions
     */
    protected function magicGetConfigPid(): int
    {
        return $this->configPid;
    }

    /**
     * This returns $this->hasFulltext via __get()
     *
     * @access protected
     *
     * @return bool Are there any fulltext files available?
     */
    protected function magicGetHasFulltext(): bool
    {
        $this->ensureHasFulltextIsSet();
        return $this->hasFulltext;
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     * It builds an array of the document's metadata
     *
     * @access protected
     *
     * @return array Array of metadata with their corresponding logical structure node ID as key
     */
    protected function magicGetMetadataArray(): array
    {
        if ($this->configPid == 0) {
            $this->logger->error('Invalid PID for metadata definitions');
            return [];
        }
        if (
            !$this->metadataArrayLoaded
            || $this->metadataArray[0] != $this->configPid
        ) {
            $this->prepareMetadataArray();
            $this->metadataArray[0] = $this->configPid;
            $this->metadataArrayLoaded = true;
        }
        return $this->metadataArray;
    }

    /**
     * This returns $this->numPages via __get()
     *
     * @access protected
     *
     * @return int The total number of pages and/or tracks
     */
    protected function magicGetNumPages(): int
    {
        $this->magicGetPhysicalStructure();
        return $this->numPages;
    }

    /**
     * This returns $this->parentId via __get()
     *
     * @access protected
     *
     * @return int The UID of the parent document or zero if not applicable
     */
    protected function magicGetParentId(): int
    {
        return $this->parentId;
    }

    /**
     * This gives an array of the document's physical structure metadata
     *
     * @access protected
     *
     * @return array Array of elements' type, label and file representations ordered by "@ID" attribute / Canvas order
     */
    protected function magicGetPhysicalStructureInfo(): array
    {
        // Is there no physical structure array yet?
        if (!$this->physicalStructureLoaded) {
            // Build physical structure array.
            $this->magicGetPhysicalStructure();
        }
        return $this->physicalStructureInfo;
    }

    /**
     * This returns $this->ready via __get()
     *
     * @access protected
     *
     * @return bool Is the document instantiated successfully?
     */
    protected function magicGetReady(): bool
    {
        return $this->ready;
    }

    /**
     * This returns $this->recordId via __get()
     *
     * @access protected
     *
     * @return string The METS file's / IIIF manifest's record identifier
     */
    protected function magicGetRecordId(): string
    {
        return $this->recordId;
    }

    /**
     * This returns $this->rootId via __get()
     *
     * @access protected
     *
     * @return int The UID of the root document or zero if not applicable
     */
    protected function magicGetRootId(): int
    {
        if (!$this->rootIdLoaded) {
            if ($this->parentId) {
                $parent = self::getInstance((string) $this->parentId, ['storagePid' => $this->configPid]);
                $this->rootId = $parent->rootId;
            }
            $this->rootIdLoaded = true;
        }
        return $this->rootId;
    }

    /**
     * This builds an array of the document's logical structure
     *
     * @access protected
     *
     * @return array Array of structure nodes' id, label, type and physical page indexes/mptr / Canvas link with original hierarchy preserved
     */
    protected function magicGetTableOfContents(): array
    {
        // Is there no logical structure array yet?
        if (!$this->tableOfContentsLoaded) {
            // Get all logical structures.
            $this->getLogicalStructure('', true);
            $this->tableOfContentsLoaded = true;
        }
        return $this->tableOfContents;
    }

    /**
     * This sets $this->configPid via __set()
     *
     * @access protected
     *
     * @param int $value The new PID for the metadata definitions
     *
     * @return void
     */
    protected function _setConfigPid(int $value): void
    {
        if ($this->configPid == 0) {
            $this->configPid = max($value, 0);
        }
    }

    /**
     * This is a singleton class, thus the constructor should be private/protected
     * (Get an instance of this class by calling AbstractDocument::getInstance())
     *
     * @access protected
     *
     * @param string $location The location URL of the XML file to parse
     * @param \SimpleXMLElement|IiifResourceInterface $preloadedDocument Either null or the \SimpleXMLElement
     * or IiifResourceInterface that has been loaded to determine the basic document format.
     *
     * @return void
     */
    protected function __construct(string $location, $preloadedDocument, array $settings = [])
    {
        // Note: Any change here might require an update in function __sleep
        // of class MetsDocument and class IiifManifest, too.
        $storagePid = array_key_exists('storagePid', $settings) ? max((int) $settings['storagePid'], 0) : 0;
        $this->configPid = $storagePid;
        $this->useGroupsConfiguration = UseGroupsConfiguration::getInstance();
        $this->setPreloadedDocument($preloadedDocument);
        $this->init($location, $settings);
        $this->establishRecordId($storagePid);
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var Name of variable to get
     *
     * @return mixed Value of $this->$var
     */
    public function __get(string $var)
    {
        $method = 'magicGet' . ucfirst($var);
        if (
            !property_exists($this, $var)
            || !method_exists($this, $method)
        ) {
            $this->logger->warning('There is no getter function for property "' . $var . '"');
            return null;
        } else {
            return $this->$method();
        }
    }

    /**
     * This magic method is called each time an invisible property is checked for isset() or empty()
     *
     * @access public
     *
     * @param string $var Name of variable to check
     *
     * @return bool true if variable is set and not empty, false otherwise
     */
    public function __isset(string $var): bool
    {
        return !empty($this->__get($var));
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var Name of variable to set
     * @param mixed $value New value of variable
     *
     * @return void
     */
    public function __set(string $var, $value): void
    {
        $method = '_set' . ucfirst($var);
        if (
            !property_exists($this, $var)
            || !method_exists($this, $method)
        ) {
            $this->logger->warning('There is no setter function for property "' . $var . '"');
        } else {
            $this->$method($value);
        }
    }

    /**
     * Get IDs of logical structures that a page belongs to, indexed by depth.
     *
     * @param int $pageNo
     * @return array
     */
    public function getLogicalSectionsOnPage($pageNo)
    {
        $this->magicGetSmLinks();
        $this->magicGetPhysicalStructure();

        $ids = [];
        if (!empty($this->physicalStructure[$pageNo]) && !empty($this->smLinks['p2l'][$this->physicalStructure[$pageNo]])) {
            foreach ($this->smLinks['p2l'][$this->physicalStructure[$pageNo]] as $logId) {
                $depth = $this->getStructureDepth($logId);
                $ids[$depth][] = $logId;
            }
        }
        ksort($ids);
        reset($ids);
        return $ids;
    }

    /**
     * Get URL of download file of specified page, or the empty string if there is no such link.
     *
     * @param int $pageNumber
     * @return string
     */
    public function getPageLink($pageNumber)
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey);
        $fileGrpsDownload = GeneralUtility::trimExplode(',', $extConf['files']['fileGrpDownload']);
        // Get image link.
        foreach ($fileGrpsDownload as $fileGrpDownload) {
            if (!empty($this->physicalStructureInfo[$this->physicalStructure[$pageNumber]]['files'][$fileGrpDownload])) {
                return $this->getFileLocation($this->physicalStructureInfo[$this->physicalStructure[$pageNumber]]['files'][$fileGrpDownload]);
            }
        }
        return '';
    }

    public function toArray($uriBuilder, array $config = [])
    {
        $proxyFileGroups = $config['proxyFileGroups'] ?? [];
        $forceAbsoluteUrl = $config['forceAbsoluteUrl'] ?? false;

        $this->magicGetSmLinks();
        $this->magicGetPhysicalStructure();

        $proxyFileGroups = $config['proxyFileGroups'] ?? [];
        $forceAbsoluteUrl = $config['forceAbsoluteUrl'] ?? false;
        $minPage = $config['minPage'] ?? 1;
        $maxPage = $config['maxPage'] ?? $this->numPages;

        $result = [
            'pages' => [],
            'query' => [
                'minPage' => $minPage
            ]
        ];

        $allFiles = $this->getAllFiles();

        for ($page = $minPage; $page <= $maxPage; $page++) {
            $pageEntry = [
                'logSections' => array_merge(...$this->getLogicalSectionsOnPage($page)),
                'files' => [],
            ];

            foreach ($this->physicalStructureInfo[$this->physicalStructure[$page]]['files'] as $fileGrp => $fileId) {
                if ($allFiles === null) {
                    $file = [
                        'url' => $this->getFileLocation($fileId),
                        'mimetype' => $this->getFileMimeType($fileId),
                    ];
                } else {
                    $file = $allFiles[$fileId] ?? null;
                    if ($file === null) {
                        continue;
                    }
                }

                $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey);
                $nonProxyMimeTypes = GeneralUtility::trimExplode(',', $extConf['nonProxyMimeTypes']);

                // Only deliver static images via the internal PageViewProxy.
                // (For IIP and IIIF, the viewer needs to build and access a separate metadata URL, see `getMetadataURL`.)
                if (in_array($fileGrp, $proxyFileGroups) && !in_array($file['mimetype'], $nonProxyMimeTypes)) {
                    // Configure @action URL for form.
                    $file['url'] = $uriBuilder
                        ->reset()
                        ->setTargetPageUid($GLOBALS['TSFE']->id)
                        ->setCreateAbsoluteUri($forceAbsoluteUrl)
                        ->setArguments([
                            'eID' => 'tx_dlf_pageview_proxy',
                            'url' => $file['url'],
                            'uHash' => GeneralUtility::hmac($file['url'], 'PageViewProxy')
                        ])
                        ->build();
                }

                $pageEntry['files'][$fileGrp] = $file;
            }

            $result['pages'][] = $pageEntry;
        }

        return $result;
    }
}
