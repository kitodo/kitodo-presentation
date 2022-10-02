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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use Ubl\Iiif\Presentation\Common\Model\Resources\IiifResourceInterface;
use Ubl\Iiif\Tools\IiifHelper;

/**
 * Document class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @property int $cPid This holds the PID for the configuration
 * @property-read bool $hasFulltext Are there any fulltext files available?
 * @property-read string $location This holds the documents location
 * @property-read array $metadataArray This holds the documents' parsed metadata array
 * @property-read int $numPages The holds the total number of pages
 * @property-read int $parentId This holds the UID of the parent document or zero if not multi-volumed
 * @property-read array $physicalStructure This holds the physical structure
 * @property-read array $physicalStructureInfo This holds the physical structure metadata
 * @property-read int $pid This holds the PID of the document or zero if not in database
 * @property-read bool $ready Is the document instantiated successfully?
 * @property-read string $recordId The METS file's / IIIF manifest's record identifier
 * @property-read int $rootId This holds the UID of the root document or zero if not multi-volumed
 * @property-read array $smLinks This holds the smLinks between logical and physical structMap
 * @property-read array $tableOfContents This holds the logical structure
 * @property-read string $thumbnail This holds the document's thumbnail location
 * @property-read string $toplevelId This holds the toplevel structure's "@ID" (METS) or the manifest's "@id" (IIIF)
 * @abstract
 */
abstract class Doc
{
    /**
     * This holds the logger
     *
     * @var LogManager
     * @access protected
     */
    protected $logger;

    /**
     * This holds the PID for the configuration
     *
     * @var int
     * @access protected
     */
    protected $cPid = 0;

    /**
     * The extension key
     *
     * @var string
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * This holds the configuration for all supported metadata encodings
     * @see loadFormats()
     *
     * @var array
     * @access protected
     */
    protected $formats = [
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
     * Are the available metadata formats loaded?
     * @see $formats
     *
     * @var bool
     * @access protected
     */
    protected $formatsLoaded = false;

    /**
     * Are there any fulltext files available? This also includes IIIF text annotations
     * with motivation 'painting' if Kitodo.Presentation is configured to store text
     * annotations as fulltext.
     *
     * @var bool
     * @access protected
     */
    protected $hasFulltext = false;

    /**
     * Last searched logical and physical page
     *
     * @var array
     * @access protected
     */
    protected $lastSearchedPhysicalPage = ['logicalPage' => null, 'physicalPage' => null];

    /**
     * This holds the logical units
     *
     * @var array
     * @access protected
     */
    protected $logicalUnits = [];

    /**
     * This holds the documents' parsed metadata array with their corresponding
     * structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
     *
     * @var array
     * @access protected
     */
    protected $metadataArray = [];

    /**
     * Is the metadata array loaded?
     * @see $metadataArray
     *
     * @var bool
     * @access protected
     */
    protected $metadataArrayLoaded = false;

    /**
     * The holds the total number of pages
     *
     * @var int
     * @access protected
     */
    protected $numPages = 0;

    /**
     * This holds the UID of the parent document or zero if not multi-volumed
     *
     * @var int
     * @access protected
     */
    protected $parentId = 0;

    /**
     * This holds the physical structure
     *
     * @var array
     * @access protected
     */
    protected $physicalStructure = [];

    /**
     * This holds the physical structure metadata
     *
     * @var array
     * @access protected
     */
    protected $physicalStructureInfo = [];

    /**
     * Is the physical structure loaded?
     * @see $physicalStructure
     *
     * @var bool
     * @access protected
     */
    protected $physicalStructureLoaded = false;

    /**
     * This holds the PID of the document or zero if not in database
     *
     * @var int
     * @access protected
     */
    protected $pid = 0;

    /**
     * This holds the documents' raw text pages with their corresponding
     * structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
     *
     * @var array
     * @access protected
     */
    protected $rawTextArray = [];

    /**
     * Is the document instantiated successfully?
     *
     * @var bool
     * @access protected
     */
    protected $ready = false;

    /**
     * The METS file's / IIIF manifest's record identifier
     *
     * @var string
     * @access protected
     */
    protected $recordId;

    /**
     * This holds the singleton object of the document
     *
     * @var array (\Kitodo\Dlf\Common\Doc)
     * @static
     * @access protected
     */
    protected static $registry = [];

    /**
     * This holds the UID of the root document or zero if not multi-volumed
     *
     * @var int
     * @access protected
     */
    protected $rootId = 0;

    /**
     * Is the root id loaded?
     * @see $rootId
     *
     * @var bool
     * @access protected
     */
    protected $rootIdLoaded = false;

    /**
     * This holds the smLinks between logical and physical structMap
     *
     * @var array
     * @access protected
     */
    protected $smLinks = ['l2p' => [], 'p2l' => []];

    /**
     * Are the smLinks loaded?
     * @see $smLinks
     *
     * @var bool
     * @access protected
     */
    protected $smLinksLoaded = false;

    /**
     * This holds the logical structure
     *
     * @var array
     * @access protected
     */
    protected $tableOfContents = [];

    /**
     * Is the table of contents loaded?
     * @see $tableOfContents
     *
     * @var bool
     * @access protected
     */
    protected $tableOfContentsLoaded = false;

    /**
     * This holds the document's thumbnail location
     *
     * @var string
     * @access protected
     */
    protected $thumbnail = '';

    /**
     * Is the document's thumbnail location loaded?
     * @see $thumbnail
     *
     * @var bool
     * @access protected
     */
    protected $thumbnailLoaded = false;

    /**
     * This holds the toplevel structure's "@ID" (METS) or the manifest's "@id" (IIIF)
     *
     * @var string
     * @access protected
     */
    protected $toplevelId = '';

    /**
     * This holds the whole XML file as \SimpleXMLElement object
     *
     * @var \SimpleXMLElement
     * @access protected
     */
    protected $xml;

    /**
     * This clears the static registry to prevent memory exhaustion
     *
     * @access public
     *
     * @static
     *
     * @return void
     */
    public static function clearRegistry()
    {
        // Reset registry array.
        self::$registry = [];
    }

    /**
     * This ensures that the recordId, if existent, is retrieved from the document
     *
     * @access protected
     *
     * @abstract
     *
     * @param int $pid: ID of the configuration page with the recordId config
     *
     */
    protected abstract function establishRecordId($pid);

    /**
     * Source document PHP object which is represented by a Document instance
     *
     * @access protected
     *
     * @abstract
     *
     * @return \SimpleXMLElement|IiifResourceInterface An PHP object representation of
     * the current document. SimpleXMLElement for METS, IiifResourceInterface for IIIF
     */
    protected abstract function getDocument();

    /**
     * This gets the location of a downloadable file for a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The "@ID" attribute of the file node (METS) or the "@id" property of the IIIF resource
     *
     * @return string    The file's location as URL
     */
    public abstract function getDownloadLocation($id);

    /**
     * This gets the location of a file representing a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The "@ID" attribute of the file node (METS) or the "@id" property of the IIIF resource
     *
     * @return string The file's location as URL
     */
    public abstract function getFileLocation($id);

    /**
     * This gets the MIME type of a file representing a physical page or track
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The "@ID" attribute of the file node
     *
     * @return string The file's MIME type
     */
    public abstract function getFileMimeType($id);

    /**
     * This is a singleton class, thus an instance must be created by this method
     *
     * @access public
     *
     * @static
     *
     * @param string $location: The URL of XML file or the IRI of the IIIF resource
     * @param array $settings
     * @param bool $forceReload: Force reloading the document instead of returning the cached instance
     *
     * @return \Kitodo\Dlf\Common\Doc|null Instance of this class, either MetsDocument or IiifManifest
     */
    public static function &getInstance($location, $settings = [], $forceReload = false)
    {
        // Create new instance depending on format (METS or IIIF) ...
        $documentFormat = null;
        $xml = null;
        $iiif = null;

        if ($instance = self::getDocCache($location)) {
            return $instance;
        } else {
            $instance = null;
        }

        // Try to load a file from the url
        if (GeneralUtility::isValidUrl($location)) {
            // Load extension configuration
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey);

            $content = Helper::getUrl($location);
            if ($content !== false) {
                $xml = Helper::getXmlFileAsString($content);
                if ($xml !== false) {
                    /* @var $xml \SimpleXMLElement */
                    $xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');
                    $xpathResult = $xml->xpath('//mets:mets');
                    $documentFormat = !empty($xpathResult) ? 'METS' : null;
                } else {
                    // Try to load file as IIIF resource instead.
                    $contentAsJsonArray = json_decode($content, true);
                    if ($contentAsJsonArray !== null) {
                        IiifHelper::setUrlReader(IiifUrlReader::getInstance());
                        IiifHelper::setMaxThumbnailHeight($extConf['iiifThumbnailHeight']);
                        IiifHelper::setMaxThumbnailWidth($extConf['iiifThumbnailWidth']);
                        $iiif = IiifHelper::loadIiifResource($contentAsJsonArray);
                        if ($iiif instanceof IiifResourceInterface) {
                            $documentFormat = 'IIIF';
                        }
                    }
                }
            }
        }

        // Sanitize input.
        $pid = max(intval($settings['storagePid']), 0);
        if ($documentFormat == 'METS') {
            $instance = new MetsDocument($location, $pid, $xml);
        } elseif ($documentFormat == 'IIIF') {
            $instance = new IiifManifest($location, $pid, $iiif);
        }

        if ($instance) {
            self::setDocCache($location, $instance);
        }

        return $instance;
    }

    /**
     * This gets details about a logical structure element
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The "@ID" attribute of the logical structure node (METS) or
     * the "@id" property of the Manifest / Range (IIIF)
     * @param bool $recursive: Whether to include the child elements / resources
     *
     * @return array Array of the element's id, label, type and physical page indexes/mptr link
     */
    public abstract function getLogicalStructure($id, $recursive = false);

    /**
     * This extracts all the metadata for a logical structure node
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The "@ID" attribute of the logical structure node (METS) or the "@id" property
     * of the Manifest / Range (IIIF)
     * @param int $cPid: The PID for the metadata definitions
     *                       (defaults to $this->cPid or $this->pid)
     *
     * @return array The logical structure node's / the IIIF resource's parsed metadata array
     */
    public abstract function getMetadata($id, $cPid = 0);

    /**
     * This returns the first corresponding physical page number of a given logical page label
     *
     * @access public
     *
     * @param string $logicalPage: The label (or a part of the label) of the logical page
     *
     * @return int The physical page number
     */
    public function getPhysicalPage($logicalPage)
    {
        if (
            !empty($this->lastSearchedPhysicalPage['logicalPage'])
            && $this->lastSearchedPhysicalPage['logicalPage'] == $logicalPage
        ) {
            return $this->lastSearchedPhysicalPage['physicalPage'];
        } else {
            $physicalPage = 0;
            foreach ($this->physicalStructureInfo as $page) {
                if (strpos($page['orderlabel'], $logicalPage) !== false) {
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
     * This extracts the OCR full text for a physical structure node / IIIF Manifest / Canvas. Text might be
     * given as ALTO for METS or as annotations or ALTO for IIIF resources.
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The "@ID" attribute of the physical structure node (METS) or the "@id" property
     * of the Manifest / Range (IIIF)
     *
     * @return string The OCR full text
     */
    public abstract function getFullText($id);

    /**
     * This extracts the OCR full text for a physical structure node / IIIF Manifest / Canvas from an
     * XML full text representation (currently only ALTO). For IIIF manifests, ALTO documents have
     * to be given in the Canvas' / Manifest's "seeAlso" property.
     *
     * @param string $id: The "@ID" attribute of the physical structure node (METS) or the "@id" property
     * of the Manifest / Range (IIIF)
     *
     * @return string The OCR full text
     */
    protected function getFullTextFromXml($id)
    {
        $fullText = '';
        // Load available text formats, ...
        $this->loadFormats();
        // ... physical structure ...
        $this->_getPhysicalStructure();
        // ... and extension configuration.
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey);
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $extConf['fileGrpFulltext']);
        if (!empty($this->physicalStructureInfo[$id])) {
            while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
                if (!empty($this->physicalStructureInfo[$id]['files'][$fileGrpFulltext])) {
                    // Get full text file.
                    $fileContent = GeneralUtility::getUrl($this->getFileLocation($this->physicalStructureInfo[$id]['files'][$fileGrpFulltext]));
                    if ($fileContent !== false) {
                        $textFormat = $this->getTextFormat($fileContent);
                    } else {
                        $this->logger->warning('Couldn\'t load full text file for structure node @ID "' . $id . '"');
                        return $fullText;
                    }
                    break;
                }
            }
        } else {
            $this->logger->warning('Invalid structure node @ID "' . $id . '"');
            return $fullText;
        }
        // Is this text format supported?
        // This part actually differs from previous version of indexed OCR
        if (!empty($fileContent) && !empty($this->formats[$textFormat])) {
            $textMiniOcr = '';
            if (!empty($this->formats[$textFormat]['class'])) {
                $class = $this->formats[$textFormat]['class'];
                // Get the raw text from class.
                if (
                    class_exists($class)
                    && ($obj = GeneralUtility::makeInstance($class)) instanceof FulltextInterface
                ) {
                    // Load XML from file.
                    $ocrTextXml = Helper::getXmlFileAsString($fileContent);
                    $textMiniOcr = $obj->getTextAsMiniOcr($ocrTextXml);
                    $this->rawTextArray[$id] = $textMiniOcr;
                } else {
                    $this->logger->warning('Invalid class/method "' . $class . '->getRawText()" for text format "' . $textFormat . '"');
                }
            }
            $fullText = $textMiniOcr;
        } else {
            $this->logger->warning('Unsupported text format "' . $textFormat . '" in physical node with @ID "' . $id . '"');
        }
        return $fullText;
    }

    /**
     * Get format of the OCR full text
     *
     * @access private
     *
     * @param string $fileContent: content of the XML file
     *
     * @return string The format of the OCR full text
     */
    private function getTextFormat($fileContent)
    {
        $xml = Helper::getXmlFileAsString($fileContent);

        if ($xml !== false) {
            // Get the root element's name as text format.
            return strtoupper($xml->getName());
        } else {
            return '';
        }
    }

    /**
     * This determines a title for the given document
     *
     * @access public
     *
     * @static
     *
     * @param int $uid: The UID of the document
     * @param bool $recursive: Search superior documents for a title, too?
     *
     * @return string The title of the document itself or a parent document
     */
    public static function getTitle($uid, $recursive = false)
    {
        $title = '';
        // Sanitize input.
        $uid = max(intval($uid), 0);
        if ($uid) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $result = $queryBuilder
                ->select(
                    'tx_dlf_documents.title',
                    'tx_dlf_documents.partof'
                )
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.uid', $uid),
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                // Get title information.
                $title = $resArray['title'];
                $partof = $resArray['partof'];
                // Search parent documents recursively for a title?
                if (
                    $recursive
                    && empty($title)
                    && intval($partof)
                    && $partof != $uid
                ) {
                    $title = self::getTitle($partof, true);
                }
            } else {
                Helper::log('No document with UID ' . $uid . ' found or document not accessible', LOG_SEVERITY_WARNING);
            }
        } else {
            Helper::log('Invalid UID ' . $uid . ' for document', LOG_SEVERITY_ERROR);
        }
        return $title;
    }

    /**
     * This extracts all the metadata for the toplevel logical structure node / resource
     *
     * @access public
     *
     * @param int $cPid: The PID for the metadata definitions
     *
     * @return array The logical structure node's / resource's parsed metadata array
     */
    public function getTitledata($cPid = 0)
    {
        $titledata = $this->getMetadata($this->_getToplevelId(), $cPid);
        // Add information from METS structural map to titledata array.
        if ($this instanceof MetsDocument) {
            $this->addMetadataFromMets($titledata, $this->_getToplevelId());
        }
        // Set record identifier for METS file / IIIF manifest if not present.
        if (
            is_array($titledata)
            && array_key_exists('record_id', $titledata)
        ) {
            if (
                !empty($this->recordId)
                && !in_array($this->recordId, $titledata['record_id'])
            ) {
                array_unshift($titledata['record_id'], $this->recordId);
            }
        }
        return $titledata;
    }

    /**
     * Traverse a logical (sub-) structure tree to find the structure with the requested logical id and return its depth.
     *
     * @access protected
     *
     * @param array $structure: logical structure array
     * @param int $depth: current tree depth
     * @param string $logId: ID of the logical structure whose depth is requested
     *
     * @return int|bool: false if structure with $logId is not a child of this substructure,
     * or the actual depth.
     */
    protected function getTreeDepth($structure, $depth, $logId)
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
     * @param string $logId: The id of the logical structure element whose depth is requested
     * @return int|bool tree depth as integer or false if no element with $logId exists within the TOC.
     */
    public function getStructureDepth($logId)
    {
        return $this->getTreeDepth($this->_getTableOfContents(), 1, $logId);
    }

    /**
     * This sets some basic class properties
     *
     * @access protected
     *
     * @abstract
     *
     * @param string $location:The location URL of the XML file to parse
     *
     * @return void
     */
    protected abstract function init($location);

    /**
     * Reuse any document object that might have been already loaded to determine whether document is METS or IIIF
     *
     * @access protected
     *
     * @abstract
     *
     * @param \SimpleXMLElement|IiifResourceInterface $preloadedDocument: any instance that has already been loaded
     *
     * @return bool true if $preloadedDocument can actually be reused, false if it has to be loaded again
     */
    protected abstract function setPreloadedDocument($preloadedDocument);

    /**
     * METS/IIIF specific part of loading a location
     *
     * @access protected
     *
     * @abstract
     *
     * @param string $location: The URL of the file to load
     *
     * @return bool true on success or false on failure
     */
    protected abstract function loadLocation($location);

    /**
     * Load XML file / IIIF resource from URL
     *
     * @access protected
     *
     * @param string $location: The URL of the file to load
     *
     * @return bool true on success or false on failure
     */
    protected function load($location)
    {
        // Load XML / JSON-LD file.
        if (GeneralUtility::isValidUrl($location)) {
            // the actual loading is format specific
            return $this->loadLocation($location);
        } else {
            $this->logger->error('Invalid file location "' . $location . '" for document loading');
        }
        return false;
    }

    /**
     * Analyze the document if it contains any fulltext that needs to be indexed.
     *
     * @access protected
     *
     * @abstract
     */
    protected abstract function ensureHasFulltextIsSet();

    /**
     * Register all available data formats
     *
     * @access protected
     *
     * @return void
     */
    protected function loadFormats()
    {
        if (!$this->formatsLoaded) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_formats');

            // Get available data formats from database.
            $result = $queryBuilder
                ->select(
                    'tx_dlf_formats.type AS type',
                    'tx_dlf_formats.root AS root',
                    'tx_dlf_formats.namespace AS namespace',
                    'tx_dlf_formats.class AS class'
                )
                ->from('tx_dlf_formats')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_formats.pid', 0)
                )
                ->execute();

            while ($resArray = $result->fetch()) {
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
     * Register all available namespaces for a \SimpleXMLElement object
     *
     * @access public
     *
     * @param \SimpleXMLElement|\DOMXPath &$obj: \SimpleXMLElement or \DOMXPath object
     *
     * @return void
     */
    public function registerNamespaces(&$obj)
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
     * This returns $this->cPid via __get()
     *
     * @access protected
     *
     * @return int The PID of the metadata definitions
     */
    protected function _getCPid()
    {
        return $this->cPid;
    }

    /**
     * This returns $this->hasFulltext via __get()
     *
     * @access protected
     *
     * @return bool Are there any fulltext files available?
     */
    protected function _getHasFulltext()
    {
        $this->ensureHasFulltextIsSet();
        return $this->hasFulltext;
    }

    /**
     * This returns $this->location via __get()
     *
     * @access protected
     *
     * @return string The location of the document
     */
    protected function _getLocation()
    {
        return $this->location;
    }

    /**
     * Format specific part of building the document's metadata array
     *
     * @access protected
     *
     * @abstract
     *
     * @param int $cPid
     */
    protected abstract function prepareMetadataArray($cPid);

    /**
     * This builds an array of the document's metadata
     *
     * @access protected
     *
     * @return array Array of metadata with their corresponding logical structure node ID as key
     */
    protected function _getMetadataArray()
    {
        // Set metadata definitions' PID.
        $cPid = ($this->cPid ? $this->cPid : $this->pid);
        if (!$cPid) {
            $this->logger->error('Invalid PID ' . $cPid . ' for metadata definitions');
            return [];
        }
        if (
            !$this->metadataArrayLoaded
            || $this->metadataArray[0] != $cPid
        ) {
            $this->prepareMetadataArray($cPid);
            $this->metadataArray[0] = $cPid;
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
    protected function _getNumPages()
    {
        $this->_getPhysicalStructure();
        return $this->numPages;
    }

    /**
     * This returns $this->parentId via __get()
     *
     * @access protected
     *
     * @return int The UID of the parent document or zero if not applicable
     */
    protected function _getParentId()
    {
        return $this->parentId;
    }

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
    protected abstract function _getPhysicalStructure();

    /**
     * This gives an array of the document's physical structure metadata
     *
     * @access protected
     *
     * @return array Array of elements' type, label and file representations ordered by "@ID" attribute / Canvas order
     */
    protected function _getPhysicalStructureInfo()
    {
        // Is there no physical structure array yet?
        if (!$this->physicalStructureLoaded) {
            // Build physical structure array.
            $this->_getPhysicalStructure();
        }
        return $this->physicalStructureInfo;
    }

    /**
     * This returns $this->pid via __get()
     *
     * @access protected
     *
     * @return int The PID of the document or zero if not in database
     */
    protected function _getPid()
    {
        return $this->pid;
    }

    /**
     * This returns $this->ready via __get()
     *
     * @access protected
     *
     * @return bool Is the document instantiated successfully?
     */
    protected function _getReady()
    {
        return $this->ready;
    }

    /**
     * This returns $this->recordId via __get()
     *
     * @access protected
     *
     * @return mixed The METS file's / IIIF manifest's record identifier
     */
    protected function _getRecordId()
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
    protected function _getRootId()
    {
        if (!$this->rootIdLoaded) {
            if ($this->parentId) {
                $parent = self::getInstance($this->parentId, ['storagePid' => $this->pid]);
                $this->rootId = $parent->rootId;
            }
            $this->rootIdLoaded = true;
        }
        return $this->rootId;
    }

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
    protected abstract function _getSmLinks();

    /**
     * This builds an array of the document's logical structure
     *
     * @access protected
     *
     * @return array Array of structure nodes' id, label, type and physical page indexes/mptr / Canvas link with original hierarchy preserved
     */
    protected function _getTableOfContents()
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
     * This returns the document's thumbnail location
     *
     * @access protected
     *
     * @abstract
     *
     * @param bool $forceReload: Force reloading the thumbnail instead of returning the cached value
     *
     * @return string The document's thumbnail location
     */
    protected abstract function _getThumbnail($forceReload = false);

    /**
     * This returns the ID of the toplevel logical structure node
     *
     * @access protected
     *
     * @abstract
     *
     * @return string The logical structure node's ID
     */
    protected abstract function _getToplevelId();

    /**
     * This returns $this->uid via __get()
     *
     * @access protected
     *
     * @return mixed The UID or the URL of the document
     */
    protected function _getUid()
    {
        return $this->uid;
    }

    /**
     * This sets $this->cPid via __set()
     *
     * @access protected
     *
     * @param int $value: The new PID for the metadata definitions
     *
     * @return void
     */
    protected function _setCPid($value)
    {
        $this->cPid = max(intval($value), 0);
    }

    /**
     * This is a singleton class, thus the constructor should be private/protected
     * (Get an instance of this class by calling \Kitodo\Dlf\Common\Doc::getInstance())
     *
     * @access protected
     *
     * @param string $location: The location URL of the XML file to parse
     * @param int $pid: If > 0, then only document with this PID gets loaded
     * @param \SimpleXMLElement|IiifResourceInterface $preloadedDocument: Either null or the \SimpleXMLElement
     * or IiifResourceInterface that has been loaded to determine the basic document format.
     *
     * @return void
     */
    protected function __construct($location, $pid, $preloadedDocument)
    {
        $this->pid = $pid;
        $this->setPreloadedDocument($preloadedDocument);
        $this->init($location);
        $this->establishRecordId($pid);
        return;
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var: Name of variable to get
     *
     * @return mixed Value of $this->$var
     */
    public function __get($var)
    {
        $method = '_get' . ucfirst($var);
        if (
            !property_exists($this, $var)
            || !method_exists($this, $method)
        ) {
            $this->logger->warning('There is no getter function for property "' . $var . '"');
            return;
        } else {
            return $this->$method();
        }
    }

    /**
     * This magic method is called each time an invisible property is checked for isset() or empty()
     *
     * @access public
     *
     * @param string $var: Name of variable to check
     *
     * @return bool true if variable is set and not empty, false otherwise
     */
    public function __isset($var)
    {
        return !empty($this->__get($var));
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var: Name of variable to set
     * @param mixed $value: New value of variable
     *
     * @return void
     */
    public function __set($var, $value)
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
     * get Cache Hit for $doc
     *
     * @param string $location
     * @return Doc|false
     */
    private static function getDocCache(string $location)
    {
        $cacheIdentifier = md5($location);
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_dlf_doc');
        $cacheHit = $cache->get($cacheIdentifier);

        return $cacheHit;
    }

    /**
     * set Cache for $doc
     *
     * @param string $location
     * @param Doc $doc
     * @return void
     */
    private static function setDocCache(string $location, Doc $doc)
    {
        $cacheIdentifier = md5($location);
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_dlf_doc');

        // Save value in cache
        $cache->set($cacheIdentifier, $doc);
    }

}
