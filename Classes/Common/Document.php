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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
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
 * @property-read string $toplevelId This holds the toplevel structure's @ID (METS) or the manifest's @id (IIIF)
 * @property-read mixed $uid This holds the UID or the URL of the document
 * @abstract
 */
abstract class Document
{
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
     * This holds the documents location
     *
     * @var string
     * @access protected
     */
    protected $location = '';

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
     * @var array (\Kitodo\Dlf\Common\Document)
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
     * This holds the toplevel structure's @ID (METS) or the manifest's @id (IIIF)
     *
     * @var string
     * @access protected
     */
    protected $toplevelId = '';

    /**
     * This holds the UID or the URL of the document
     *
     * @var mixed
     * @access protected
     */
    protected $uid = 0;

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
     * @param string $id: The @ID attribute of the file node (METS) or the @id property of the IIIF resource
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
     * @param string $id: The @ID attribute of the file node (METS) or the @id property of the IIIF resource
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
     * @param string $id: The @ID attribute of the file node
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
     * @param mixed $uid: The unique identifier of the document to parse, the URL of XML file or the IRI of the IIIF resource
     * @param int $pid: If > 0, then only document with this PID gets loaded
     * @param bool $forceReload: Force reloading the document instead of returning the cached instance
     *
     * @return \Kitodo\Dlf\Common\Document Instance of this class, either MetsDocument or IiifManifest
     */
    public static function &getInstance($uid, $pid = 0, $forceReload = false)
    {
        // Sanitize input.
        $pid = max(intval($pid), 0);
        if (!$forceReload) {
            $regObj = Helper::digest($uid);
            if (
                is_object(self::$registry[$regObj])
                && self::$registry[$regObj] instanceof self
            ) {
                // Check if instance has given PID.
                if (
                    !$pid
                    || !self::$registry[$regObj]->pid
                    || $pid == self::$registry[$regObj]->pid
                ) {
                    // Return singleton instance if available.
                    return self::$registry[$regObj];
                }
            } else {
                // Check the user's session...
                $sessionData = Helper::loadFromSession(get_called_class());
                if (
                    is_object($sessionData[$regObj])
                    && $sessionData[$regObj] instanceof self
                ) {
                    // Check if instance has given PID.
                    if (
                        !$pid
                        || !$sessionData[$regObj]->pid
                        || $pid == $sessionData[$regObj]->pid
                    ) {
                        // ...and restore registry.
                        self::$registry[$regObj] = $sessionData[$regObj];
                        return self::$registry[$regObj];
                    }
                }
            }
        }
        // Create new instance depending on format (METS or IIIF) ...
        $instance = null;
        $documentFormat = null;
        $xml = null;
        $iiif = null;
        // Try to get document format from database
        if (MathUtility::canBeInterpretedAsInteger($uid)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $queryBuilder
                ->select(
                    'tx_dlf_documents.location AS location',
                    'tx_dlf_documents.document_format AS document_format'
                )
                ->from('tx_dlf_documents');

            // Get UID of document with given record identifier.
            if ($pid) {
                $queryBuilder
                    ->where(
                        $queryBuilder->expr()->eq('tx_dlf_documents.uid', intval($uid)),
                        $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($pid)),
                        Helper::whereExpression('tx_dlf_documents')
                    );
            } else {
                $queryBuilder
                    ->where(
                        $queryBuilder->expr()->eq('tx_dlf_documents.uid', intval($uid)),
                        Helper::whereExpression('tx_dlf_documents')
                    );
            }

            $result = $queryBuilder
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                $documentFormat = $resArray['document_format'];
            }
        } else {
            // Get document format from content of remote document
            // Cast to string for safety reasons.
            $location = (string) $uid;
            // Try to load a file from the url
            if (GeneralUtility::isValidUrl($location)) {
                // Load extension configuration
                $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
                // Set user-agent to identify self when fetching XML data.
                if (!empty($extConf['useragent'])) {
                    @ini_set('user_agent', $extConf['useragent']);
                }
                $content = GeneralUtility::getUrl($location);
                if ($content !== false) {
                    // TODO use single place to load xml
                    // Turn off libxml's error logging.
                    $libxmlErrors = libxml_use_internal_errors(true);
                    // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
                    $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
                    // Try to load XML from file.
                    $xml = simplexml_load_string($content);
                    // reset entity loader setting
                    libxml_disable_entity_loader($previousValueOfEntityLoader);
                    // Reset libxml's error logging.
                    libxml_use_internal_errors($libxmlErrors);
                    if ($xml !== false) {
                        /* @var $xml \SimpleXMLElement */
                        $xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');
                        $xpathResult = $xml->xpath('//mets:mets');
                        $documentFormat = !empty($xpathResult) ? 'METS' : null;
                    } else {
                        // Try to load file as IIIF resource instead.
                        $contentAsJsonArray = json_decode($content, true);
                        if ($contentAsJsonArray !== null) {
                            // Load plugin configuration.
                            $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
                            IiifHelper::setUrlReader(IiifUrlReader::getInstance());
                            IiifHelper::setMaxThumbnailHeight($conf['iiifThumbnailHeight']);
                            IiifHelper::setMaxThumbnailWidth($conf['iiifThumbnailWidth']);
                            $iiif = IiifHelper::loadIiifResource($contentAsJsonArray);
                            if ($iiif instanceof IiifResourceInterface) {
                                $documentFormat = 'IIIF';
                            }
                        }
                    }
                }
            }
        }
        // Sanitize input.
        $pid = max(intval($pid), 0);
        if ($documentFormat == 'METS') {
            $instance = new MetsDocument($uid, $pid, $xml);
        } elseif ($documentFormat == 'IIIF') {
            $instance = new IiifManifest($uid, $pid, $iiif);
        }
        // Save instance to registry.
        if (
            $instance instanceof self
            && $instance->ready) {
            self::$registry[Helper::digest($instance->uid)] = $instance;
            if ($instance->uid != $instance->location) {
                self::$registry[Helper::digest($instance->location)] = $instance;
            }
            // Load extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
            // Save registry to session if caching is enabled.
            if (!empty($extConf['caching'])) {
                Helper::saveToSession(self::$registry, get_class($instance));
            }
        }
        // Return new instance.
        return $instance;
    }

    /**
     * This gets details about a logical structure element
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the logical structure node (METS) or
     * the @id property of the Manifest / Range (IIIF)
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
     * @param string $id: The @ID attribute of the logical structure node (METS) or the @id property
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
     * This extracts the raw text for a physical structure node / IIIF Manifest / Canvas. Text might be
     * given as ALTO for METS or as annotations or ALTO for IIIF resources. If IIIF plain text annotations
     * with the motivation "painting" should be treated as full text representations, the extension has to be
     * configured accordingly.
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the physical structure node (METS) or the @id property
     * of the Manifest / Range (IIIF)
     *
     * @return string The physical structure node's / IIIF resource's raw text
     */
    public abstract function getRawText($id);

    /**
     * This extracts the raw text for a physical structure node / IIIF Manifest / Canvas from an
     * XML fulltext representation (currently only ALTO). For IIIF manifests, ALTO documents have
     * to be given in the Canvas' / Manifest's "seeAlso" property.
     *
     * @param string $id: The @ID attribute of the physical structure node (METS) or the @id property
     * of the Manifest / Range (IIIF)
     *
     * @return string The physical structure node's / IIIF resource's raw text from XML
     */
    protected function getRawTextFromXml($id)
    {
        $rawText = '';
        // Load available text formats, ...
        $this->loadFormats();
        // ... physical structure ...
        $this->_getPhysicalStructure();
        // ... and extension configuration.
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $extConf['fileGrpFulltext']);
        if (!empty($this->physicalStructureInfo[$id])) {
            while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
                if (!empty($this->physicalStructureInfo[$id]['files'][$fileGrpFulltext])) {
                    // Get fulltext file.
                    $file = GeneralUtility::getUrl($this->getFileLocation($this->physicalStructureInfo[$id]['files'][$fileGrpFulltext]));
                    if ($file !== false) {
                        // Turn off libxml's error logging.
                        $libxmlErrors = libxml_use_internal_errors(true);
                        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept.
                        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
                        // Load XML from file.
                        $rawTextXml = simplexml_load_string($file);
                        // Reset entity loader setting.
                        libxml_disable_entity_loader($previousValueOfEntityLoader);
                        // Reset libxml's error logging.
                        libxml_use_internal_errors($libxmlErrors);
                        // Get the root element's name as text format.
                        $textFormat = strtoupper($rawTextXml->getName());
                    } else {
                        Helper::devLog('Couln\'t load fulltext file for structure node @ID "' . $id . '"', DEVLOG_SEVERITY_WARNING);
                        return $rawText;
                    }
                    break;
                }
            }
        } else {
            Helper::devLog('Invalid structure node @ID "' . $id . '"', DEVLOG_SEVERITY_WARNING);
            return $rawText;
        }
        // Is this text format supported?
        if (
            !empty($rawTextXml)
            && !empty($this->formats[$textFormat])
        ) {
            if (!empty($this->formats[$textFormat]['class'])) {
                $class = $this->formats[$textFormat]['class'];
                // Get the raw text from class.
                if (
                    class_exists($class)
                    && ($obj = GeneralUtility::makeInstance($class)) instanceof FulltextInterface
                ) {
                    $rawText = $obj->getRawText($rawTextXml);
                    $this->rawTextArray[$id] = $rawText;
                } else {
                    Helper::devLog('Invalid class/method "' . $class . '->getRawText()" for text format "' . $textFormat . '"', DEVLOG_SEVERITY_WARNING);
                }
            }
        } else {
            Helper::devLog('Unsupported text format "' . $textFormat . '" in physical node with @ID "' . $id . '"', DEVLOG_SEVERITY_WARNING);
        }
        return $rawText;
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
                Helper::devLog('No document with UID ' . $uid . ' found or document not accessible', DEVLOG_SEVERITY_WARNING);
            }
        } else {
            Helper::devLog('Invalid UID ' . $uid . ' for document', DEVLOG_SEVERITY_ERROR);
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
     * Traverse a logical (sub-) structure tree to find the structure with the requested logical id and return it's depth.
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
     * @return void
     */
    protected abstract function init();

    /**
     * Reuse any document object that might have been already loaded to determine wether document is METS or IIIF
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
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($location)) {
            // Load extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
            // Set user-agent to identify self when fetching XML / JSON-LD data.
            if (!empty($extConf['useragent'])) {
                @ini_set('user_agent', $extConf['useragent']);
            }
            // the actual loading is format specific
            return $this->loadLocation($location);
        } else {
            Helper::devLog('Invalid file location "' . $location . '" for document loading', DEVLOG_SEVERITY_ERROR);
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
            Helper::devLog('Given object is neither a SimpleXMLElement nor a DOMXPath instance', DEVLOG_SEVERITY_ERROR);
            return;
        }
        // Register metadata format's namespaces.
        foreach ($this->formats as $enc => $conf) {
            $obj->$method(strtolower($enc), $conf['namespaceURI']);
        }
    }

    /**
     * This saves the document to the database and index
     *
     * @access public
     *
     * @param int $pid: The PID of the saved record
     * @param int $core: The UID of the Solr core for indexing
     * @param int|string $owner: UID or index_name of owner to set while indexing
     *
     * @return bool true on success or false on failure
     */
    public function save($pid = 0, $core = 0, $owner = null)
    {
        if (\TYPO3_MODE !== 'BE') {
            Helper::devLog('Saving a document is only allowed in the backend', DEVLOG_SEVERITY_ERROR);
            return false;
        }
        // Make sure $pid is a non-negative integer.
        $pid = max(intval($pid), 0);
        // Make sure $core is a non-negative integer.
        $core = max(intval($core), 0);
        // If $pid is not given, try to get it elsewhere.
        if (
            !$pid
            && $this->pid
        ) {
            // Retain current PID.
            $pid = $this->pid;
        } elseif (!$pid) {
            Helper::devLog('Invalid PID ' . $pid . ' for document saving', DEVLOG_SEVERITY_ERROR);
            return false;
        }
        // Set PID for metadata definitions.
        $this->cPid = $pid;
        // Set UID placeholder if not updating existing record.
        if ($pid != $this->pid) {
            $this->uid = uniqid('NEW');
        }
        // Get metadata array.
        $metadata = $this->getTitledata($pid);
        // Check for record identifier.
        if (empty($metadata['record_id'][0])) {
            Helper::devLog('No record identifier found to avoid duplication', DEVLOG_SEVERITY_ERROR);
            return false;
        }
        // Load plugin configuration.
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_structures');

        // Get UID for structure type.
        $result = $queryBuilder
            ->select('tx_dlf_structures.uid AS uid')
            ->from('tx_dlf_structures')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_structures.pid', intval($pid)),
                $queryBuilder->expr()->eq('tx_dlf_structures.index_name', $queryBuilder->expr()->literal($metadata['type'][0])),
                Helper::whereExpression('tx_dlf_structures')
            )
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
            $structure = $resArray['uid'];
        } else {
            Helper::devLog('Could not identify document/structure type "' . $queryBuilder->expr()->literal($metadata['type'][0]) . '"', DEVLOG_SEVERITY_ERROR);
            return false;
        }
        $metadata['type'][0] = $structure;

        // Remove appended "valueURI" from authors' names for storing in database.
        foreach ($metadata['author'] as $i => $author) {
            $splitName = explode(chr(31), $author);
            $metadata['author'][$i] = $splitName[0];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        // Get UIDs for collections.
        $result = $queryBuilder
            ->select(
                'tx_dlf_collections.index_name AS index_name',
                'tx_dlf_collections.uid AS uid'
            )
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($pid)),
                $queryBuilder->expr()->in('tx_dlf_collections.sys_language_uid', [-1, 0]),
                Helper::whereExpression('tx_dlf_collections')
            )
            ->execute();

        $collUid = [];
        while ($resArray = $result->fetch()) {
            $collUid[$resArray['index_name']] = $resArray['uid'];
        }
        $collections = [];
        foreach ($metadata['collection'] as $collection) {
            if (!empty($collUid[$collection])) {
                // Add existing collection's UID.
                $collections[] = $collUid[$collection];
            } else {
                // Insert new collection.
                $collNewUid = uniqid('NEW');
                $collData['tx_dlf_collections'][$collNewUid] = [
                    'pid' => $pid,
                    'label' => $collection,
                    'index_name' => $collection,
                    'oai_name' => (!empty($conf['publishNewCollections']) ? Helper::getCleanString($collection) : ''),
                    'description' => '',
                    'documents' => 0,
                    'owner' => 0,
                    'status' => 0,
                ];
                $substUid = Helper::processDBasAdmin($collData);
                // Prevent double insertion.
                unset($collData);
                // Add new collection's UID.
                $collections[] = $substUid[$collNewUid];
                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    Helper::addMessage(
                        htmlspecialchars(sprintf(Helper::getMessage('flash.newCollection'), $collection, $substUid[$collNewUid])),
                        Helper::getMessage('flash.attention', true),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
                        true
                    );
                }
            }
        }
        $metadata['collection'] = $collections;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_libraries');

        // Get UID for owner.
        if (empty($owner)) {
            $owner = empty($metadata['owner'][0]) ? $metadata['owner'][0] : 'default';
        }
        if (!MathUtility::canBeInterpretedAsInteger($owner)) {
            $result = $queryBuilder
                ->select('tx_dlf_libraries.uid AS uid')
                ->from('tx_dlf_libraries')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_libraries.pid', intval($pid)),
                    $queryBuilder->expr()->eq('tx_dlf_libraries.index_name', $queryBuilder->expr()->literal($owner)),
                    Helper::whereExpression('tx_dlf_libraries')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                $ownerUid = $resArray['uid'];
            } else {
                // Insert new library.
                $libNewUid = uniqid('NEW');
                $libData['tx_dlf_libraries'][$libNewUid] = [
                    'pid' => $pid,
                    'label' => $owner,
                    'index_name' => $owner,
                    'website' => '',
                    'contact' => '',
                    'image' => '',
                    'oai_label' => '',
                    'oai_base' => '',
                    'opac_label' => '',
                    'opac_base' => '',
                    'union_label' => '',
                    'union_base' => '',
                ];
                $substUid = Helper::processDBasAdmin($libData);
                // Add new library's UID.
                $ownerUid = $substUid[$libNewUid];
                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    Helper::addMessage(
                        htmlspecialchars(sprintf(Helper::getMessage('flash.newLibrary'), $owner, $ownerUid)),
                        Helper::getMessage('flash.attention', true),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
                        true
                    );
                }
            }
            $owner = $ownerUid;
        }
        $metadata['owner'][0] = $owner;
        // Get UID of parent document.
        $partof = $this->getParentDocumentUidForSaving($pid, $core, $owner);
        // Use the date of publication or title as alternative sorting metric for parts of multi-part works.
        if (!empty($partof)) {
            if (
                empty($metadata['volume'][0])
                && !empty($metadata['year'][0])
            ) {
                $metadata['volume'] = $metadata['year'];
            }
            if (empty($metadata['volume_sorting'][0])) {
                // If METS @ORDER is given it is preferred over year_sorting and year.
                if (!empty($metadata['mets_order'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['mets_order'][0];
                } elseif (!empty($metadata['year_sorting'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['year_sorting'][0];
                } elseif (!empty($metadata['year'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['year'][0];
                }
            }
            // If volume_sorting is still empty, try to use title_sorting or METS @ORDERLABEL finally (workaround for newspapers)
            if (empty($metadata['volume_sorting'][0])) {
                if (!empty($metadata['title_sorting'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['title_sorting'][0];
                } elseif (!empty($metadata['mets_orderlabel'][0])) {
                    $metadata['volume_sorting'][0] = $metadata['mets_orderlabel'][0];
                }
            }
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');

        // Get metadata for lists and sorting.
        $result = $queryBuilder
            ->select(
                'tx_dlf_metadata.index_name AS index_name',
                'tx_dlf_metadata.is_listed AS is_listed',
                'tx_dlf_metadata.is_sortable AS is_sortable'
            )
            ->from('tx_dlf_metadata')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('tx_dlf_metadata.is_listed', 1),
                    $queryBuilder->expr()->eq('tx_dlf_metadata.is_sortable', 1)
                ),
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($pid)),
                Helper::whereExpression('tx_dlf_metadata')
            )
            ->execute();

        $listed = [];
        $sortable = [];

        while ($resArray = $result->fetch()) {
            if (!empty($metadata[$resArray['index_name']])) {
                if ($resArray['is_listed']) {
                    $listed[$resArray['index_name']] = $metadata[$resArray['index_name']];
                }
                if ($resArray['is_sortable']) {
                    $sortable[$resArray['index_name']] = $metadata[$resArray['index_name']][0];
                }
            }
        }
        // Fill data array.
        $data['tx_dlf_documents'][$this->uid] = [
            'pid' => $pid,
            $GLOBALS['TCA']['tx_dlf_documents']['ctrl']['enablecolumns']['starttime'] => 0,
            $GLOBALS['TCA']['tx_dlf_documents']['ctrl']['enablecolumns']['endtime'] => 0,
            'prod_id' => $metadata['prod_id'][0],
            'location' => $this->location,
            'record_id' => $metadata['record_id'][0],
            'opac_id' => $metadata['opac_id'][0],
            'union_id' => $metadata['union_id'][0],
            'urn' => $metadata['urn'][0],
            'purl' => $metadata['purl'][0],
            'title' => $metadata['title'][0],
            'title_sorting' => $metadata['title_sorting'][0],
            'author' => implode('; ', $metadata['author']),
            'year' => implode('; ', $metadata['year']),
            'place' => implode('; ', $metadata['place']),
            'thumbnail' => $this->_getThumbnail(true),
            'metadata' => serialize($listed),
            'metadata_sorting' => serialize($sortable),
            'structure' => $metadata['type'][0],
            'partof' => $partof,
            'volume' => $metadata['volume'][0],
            'volume_sorting' => $metadata['volume_sorting'][0],
            'license' => $metadata['license'][0],
            'terms' => $metadata['terms'][0],
            'restrictions' => $metadata['restrictions'][0],
            'out_of_print' => $metadata['out_of_print'][0],
            'rights_info' => $metadata['rights_info'][0],
            'collections' => $metadata['collection'],
            'mets_label' => $metadata['mets_label'][0],
            'mets_orderlabel' => $metadata['mets_orderlabel'][0],
            'mets_order' => $metadata['mets_order'][0],
            'owner' => $metadata['owner'][0],
            'solrcore' => $core,
            'status' => 0,
            'document_format' => $metadata['document_format'][0],
        ];
        // Unhide hidden documents.
        if (!empty($conf['unhideOnIndex'])) {
            $data['tx_dlf_documents'][$this->uid][$GLOBALS['TCA']['tx_dlf_documents']['ctrl']['enablecolumns']['disabled']] = 0;
        }
        // Process data.
        $newIds = Helper::processDBasAdmin($data);
        // Replace placeholder with actual UID.
        if (strpos($this->uid, 'NEW') === 0) {
            $this->uid = $newIds[$this->uid];
            $this->pid = $pid;
            $this->parentId = $partof;
        }
        if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
            Helper::addMessage(
                htmlspecialchars(sprintf(Helper::getMessage('flash.documentSaved'), $metadata['title'][0], $this->uid)),
                Helper::getMessage('flash.done', true),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                true
            );
        }
        // Add document to index.
        if ($core) {
            Indexer::add($this, $core);
        } else {
            Helper::devLog('Invalid UID "' . $core . '" for Solr core', DEVLOG_SEVERITY_NOTICE);
        }
        return true;
    }

    /**
     * Get the ID of the parent document if the current document has one. Also save a parent document
     * to the database and the Solr index if their $pid and the current $pid differ.
     * Currently only applies to METS documents.
     *
     * @access protected
     *
     * @abstract
     *
     * @return int The parent document's id.
     */
    protected abstract function getParentDocumentUidForSaving($pid, $core, $owner);

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
            Helper::devLog('Invalid PID ' . $cPid . ' for metadata definitions', DEVLOG_SEVERITY_ERROR);
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
     * by @ORDER attribute / IIIF Sequence's Canvases
     */
    protected abstract function _getPhysicalStructure();

    /**
     * This gives an array of the document's physical structure metadata
     *
     * @access protected
     *
     * @return array Array of elements' type, label and file representations ordered by @ID attribute / Canvas order
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
                $parent = self::getInstance($this->parentId, $this->pid);
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
     * This magic method is invoked each time a clone is called on the object variable
     *
     * @access protected
     *
     * @return void
     */
    protected function __clone()
    {
        // This method is defined as protected because singleton objects should not be cloned.
    }

    /**
     * This is a singleton class, thus the constructor should be private/protected
     * (Get an instance of this class by calling \Kitodo\Dlf\Common\Document::getInstance())
     *
     * @access protected
     *
     * @param int $uid: The UID of the document to parse or URL to XML file
     * @param int $pid: If > 0, then only document with this PID gets loaded
     * @param \SimpleXMLElement|IiifResourceInterface $preloadedDocument: Either null or the \SimpleXMLElement
     * or IiifResourceInterface that has been loaded to determine the basic document format.
     *
     * @return void
     */
    protected function __construct($uid, $pid, $preloadedDocument)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');
        $location = '';
        // Prepare to check database for the requested document.
        if (MathUtility::canBeInterpretedAsInteger($uid)) {
            $whereClause = $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq('tx_dlf_documents.uid', intval($uid)),
                Helper::whereExpression('tx_dlf_documents')
            );
        } else {
            // Try to load METS file / IIIF manifest.
            if ($this->setPreloadedDocument($preloadedDocument) || (GeneralUtility::isValidUrl($uid)
                && $this->load($uid))) {
                // Initialize core METS object.
                $this->init();
                if ($this->getDocument() !== null) {
                    // Cast to string for safety reasons.
                    $location = (string) $uid;
                    $this->establishRecordId($pid);
                } else {
                    // No METS / IIIF part found.
                    return;
                }
            } else {
                // Loading failed.
                return;
            }
            if (
                !empty($location)
                && !empty($this->recordId)
            ) {
                // Try to match record identifier or location (both should be unique).
                $whereClause = $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('tx_dlf_documents.location', $queryBuilder->expr()->literal($location)),
                        $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($this->recordId))
                    ),
                    Helper::whereExpression('tx_dlf_documents')
                );
            } else {
                // Can't persistently identify document, don't try to match at all.
                $whereClause = '1=-1';
            }
        }
        // Check for PID if needed.
        if ($pid) {
            $whereClause = $queryBuilder->expr()->andX(
                $whereClause,
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($pid))
            );
        }
        // Get document PID and location from database.
        $result = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.pid AS pid',
                'tx_dlf_documents.record_id AS record_id',
                'tx_dlf_documents.partof AS partof',
                'tx_dlf_documents.thumbnail AS thumbnail',
                'tx_dlf_documents.location AS location'
            )
            ->from('tx_dlf_documents')
            ->where($whereClause)
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
            $this->uid = $resArray['uid'];
            $this->pid = $resArray['pid'];
            $this->recordId = $resArray['record_id'];
            $this->parentId = $resArray['partof'];
            $this->thumbnail = $resArray['thumbnail'];
            $this->location = $resArray['location'];
            $this->thumbnailLoaded = true;
            // Load XML file if necessary...
            if (
                $this->getDocument() === null
                && $this->load($this->location)
            ) {
                // ...and set some basic properties.
                $this->init();
            }
            // Do we have a METS / IIIF object now?
            if ($this->getDocument() !== null) {
                // Set new location if necessary.
                if (!empty($location)) {
                    $this->location = $location;
                }
                // Document ready!
                $this->ready = true;
            }
        } elseif ($this->getDocument() !== null) {
            // Set location as UID for documents not in database.
            $this->uid = $location;
            $this->location = $location;
            // Document ready!
            $this->ready = true;
        } else {
            Helper::devLog('No document with UID ' . $uid . ' found or document not accessible', DEVLOG_SEVERITY_ERROR);
        }
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
            Helper::devLog('There is no getter function for property "' . $var . '"', DEVLOG_SEVERITY_WARNING);
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
            Helper::devLog('There is no setter function for property "' . $var . '"', DEVLOG_SEVERITY_WARNING);
        } else {
            $this->$method($value);
        }
    }
}
