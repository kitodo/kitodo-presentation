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

/**
 * Document class 'tx_dlf_document' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
final class tx_dlf_document {

    /**
     * This holds the whole XML file as string for serialization purposes
     * @see __sleep() / __wakeup()
     *
     * @var	string
     * @access protected
     */
    protected $asXML = '';

    /**
     * This holds the PID for the configuration
     *
     * @var	integer
     * @access protected
     */
    protected $cPid = 0;

    /**
     * This holds the XML file's dmdSec parts with their IDs as array key
     *
     * @var	array
     * @access protected
     */
    protected $dmdSec = array ();

    /**
     * Are the METS file's dmdSecs loaded?
     * @see $dmdSec
     *
     * @var	boolean
     * @access protected
     */
    protected $dmdSecLoaded = FALSE;

    /**
     * The extension key
     *
     * @var	string
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * This holds the file ID -> USE concordance
     * @see _getFileGrps()
     *
     * @var	array
     * @access protected
     */
    protected $fileGrps = array ();

    /**
     * Are the file groups loaded?
     * @see $fileGrps
     *
     * @var	boolean
     * @access protected
     */
    protected $fileGrpsLoaded = FALSE;

    /**
     * This holds the configuration for all supported metadata encodings
     * @see loadFormats()
     *
     * @var	array
     * @access protected
     */
    protected $formats = array (
        'OAI' => array (
            'rootElement' => 'OAI-PMH',
            'namespaceURI' => 'http://www.openarchives.org/OAI/2.0/',
        ),
        'METS' => array (
            'rootElement' => 'mets',
            'namespaceURI' => 'http://www.loc.gov/METS/',
        ),
        'XLINK' => array (
            'rootElement' => 'xlink',
            'namespaceURI' => 'http://www.w3.org/1999/xlink',
        )
    );

    /**
     * Are the available metadata formats loaded?
     * @see $formats
     *
     * @var	boolean
     * @access protected
     */
    protected $formatsLoaded = FALSE;

    /**
     * Are there any fulltext files available?
     *
     * @var boolean
     * @access protected
     */
    protected $hasFulltext = FALSE;

    /**
     * Mimetype of fulltext application/tei+xml or text/xml?
     *
     * @var string
     * @access protected
     */
    protected $mimetypeFulltext = '';

    /**
     * Last searched logical and physical page
     *
     * @var	array
     * @access protected
     */
    protected $lastSearchedPhysicalPage = array ('logicalPage' => NULL, 'physicalPage' => NULL);

    /**
     * This holds the documents location
     *
     * @var	string
     * @access protected
     */
    protected $location = '';

    /**
     * This holds the logical units
     *
     * @var	array
     * @access protected
     */
    protected $logicalUnits = array ();

    /**
     * This holds the documents' parsed metadata array with their corresponding structMap//div's ID as array key
     *
     * @var	array
     * @access protected
     */
    protected $metadataArray = array ();

    /**
     * Is the metadata array loaded?
     * @see $metadataArray
     *
     * @var	boolean
     * @access protected
     */
    protected $metadataArrayLoaded = FALSE;

    /**
     * This holds the XML file's METS part as SimpleXMLElement object
     *
     * @var	SimpleXMLElement
     * @access protected
     */
    protected $mets;

    /**
     * The holds the total number of pages
     *
     * @var	integer
     * @access protected
     */
    protected $numPages = 0;

    /**
     * This holds the UID of the parent document or zero if not multi-volumed
     *
     * @var	integer
     * @access protected
     */
    protected $parentId = 0;

    /**
     * This holds the physical structure
     *
     * @var	array
     * @access protected
     */
    protected $physicalStructure = array ();

    /**
     * This holds the physical structure metadata
     *
     * @var	array
     * @access protected
     */
    protected $physicalStructureInfo = array ();

    /**
     * Is the physical structure loaded?
     * @see $physicalStructure
     *
     * @var	boolean
     * @access protected
     */
    protected $physicalStructureLoaded = FALSE;

    /**
     * This holds the PID of the document or zero if not in database
     *
     * @var	integer
     * @access protected
     */
    protected $pid = 0;

    /**
     * This holds the documents' raw text pages with their corresponding structMap//div's ID as array key
     *
     * @var	array
     * @access protected
     */
    protected $rawTextArray = array ();

    /**
     * Is the document instantiated successfully?
     *
     * @var	boolean
     * @access protected
     */
    protected $ready = FALSE;

    /**
     * The METS file's record identifier
     *
     * @var	string
     * @access protected
     */
    protected $recordId;

    /**
     * This holds the singleton object of the document
     *
     * @var	array (tx_dlf_document)
     * @access protected
     */
    protected static $registry = array ();

    /**
     * This holds the UID of the root document or zero if not multi-volumed
     *
     * @var	integer
     * @access protected
     */
    protected $rootId = 0;

    /**
     * Is the root id loaded?
     * @see $rootId
     *
     * @var	boolean
     * @access protected
     */
    protected $rootIdLoaded = FALSE;

    /**
     * This holds the smLinks between logical and physical structMap
     *
     * @var	array
     * @access protected
     */
    protected $smLinks = array ('l2p' => array (), 'p2l' => array ());

    /**
     * Are the smLinks loaded?
     * @see $smLinks
     *
     * @var	boolean
     * @access protected
     */
    protected $smLinksLoaded = FALSE;

    /**
     * This holds the logical structure
     *
     * @var	array
     * @access protected
     */
    protected $tableOfContents = array ();

    /**
     * Is the table of contents loaded?
     * @see $tableOfContents
     *
     * @var	boolean
     * @access protected
     */
    protected $tableOfContentsLoaded = FALSE;

    /**
     * This holds the document's thumbnail location.
     *
     * @var	string
     * @access protected
     */
    protected $thumbnail = '';

    /**
     * Is the document's thumbnail location loaded?
     * @see $thumbnail
     *
     * @var	boolean
     * @access protected
     */
    protected $thumbnailLoaded = FALSE;

    /**
     * This holds the toplevel structure's @ID
     *
     * @var	string
     * @access protected
     */
    protected $toplevelId = '';

    /**
     * This holds the UID or the URL of the document
     *
     * @var	mixed
     * @access protected
     */
    protected $uid = 0;

    /**
     * This holds the whole XML file as SimpleXMLElement object
     *
     * @var	SimpleXMLElement
     * @access protected
     */
    protected $xml;

    /**
     * This adds metadata from METS structural map to metadata array.
     *
     * @access	public
     *
     * @param	array	&$metadata: The metadata array to extend
     * @param	string	$id: The @ID attribute of the logical structure node
     *
     * @return  void
     */
    public function addMetadataFromMets(&$metadata, $id) {
        $details = $this->getLogicalStructure($id);
        if (!empty($details)) {
            $metadata['mets_label'][0] = $details['label'];
            $metadata['mets_orderlabel'][0] = $details['orderlabel'];
            // Set publication date to @ORDERLABEL only for calendar.
            if ($details['type'] == 'year' && empty($metadata['year'][0])) {
                $metadata['year'][0] = $details['orderlabel'];
            }
        }
    }

    /**
     * This clears the static registry to prevent memory exhaustion
     *
     * @access	public
     *
     * @return	void
     */
    public static function clearRegistry() {

        // Reset registry array.
        self::$registry = array ();

    }

    /**
     * This gets the location of a file representing a physical page or track
     *
     * @access	public
     *
     * @param	string		$id: The @ID attribute of the file node
     *
     * @return	string		The file's location as URL
     */
    public function getFileLocation($id) {

        if (!empty($id) && ($location = $this->mets->xpath('./mets:fileSec/mets:fileGrp/mets:file[@ID="'.$id.'"]/mets:FLocat[@LOCTYPE="URL"]'))) {

            return (string) $location[0]->attributes('http://www.w3.org/1999/xlink')->href;

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getFileLocation('.$id.')] There is no file node with @ID "'.$id.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

            }

            return '';

        }

    }

    /**
     * This gets the MIME type of a file representing a physical page or track
     *
     * @access	public
     *
     * @param	string		$id: The @ID attribute of the file node
     *
     * @return	string		The file's MIME type
     */
    public function getFileMimeType($id) {

        if (!empty($id) && ($mimetype = $this->mets->xpath('./mets:fileSec/mets:fileGrp/mets:file[@ID="'.$id.'"]/@MIMETYPE'))) {

            return (string) $mimetype[0];

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getFileMimeType('.$id.')] There is no file node with @ID "'.$id.'" or no MIME type specified', self::$extKey, SYSLOG_SEVERITY_WARNING);

            }

            return '';

        }

    }

    /**
     * This is a singleton class, thus an instance must be created by this method
     *
     * @access	public
     *
     * @param	mixed		$uid: The unique identifier of the document to parse or URL of XML file
     * @param	integer		$pid: If > 0, then only document with this PID gets loaded
     * @param	boolean		$forceReload: Force reloading the document instead of returning the cached instance
     *
     * @return	&tx_dlf_document		Instance of this class
     */
    public static function &getInstance($uid, $pid = 0, $forceReload = FALSE) {

        // Sanitize input.
        $pid = max(intval($pid), 0);

        if (!$forceReload) {

            $regObj = md5($uid);

            if (is_object(self::$registry[$regObj]) && self::$registry[$regObj] instanceof self) {

                // Check if instance has given PID.
                if (!$pid || !self::$registry[$regObj]->pid || $pid == self::$registry[$regObj]->pid) {

                    // Return singleton instance if available.
                    return self::$registry[$regObj];

                }

            } else {

                // Check the user's session...
                $sessionData = tx_dlf_helper::loadFromSession(get_called_class());

                if (is_object($sessionData[$regObj]) && $sessionData[$regObj] instanceof self) {

                    // Check if instance has given PID.
                    if (!$pid || !$sessionData[$regObj]->pid || $pid == $sessionData[$regObj]->pid) {

                        // ...and restore registry.
                        self::$registry[$regObj] = $sessionData[$regObj];

                        return self::$registry[$regObj];

                    }

                }

            }

        }

        // Create new instance...
        $instance = new self($uid, $pid);

        // ...and save it to registry.
        if ($instance->ready) {

            self::$registry[md5($instance->uid)] = $instance;

            if ($instance->uid != $instance->location) {

                self::$registry[md5($instance->location)] = $instance;

            }

            // Load extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

            // Save registry to session if caching is enabled.
            if (!empty($extConf['caching'])) {

                tx_dlf_helper::saveToSession(self::$registry, get_class($instance));

            }

        }

        // Return new instance.
        return $instance;

    }

    /**
     * This gets details about a logical structure element
     *
     * @access	public
     *
     * @param	string		$id: The @ID attribute of the logical structure node
     * @param	boolean		$recursive: Whether to include the child elements
     *
     * @return	array		Array of the element's id, label, type and physical page indexes/mptr link
     */
    public function getLogicalStructure($id, $recursive = FALSE) {

        $details = array ();

        // Is the requested logical unit already loaded?
        if (!$recursive && !empty($this->logicalUnits[$id])) {

            // Yes. Return it.
            return $this->logicalUnits[$id];

        } elseif (!empty($id)) {

            // Get specified logical unit.
            $divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]');

        } else {

            // Get all logical units at top level.
            $divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div');

        }

        if (!empty($divs)) {

            if (!$recursive) {

                // Get the details for the first xpath hit.
                $details = $this->getLogicalStructureInfo($divs[0]);

            } else {

                // Walk the logical structure recursively and fill the whole table of contents.
                foreach ($divs as $div) {

                    $this->tableOfContents[] = $this->getLogicalStructureInfo($div, TRUE);

                }

            }

        }

        return $details;

    }

    /**
     * This gets details about a logical structure element
     *
     * @access	protected
     *
     * @param	SimpleXMLElement		$structure: The logical structure node
     * @param	boolean		$recursive: Whether to include the child elements
     *
     * @return	array		Array of the element's id, label, type and physical page indexes/mptr link
     */
    protected function getLogicalStructureInfo(SimpleXMLElement $structure, $recursive = FALSE) {

        // Get attributes.
        foreach ($structure->attributes() as $attribute => $value) {

            $attributes[$attribute] = (string) $value;

        }

        // Load plugin configuration.
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

        // Extract identity information.
        $details = array ();

        $details['id'] = $attributes['ID'];

        $details['dmdId'] = (isset($attributes['DMDID']) ? $attributes['DMDID'] : '');

        $details['order'] = (isset($attributes['ORDER']) ? $attributes['ORDER'] : '');

        $details['label'] = (isset($attributes['LABEL']) ? $attributes['LABEL'] : '');

        $details['orderlabel'] = (isset($attributes['ORDERLABEL']) ? $attributes['ORDERLABEL'] : '');

        $details['contentIds'] = (isset($attributes['CONTENTIDS']) ? $attributes['CONTENTIDS'] : '');

        $details['volume'] = '';

        // Set volume information only if no label is set and this is the toplevel structure element.
        if (empty($details['label']) && $details['id'] == $this->_getToplevelId()) {

            $metadata = $this->getMetadata($details['id']);

            if (!empty($metadata['volume'][0])) {

                $details['volume'] = $metadata['volume'][0];

            }

        }

        $details['pagination'] = '';

        $details['type'] = $attributes['TYPE'];

        $details['thumbnailId'] = '';

        // Load smLinks.
        $this->_getSmLinks();

        // Load physical structure.
        $this->_getPhysicalStructure();

        // Get the physical page or external file this structure element is pointing at.
        $details['points'] = '';

        // Is there a mptr node?
        if (count($structure->children('http://www.loc.gov/METS/')->mptr)) {

            // Yes. Get the file reference.
            $details['points'] = (string) $structure->children('http://www.loc.gov/METS/')->mptr[0]->attributes('http://www.w3.org/1999/xlink')->href;

        // Are there any physical elements and is this logical unit linked to at least one of them?
        } elseif (!empty($this->physicalStructure) && array_key_exists($details['id'], $this->smLinks['l2p'])) {

            $details['points'] = max(intval(array_search($this->smLinks['l2p'][$details['id']][0], $this->physicalStructure, TRUE)), 1);

            if (!empty($this->physicalStructureInfo[$this->smLinks['l2p'][$details['id']][0]]['files'][$extConf['fileGrpThumbs']])) {

                $details['thumbnailId'] = $this->physicalStructureInfo[$this->smLinks['l2p'][$details['id']][0]]['files'][$extConf['fileGrpThumbs']];

            }

            // Get page/track number of the first page/track related to this structure element.
            $details['pagination'] = $this->physicalStructureInfo[$this->smLinks['l2p'][$details['id']][0]]['orderlabel'];

        // Is this the toplevel structure element?
        } elseif ($details['id'] == $this->_getToplevelId()) {

            // Yes. Point to itself.
            $details['points'] = 1;

            if (!empty($this->physicalStructure) && !empty($this->physicalStructureInfo[$this->physicalStructure[1]]['files'][$extConf['fileGrpThumbs']])) {

                $details['thumbnailId'] = $this->physicalStructureInfo[$this->physicalStructure[1]]['files'][$extConf['fileGrpThumbs']];

            }

        }

        // Get the files this structure element is pointing at.
        $details['files'] = array ();

        $fileUse = $this->_getFileGrps();

        // Get the file representations from fileSec node.
        foreach ($structure->children('http://www.loc.gov/METS/')->fptr as $fptr) {

            // Check if file has valid @USE attribute.
            if (!empty($fileUse[(string) $fptr->attributes()->FILEID])) {

                $details['files'][$fileUse[(string) $fptr->attributes()->FILEID]] = (string) $fptr->attributes()->FILEID;

            }

        }

        // Keep for later usage.
        $this->logicalUnits[$details['id']] = $details;

        // Walk the structure recursively? And are there any children of the current element?
        if ($recursive && count($structure->children('http://www.loc.gov/METS/')->div)) {

            $details['children'] = array ();

            foreach ($structure->children('http://www.loc.gov/METS/')->div as $child) {

                // Repeat for all children.
                $details['children'][] = $this->getLogicalStructureInfo($child, TRUE);

            }

        }

        return $details;

    }

    /**
     * This extracts all the metadata for a logical structure node
     *
     * @access	public
     *
     * @param	string		$id: The @ID attribute of the logical structure node
     * @param	integer		$cPid: The PID for the metadata definitions
     * 						(defaults to $this->cPid or $this->pid)
     *
     * @return	array		The logical structure node's parsed metadata array
     */
    public function getMetadata($id, $cPid = 0) {

        // Save parameter for logging purposes.
        $_cPid = $cPid;

        // Make sure $cPid is a non-negative integer.
        $cPid = max(intval($cPid), 0);

        // If $cPid is not given, try to get it elsewhere.
        if (!$cPid && ($this->cPid || $this->pid)) {

            // Retain current PID.
            $cPid = ($this->cPid ? $this->cPid : $this->pid);

        } elseif (!$cPid) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getMetadata('.$id.', '.$_cPid.')] Invalid PID "'.$cPid.'" for metadata definitions', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return array ();

        }

        // Get metadata from parsed metadata array if available.
        if (!empty($this->metadataArray[$id]) && $this->metadataArray[0] == $cPid) {

            return $this->metadataArray[$id];

        }

        // Initialize metadata array with empty values.
        $metadata = array (
            'title' => array (),
            'title_sorting' => array (),
            'author' => array (),
            'place' => array (),
            'year' => array (),
            'prod_id' => array (),
            'record_id' => array (),
            'opac_id' => array (),
            'union_id' => array (),
            'urn' => array (),
            'purl' => array (),
            'type' => array (),
            'volume' => array (),
            'volume_sorting' => array (),
            'collection' => array (),
            'owner' => array (),
            'mets_label' => array (),
            'mets_orderlabel' => array (),
        );

        // Get the logical structure node's DMDID.
        if (!empty($this->logicalUnits[$id])) {

            $dmdId = $this->logicalUnits[$id]['dmdId'];

        } else {

            $dmdId = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]/@DMDID');

            $dmdId = (string) $dmdId[0];

        }

        if (!empty($dmdId)) {

            // Load available metadata formats and dmdSecs.
            $this->loadFormats();

            $this->_getDmdSec();

            // Is this metadata format supported?
            if (!empty($this->formats[$this->dmdSec[$dmdId]['type']])) {

                if (!empty($this->formats[$this->dmdSec[$dmdId]['type']]['class'])) {

                    $class = $this->formats[$this->dmdSec[$dmdId]['type']]['class'];

                    // Get the metadata from class.
                    if (class_exists($class) && ($obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class)) instanceof tx_dlf_format) {

                        $obj->extractMetadata($this->dmdSec[$dmdId]['xml'], $metadata);

                    } else {

                        if (TYPO3_DLOG) {

                            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getMetadata('.$id.', '.$_cPid.')] Invalid class/method "'.$class.'->extractMetadata()" for metadata format "'.$this->dmdSec[$dmdId]['type'].'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

                        }

                    }

                }

            } else {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getMetadata('.$id.', '.$_cPid.')] Unsupported metadata format "'.$this->dmdSec[$dmdId]['type'].'" in dmdSec with @ID "'.$dmdId.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

                }

                return array ();

            }

            // Get the structure's type.
            if (!empty($this->logicalUnits[$id])) {

                $metadata['type'] = array ($this->logicalUnits[$id]['type']);

            } else {

                $struct = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]/@TYPE');

                $metadata['type'] = array ((string) $struct[0]);

            }

            // Get the additional metadata from database.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadataformat.xpath AS xpath,tx_dlf_metadataformat.xpath_sorting AS xpath_sorting,tx_dlf_metadata.is_sortable AS is_sortable,tx_dlf_metadata.default_value AS default_value,tx_dlf_metadata.format AS format',
                'tx_dlf_metadata,tx_dlf_metadataformat,tx_dlf_formats',
                'tx_dlf_metadata.pid='.$cPid.' AND tx_dlf_metadataformat.pid='.$cPid.' AND ((tx_dlf_metadata.uid=tx_dlf_metadataformat.parent_id AND tx_dlf_metadataformat.encoded=tx_dlf_formats.uid AND tx_dlf_formats.type='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->dmdSec[$dmdId]['type'], 'tx_dlf_formats').') OR tx_dlf_metadata.format=0)'.tx_dlf_helper::whereClause('tx_dlf_metadata', TRUE).tx_dlf_helper::whereClause('tx_dlf_metadataformat').tx_dlf_helper::whereClause('tx_dlf_formats'),
                '',
                '',
                ''
            );

            // We need a DOMDocument here, because SimpleXML doesn't support XPath functions properly.
            $domNode = dom_import_simplexml($this->dmdSec[$dmdId]['xml']);

            $domXPath = new DOMXPath($domNode->ownerDocument);

            $this->registerNamespaces($domXPath);

            // OK, now make the XPath queries.
            while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

                // Set metadata field's value(s).
                if ($resArray['format'] > 0 && !empty($resArray['xpath']) && ($values = $domXPath->evaluate($resArray['xpath'], $domNode))) {

                    if ($values instanceof DOMNodeList && $values->length > 0) {

                        $metadata[$resArray['index_name']] = array ();

                        foreach ($values as $value) {

                            $metadata[$resArray['index_name']][] = trim((string) $value->nodeValue);

                        }

                    } elseif (!($values instanceof DOMNodeList)) {

                        $metadata[$resArray['index_name']] = array (trim((string) $values));

                    }

                }

                // Set default value if applicable.
                // '!empty($resArray['default_value'])' is not possible, because '0' is a valid default value.
                // Setting an empty default value creates a lot of empty fields within the index.
                // These empty fields are then shown within the search facets as 'empty'.
                if (empty($metadata[$resArray['index_name']][0]) && strlen($resArray['default_value']) > 0) {

                    $metadata[$resArray['index_name']] = array ($resArray['default_value']);

                }

                // Set sorting value if applicable.
                if (!empty($metadata[$resArray['index_name']]) && $resArray['is_sortable']) {

                    if ($resArray['format'] > 0 && !empty($resArray['xpath_sorting']) && ($values = $domXPath->evaluate($resArray['xpath_sorting'], $domNode))) {

                        if ($values instanceof DOMNodeList && $values->length > 0) {

                            $metadata[$resArray['index_name'].'_sorting'][0] = trim((string) $values->item(0)->nodeValue);

                        } elseif (!($values instanceof DOMNodeList)) {

                            $metadata[$resArray['index_name'].'_sorting'][0] = trim((string) $values);

                        }

                    }

                    if (empty($metadata[$resArray['index_name'].'_sorting'][0])) {

                        $metadata[$resArray['index_name'].'_sorting'][0] = $metadata[$resArray['index_name']][0];

                    }

                }

            }

            // Set title to empty string if not present.
            if (empty($metadata['title'][0])) {

                $metadata['title'][0] = '';

                $metadata['title_sorting'][0] = '';

            }

            // Add collections from database to toplevel element if document is already saved.
            if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->uid) && $id == $this->_getToplevelId()) {

                $result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
                    'tx_dlf_collections.index_name AS index_name',
                    'tx_dlf_documents',
                    'tx_dlf_relations',
                    'tx_dlf_collections',
                    'AND tx_dlf_collections.pid='.intval($cPid).' AND tx_dlf_documents.uid='.intval($this->uid).' AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').' AND tx_dlf_collections.sys_language_uid IN (-1,0)'.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
                    'tx_dlf_collections.index_name',
                    '',
                    ''
                );

                while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

                    if (!in_array($resArray['index_name'], $metadata['collection'])) {

                        $metadata['collection'][] = $resArray['index_name'];

                    }

                }

            }

        } else {

            // There is no dmdSec for this structure node.
            return array ();

        }

        return $metadata;

    }

    /**
     * This returns the first corresponding physical page number of a given logical page label
     *
     * @access	public
     *
     * @param	string		$logicalPage: The label (or a part of the label) of the logical page
     *
     * @return	integer		The physical page number
     */
    public function getPhysicalPage($logicalPage) {

        if (!empty($this->lastSearchedPhysicalPage['logicalPage']) && $this->lastSearchedPhysicalPage['logicalPage'] == $logicalPage) {

            return $this->lastSearchedPhysicalPage['physicalPage'];

        } else {

            $physicalPage = 0;

            foreach ($this->physicalStructureInfo as $page) {

                if (strpos($page['orderlabel'], $logicalPage) !== FALSE) {

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
     * This extracts the raw text for a physical structure node
     *
     * @access	public
     *
     * @param	string		$id: The @ID attribute of the physical structure node
     *
     * @return	string		The physical structure node's raw text
     */
    public function getRawText($id) {

        $rawText = '';

        // Get text from raw text array if available.
        if (!empty($this->rawTextArray[$id])) {

            return $this->rawTextArray[$id];

        }

        // Load fileGrps and check for fulltext files.
        $this->_getFileGrps();

        if ($this->hasFulltext) {

            // Load available text formats, ...
            $this->loadFormats();

            // ... physical structure ...
            $this->_getPhysicalStructure();

            // ... and extension configuration.
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

            if (!empty($this->physicalStructureInfo[$id])) {

                // Get fulltext file.
                $file = $this->getFileLocation($this->physicalStructureInfo[$id]['files'][$extConf['fileGrpFulltext']]);

                // Turn off libxml's error logging.
                $libxmlErrors = libxml_use_internal_errors(TRUE);

                // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept.
                $previousValueOfEntityLoader = libxml_disable_entity_loader(TRUE);

                // Load XML from file.
                $rawTextXml = simplexml_load_string(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file));

                // Reset entity loader setting.
                libxml_disable_entity_loader($previousValueOfEntityLoader);

                // Reset libxml's error logging.
                libxml_use_internal_errors($libxmlErrors);

                // Get the root element's name as text format.
                $textFormat = strtoupper($rawTextXml->getName());

            } else {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getRawText('.$id.')] Invalid structure node @ID "'.$id.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

                }

                return $rawText;

            }

            // Is this text format supported?
            if (!empty($this->formats[$textFormat])) {

                if (!empty($this->formats[$textFormat]['class'])) {

                    $class = $this->formats[$textFormat]['class'];

                    // Get the raw text from class.
                    if (class_exists($class) && ($obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class)) instanceof tx_dlf_fulltext) {

                        $rawText = $obj->getRawText($rawTextXml);

                        $this->rawTextArray[$id] = $rawText;

                    } else {

                        if (TYPO3_DLOG) {

                            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getRawText('.$id.')] Invalid class/method "'.$class.'->getRawText()" for text format "'.$textFormat.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

                        }

                    }

                }

            } else {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getRawText('.$id.')] Unsupported text format "'.$textFormat.'" in physical node with @ID "'.$id.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

                }

            }

        }

        return $rawText;

    }

    /**
     * This determines a title for the given document
     *
     * @access	public
     *
     * @param	integer		$uid: The UID of the document
     * @param	boolean		$recursive: Search superior documents for a title, too?
     *
     * @return	string		The title of the document itself or a parent document
     */
    public static function getTitle($uid, $recursive = FALSE) {

        // Save parameter for logging purposes.
        $_uid = $uid;

        $title = '';

        // Sanitize input.
        $uid = max(intval($uid), 0);

        if ($uid) {

            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_documents.title,tx_dlf_documents.partof',
                'tx_dlf_documents',
                'tx_dlf_documents.uid='.$uid.tx_dlf_helper::whereClause('tx_dlf_documents'),
                '',
                '',
                '1'
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

                // Get title information.
                list ($title, $partof) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

                // Search parent documents recursively for a title?
                if ($recursive && empty($title) && intval($partof) && $partof != $uid) {

                    $title = self::getTitle($partof, TRUE);

                }

            } else {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getTitle('.$_uid.', ['.($recursive ? 'TRUE' : 'FALSE').'])] No document with UID "'.$uid.'" found or document not accessible', self::$extKey, SYSLOG_SEVERITY_WARNING);

                }

            }

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getTitle('.$_uid.', ['.($recursive ? 'TRUE' : 'FALSE').'])] Invalid UID "'.$uid.'" for document', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

        }

        return $title;

    }

    /**
     * This extracts all the metadata for the toplevel logical structure node
     *
     * @access	public
     *
     * @param	integer		$cPid: The PID for the metadata definitions
     *
     * @return	array		The logical structure node's parsed metadata array
     */
    public function getTitledata($cPid = 0) {

        $titledata = $this->getMetadata($this->_getToplevelId(), $cPid);

        // Add information from METS structural map to titledata array.
        $this->addMetadataFromMets($titledata, $this->_getToplevelId());

        // Set record identifier for METS file if not present.
        if (is_array($titledata) && array_key_exists('record_id', $titledata)) {

            if (!empty($this->recordId) && !in_array($this->recordId, $titledata['record_id'])) {

                array_unshift($titledata['record_id'], $this->recordId);

            }

        };

        return $titledata;

    }

    /**
     * This sets some basic class properties
     *
     * @access	protected
     *
     * @return	void
     */
    protected function init() {

        // Get METS node from XML file.
        $this->registerNamespaces($this->xml);

        $mets = $this->xml->xpath('//mets:mets');

        if ($mets) {

            $this->mets = $mets[0];

            // Register namespaces.
            $this->registerNamespaces($this->mets);

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->init()] No METS part found in document with UID "'.$this->uid.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

        }

    }

    /**
     * Load XML file from URL
     *
     * @access	protected
     *
     * @param	string		$location: The URL of the file to load
     *
     * @return	boolean		TRUE on success or FALSE on failure
     */
    protected function load($location) {

        // Load XML file.
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($location)) {

            // Load extension configuration
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

            // Set user-agent to identify self when fetching XML data.
            if (!empty($extConf['useragent'])) {

                @ini_set('user_agent', $extConf['useragent']);

            }

            // Turn off libxml's error logging.
            $libxmlErrors = libxml_use_internal_errors(TRUE);

            // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
            $previousValueOfEntityLoader = libxml_disable_entity_loader(TRUE);

            // Load XML from file.
            $xml = simplexml_load_string(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($location));

            // reset entity loader setting
            libxml_disable_entity_loader($previousValueOfEntityLoader);

            // Reset libxml's error logging.
            libxml_use_internal_errors($libxmlErrors);

            // Set some basic properties.
            if ($xml !== FALSE) {

                $this->xml = $xml;

                return TRUE;

            } else {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->load('.$location.')] Could not load XML file from "'.$location.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

                }

            }

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->load('.$location.')] Invalid file location "'.$location.'" for document loading', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

        }

        return FALSE;

    }

    /**
     * Register all available data formats
     *
     * @access	protected
     *
     * @return	void
     */
    protected function loadFormats() {

        if (!$this->formatsLoaded) {

            // Get available data formats from database.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_formats.type AS type,tx_dlf_formats.root AS root,tx_dlf_formats.namespace AS namespace,tx_dlf_formats.class AS class',
                'tx_dlf_formats',
                'tx_dlf_formats.pid=0'.tx_dlf_helper::whereClause('tx_dlf_formats'),
                '',
                '',
                ''
            );

            while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

                // Update format registry.
                $this->formats[$resArray['type']] = array (
                    'rootElement' => $resArray['root'],
                    'namespaceURI' => $resArray['namespace'],
                    'class' => $resArray['class']
                );

            }

            $this->formatsLoaded = TRUE;

        }

    }

    /**
     * Register all available namespaces for a SimpleXMLElement object
     *
     * @access	public
     *
     * @param	SimpleXMLElement|DOMXPath		&$obj: SimpleXMLElement or DOMXPath object
     *
     * @return	void
     */
    public function registerNamespaces(&$obj) {

        $this->loadFormats();

        // Do we have a SimpleXMLElement or DOMXPath object?
        if ($obj instanceof SimpleXMLElement) {

            $method = 'registerXPathNamespace';

        } elseif ($obj instanceof DOMXPath) {

            $method = 'registerNamespace';

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->registerNamespaces(['.get_class($obj).'])] Given object is neither a SimpleXMLElement nor a DOMXPath instance', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

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
     * @access	public
     *
     * @param	integer		$pid: The PID of the saved record
     * @param	integer		$core: The UID of the Solr core for indexing
     *
     * @return	boolean		TRUE on success or FALSE on failure
     */
    public function save($pid = 0, $core = 0) {

        // Save parameters for logging purposes.
        $_pid = $pid;

        $_core = $core;

        if (TYPO3_MODE !== 'BE') {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->save('.$_pid.', '.$_core.')] Saving a document is only allowed in the backend', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return FALSE;

        }

        // Make sure $pid is a non-negative integer.
        $pid = max(intval($pid), 0);

        // Make sure $core is a non-negative integer.
        $core = max(intval($core), 0);

        // If $pid is not given, try to get it elsewhere.
        if (!$pid && $this->pid) {

            // Retain current PID.
            $pid = $this->pid;

        } elseif (!$pid) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->save('.$_pid.', '.$_core.')] Invalid PID "'.$pid.'" for document saving', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return FALSE;

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

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->save('.$_pid.', '.$_core.')] No record identifier found to avoid duplication', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return FALSE;

        }

        // Load plugin configuration.
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

        // Get UID for user "_cli_dlf".
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'be_users.uid AS uid',
            'be_users',
            'username='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_users').\TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('be_users').\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_users'),
            '',
            '',
            '1'
        );

        if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->save('.$_pid.', '.$_core.')] Backend user "_cli_dlf" not found or disabled', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return FALSE;

        }

        // Get UID for structure type.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_structures.uid AS uid',
            'tx_dlf_structures',
            'tx_dlf_structures.pid='.intval($pid).' AND tx_dlf_structures.index_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($metadata['type'][0], 'tx_dlf_structures').tx_dlf_helper::whereClause('tx_dlf_structures'),
            '',
            '',
            '1'
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

            list ($structure) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->save('.$_pid.', '.$_core.')] Could not identify document/structure type '.$GLOBALS['TYPO3_DB']->fullQuoteStr($metadata['type'][0], 'tx_dlf_structures'), self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return FALSE;

        }

        $metadata['type'][0] = $structure;

        // Remove appended "valueURI" from authors' names for storing in database.
        foreach ($metadata['author'] as $i => $author) {
            $splitName = explode(chr(31), $author);
            $metadata['author'][$i] = $splitName[0];
        }

        // Get UIDs for collections.
        $collections = array ();

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_collections.index_name AS index_name,tx_dlf_collections.uid AS uid',
            'tx_dlf_collections',
            'tx_dlf_collections.pid='.intval($pid).' AND tx_dlf_collections.sys_language_uid IN (-1,0)'.tx_dlf_helper::whereClause('tx_dlf_collections'),
            '',
            '',
            ''
        );

        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

            $collUid[$resArray['index_name']] = $resArray['uid'];

        }

        foreach ($metadata['collection'] as $collection) {

            if (!empty($collUid[$collection])) {

                // Add existing collection's UID.
                $collections[] = $collUid[$collection];

            } else {

                // Insert new collection.
                $collNewUid = uniqid('NEW');

                $collData['tx_dlf_collections'][$collNewUid] = array (
                    'pid' => $pid,
                    'label' => $collection,
                    'index_name' => $collection,
                    'oai_name' => (!empty($conf['publishNewCollections']) ? tx_dlf_helper::getCleanString($collection) : ''),
                    'description' => '',
                    'documents' => 0,
                    'owner' => 0,
                    'status' => 0,
                );

                $substUid = tx_dlf_helper::processDB($collData);

                // Prevent double insertion.
                unset ($collData);

                // Add new collection's UID.
                $collections[] = $substUid[$collNewUid];

                if (!defined('TYPO3_cliMode')) {

                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        htmlspecialchars(sprintf(tx_dlf_helper::getLL('flash.newCollection'), $collection, $substUid[$collNewUid])),
                        tx_dlf_helper::getLL('flash.attention', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
                        TRUE
                    );

                    tx_dlf_helper::addMessage($message);

                }

            }

        }

        $metadata['collection'] = $collections;

        // Get UID for owner.
        $owner = !empty($metadata['owner'][0]) ? $metadata['owner'][0] : 'default';

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_libraries.uid AS uid',
            'tx_dlf_libraries',
            'tx_dlf_libraries.pid='.intval($pid).' AND tx_dlf_libraries.index_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($owner, 'tx_dlf_libraries').tx_dlf_helper::whereClause('tx_dlf_libraries'),
            '',
            '',
            '1'
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

            list ($ownerUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

        } else {

            // Insert new library.
            $libNewUid = uniqid('NEW');

            $libData['tx_dlf_libraries'][$libNewUid] = array (
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
            );

            $substUid = tx_dlf_helper::processDB($libData);

            // Add new library's UID.
            $ownerUid = $substUid[$libNewUid];

            if (!defined('TYPO3_cliMode')) {

                $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    htmlspecialchars(sprintf(tx_dlf_helper::getLL('flash.newLibrary'), $owner, $ownerUid)),
                    tx_dlf_helper::getLL('flash.attention', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
                    TRUE
                );

                tx_dlf_helper::addMessage($message);

            }

        }

        $metadata['owner'][0] = $ownerUid;

        // Get UID of parent document.
        $partof = 0;

        // Get the closest ancestor of the current document which has a MPTR child.
        $parentMptr = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$this->_getToplevelId().'"]/ancestor::mets:div[./mets:mptr][1]/mets:mptr');

        if (!empty($parentMptr[0])) {

            $parentLocation = (string) $parentMptr[0]->attributes('http://www.w3.org/1999/xlink')->href;

            if ($parentLocation != $this->location) {

                $parentDoc = & tx_dlf_document::getInstance($parentLocation, $pid);

                if ($parentDoc->ready) {

                    if ($parentDoc->pid != $pid) {

                        $parentDoc->save($pid, $core);

                    }

                    $partof = $parentDoc->uid;

                }

            }

        }

        // Use the date of publication or title as alternative sorting metric for parts of multi-part works.
        if (!empty($partof)) {

            if (empty($metadata['volume'][0]) && !empty($metadata['year'][0])) {

                $metadata['volume'] = $metadata['year'];

            }

            if (empty($metadata['volume_sorting'][0])) {

                if (!empty($metadata['year_sorting'][0])) {

                    $metadata['volume_sorting'][0] = $metadata['year_sorting'][0];

                } elseif (!empty($metadata['year'][0])) {

                    $metadata['volume_sorting'][0] = $metadata['year'][0];

                }

            }

            // If volume_sorting is still empty, try to use title_sorting finally (workaround for newspapers)
            if (empty($metadata['volume_sorting'][0])) {

                if (!empty($metadata['title_sorting'][0])) {

                    $metadata['volume_sorting'][0] = $metadata['title_sorting'][0];

                }
            }

        }

        // Get metadata for lists and sorting.
        $listed = array ();

        $sortable = array ();

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.is_sortable AS is_sortable',
            'tx_dlf_metadata',
            '(tx_dlf_metadata.is_listed=1 OR tx_dlf_metadata.is_sortable=1) AND tx_dlf_metadata.pid='.intval($pid).tx_dlf_helper::whereClause('tx_dlf_metadata'),
            '',
            '',
            ''
        );

        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

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
        $data['tx_dlf_documents'][$this->uid] = array (
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
            'thumbnail' => $this->_getThumbnail(TRUE),
            'metadata' => serialize($listed),
            'metadata_sorting' => serialize($sortable),
            'structure' => $metadata['type'][0],
            'partof' => $partof,
            'volume' => $metadata['volume'][0],
            'volume_sorting' => $metadata['volume_sorting'][0],
            'collections' => $metadata['collection'],
            'owner' => $metadata['owner'][0],
            'mets_label' => $metadata['mets_label'][0],
            'mets_orderlabel' => $metadata['mets_orderlabel'][0],
            'solrcore' => $core,
            'status' => 0,
        );

        // Unhide hidden documents.
        if (!empty($conf['unhideOnIndex'])) {

            $data['tx_dlf_documents'][$this->uid][$GLOBALS['TCA']['tx_dlf_documents']['ctrl']['enablecolumns']['disabled']] = 0;

        }

        // Process data.
        $newIds = tx_dlf_helper::processDB($data);

        // Replace placeholder with actual UID.
        if (strpos($this->uid, 'NEW') === 0) {

            $this->uid = $newIds[$this->uid];

            $this->pid = $pid;

            $this->parentId = $partof;

        }

        if (!defined('TYPO3_cliMode')) {

            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                htmlspecialchars(sprintf(tx_dlf_helper::getLL('flash.documentSaved'), $metadata['title'][0], $this->uid)),
                tx_dlf_helper::getLL('flash.done', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                TRUE
            );

            tx_dlf_helper::addMessage($message);

        }

        // Add document to index.
        if ($core) {

            tx_dlf_indexing::add($this, $core);

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->save('.$_pid.', '.$_core.')] Invalid UID "'.$core.'" for Solr core', self::$extKey, SYSLOG_SEVERITY_NOTICE);

            }

        }

        return TRUE;

    }

    /**
     * This returns $this->cPid via __get()
     *
     * @access	protected
     *
     * @return	integer		The PID of the metadata definitions
     */
    protected function _getCPid() {

        return $this->cPid;

    }

    /**
     * This builds an array of the document's dmdSecs
     *
     * @access	protected
     *
     * @return	array		Array of dmdSecs with their IDs as array key
     */
    protected function _getDmdSec() {

        if (!$this->dmdSecLoaded) {

            // Get available data formats.
            $this->loadFormats();

            // Get dmdSec nodes from METS.
            $dmdIds = $this->mets->xpath('./mets:dmdSec/@ID');

            foreach ($dmdIds as $dmdId) {

                if ($type = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $dmdId.'"]/mets:mdWrap[not(@MDTYPE="OTHER")]/@MDTYPE')) {

                    if (!empty($this->formats[(string) $type[0]])) {

                        $type = (string) $type[0];

                        $xml = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $dmdId.'"]/mets:mdWrap[@MDTYPE="'.$type.'"]/mets:xmlData/'.strtolower($type).':'.$this->formats[$type]['rootElement']);

                    }

                } elseif ($type = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $dmdId.'"]/mets:mdWrap[@MDTYPE="OTHER"]/@OTHERMDTYPE')) {

                    if (!empty($this->formats[(string) $type[0]])) {

                        $type = (string) $type[0];

                        $xml = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $dmdId.'"]/mets:mdWrap[@MDTYPE="OTHER"][@OTHERMDTYPE="'.$type.'"]/mets:xmlData/'.strtolower($type).':'.$this->formats[$type]['rootElement']);

                    }

                }

                if ($xml) {

                    $this->dmdSec[(string) $dmdId]['type'] = $type;

                    $this->dmdSec[(string) $dmdId]['xml'] = $xml[0];

                    $this->registerNamespaces($this->dmdSec[(string) $dmdId]['xml']);

                }

            }

            $this->dmdSecLoaded = TRUE;

        }

        return $this->dmdSec;

    }

    /**
     * This builds the file ID -> USE concordance
     *
     * @access	protected
     *
     * @return	array		Array of file use groups with file IDs
     */
    protected function _getFileGrps() {

        if (!$this->fileGrpsLoaded) {

            // Get configured USE attributes.
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

            $useGrps = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $extConf['fileGrps']);

            if (!empty($extConf['fileGrpThumbs'])) {

                $useGrps[] = $extConf['fileGrpThumbs'];

            }

            if (!empty($extConf['fileGrpDownload'])) {

                $useGrps[] = $extConf['fileGrpDownload'];

            }

            if (!empty($extConf['fileGrpFulltext'])) {

                $useGrps[] = $extConf['fileGrpFulltext'];

            }

            if (!empty($extConf['fileGrpAudio'])) {

                $useGrps[] = $extConf['fileGrpAudio'];

            }

            // Get all file groups.
            $fileGrps = $this->mets->xpath('./mets:fileSec/mets:fileGrp');

            // Build concordance for configured USE attributes.
            foreach ($fileGrps as $fileGrp) {

                if (in_array((string) $fileGrp['USE'], $useGrps)) {

                    foreach ($fileGrp->children('http://www.loc.gov/METS/')->file as $file) {

                        $this->fileGrps[(string) $file->attributes()->ID] = (string) $fileGrp['USE'];

                    }

                }

            }

            // Are there any fulltext files available?
            if (!empty($extConf['fileGrpFulltext']) && in_array($extConf['fileGrpFulltext'], $this->fileGrps)) {

                $fft = $this->mets->xpath("./mets:fileSec/mets:fileGrp[@USE='FULLTEXT']/mets:file[1]");
                $this->mimetypeFulltext = $fft[0]->attributes()->MIMETYPE;
                $this->hasFulltext = TRUE;

            }

            $this->fileGrpsLoaded = TRUE;

        }

        return $this->fileGrps;

    }

    /**
     * This returns $this->hasFulltext via __get()
     *
     * @access	protected
     *
     * @return	boolean		Are there any fulltext files available?
     */
    protected function _getHasFulltext() {

        // Are the fileGrps already loaded?
        if (!$this->fileGrpsLoaded) {

            $this->_getFileGrps();

        }

        return $this->hasFulltext;

    }

    /**
     * This returns $this->location via __get()
     *
     * @access	protected
     *
     * @return	string		The location of the document
     */
    protected function _getLocation() {

        return $this->location;

    }

    /**
     * This builds an array of the document's metadata
     *
     * @access	protected
     *
     * @return	array		Array of metadata with their corresponding logical structure node ID as key
     */
    protected function _getMetadataArray() {

        // Set metadata definitions' PID.
        $cPid = ($this->cPid ? $this->cPid : $this->pid);

        if (!$cPid) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->getMetadataArray()] Invalid PID "'.$cPid.'" for metadata definitions', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return array ();

        }

        if (!$this->metadataArrayLoaded || $this->metadataArray[0] != $cPid) {

            // Get all logical structure nodes with metadata.
            if (($ids = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID]/@ID'))) {

                foreach ($ids as $id) {

                    $this->metadataArray[(string) $id] = $this->getMetadata((string) $id, $cPid);

                }

            }

            // Set current PID for metadata definitions.
            $this->metadataArray[0] = $cPid;

            $this->metadataArrayLoaded = TRUE;

        }

        return $this->metadataArray;

    }

    /**
     * This returns $this->mets via __get()
     *
     * @access	protected
     *
     * @return	SimpleXMLElement		The XML's METS part as SimpleXMLElement object
     */
    protected function _getMets() {

        return $this->mets;

    }

    /**
     * This returns $this->numPages via __get()
     *
     * @access	protected
     *
     * @return	integer		The total number of pages and/or tracks
     */
    protected function _getNumPages() {

        $this->_getPhysicalStructure();

        return $this->numPages;

    }

    /**
     * This returns $this->parentId via __get()
     *
     * @access	protected
     *
     * @return	integer		The UID of the parent document or zero if not applicable
     */
    protected function _getParentId() {

        return $this->parentId;

    }

    /**
     * This builds an array of the document's physical structure
     *
     * @access	protected
     *
     * @return	array		Array of physical elements' id, type, label and file representations ordered by @ORDER attribute
     */
    protected function _getPhysicalStructure() {

        // Is there no physical structure array yet?
        if (!$this->physicalStructureLoaded) {

            // Does the document have a structMap node of type "PHYSICAL"?
            $elementNodes = $this->mets->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div[@TYPE="physSequence"]/mets:div');

            if ($elementNodes) {

                // Get file groups.
                $fileUse = $this->_getFileGrps();

                // Get the physical sequence's metadata.
                $physNode = $this->mets->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div[@TYPE="physSequence"]');

                $physSeq[0] = (string) $physNode[0]['ID'];

                $this->physicalStructureInfo[$physSeq[0]]['id'] = (string) $physNode[0]['ID'];

                $this->physicalStructureInfo[$physSeq[0]]['dmdId'] = (isset($physNode[0]['DMDID']) ? (string) $physNode[0]['DMDID'] : '');

                $this->physicalStructureInfo[$physSeq[0]]['order'] = (isset($physNode[0]['ORDER']) ? (string) $physNode[0]['ORDER'] : '');

                $this->physicalStructureInfo[$physSeq[0]]['label'] = (isset($physNode[0]['LABEL']) ? (string) $physNode[0]['LABEL'] : '');

                $this->physicalStructureInfo[$physSeq[0]]['orderlabel'] = (isset($physNode[0]['ORDERLABEL']) ? (string) $physNode[0]['ORDERLABEL'] : '');

                $this->physicalStructureInfo[$physSeq[0]]['type'] = (string) $physNode[0]['TYPE'];

                $this->physicalStructureInfo[$physSeq[0]]['contentIds'] = (isset($physNode[0]['CONTENTIDS']) ? (string) $physNode[0]['CONTENTIDS'] : '');

                // Get the file representations from fileSec node.
                foreach ($physNode[0]->children('http://www.loc.gov/METS/')->fptr as $fptr) {

                    // Check if file has valid @USE attribute.
                    if (!empty($fileUse[(string) $fptr->attributes()->FILEID])) {

                        $this->physicalStructureInfo[$physSeq[0]]['files'][$fileUse[(string) $fptr->attributes()->FILEID]] = (string) $fptr->attributes()->FILEID;

                    }

                }

                // Build the physical elements' array from the physical structMap node.
                foreach ($elementNodes as $elementNode) {

                    $elements[(int) $elementNode['ORDER']] = (string) $elementNode['ID'];

                    $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['id'] = (string) $elementNode['ID'];

                    $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['dmdId'] = (isset($elementNode['DMDID']) ? (string) $elementNode['DMDID'] : '');

                    $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['order'] = (isset($elementNode['ORDER']) ? (string) $elementNode['ORDER'] : '');

                    $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['label'] = (isset($elementNode['LABEL']) ? (string) $elementNode['LABEL'] : '');

                    $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['orderlabel'] = (isset($elementNode['ORDERLABEL']) ? (string) $elementNode['ORDERLABEL'] : '');

                    $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['type'] = (string) $elementNode['TYPE'];

                    $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['contentIds'] = (isset($elementNode['CONTENTIDS']) ? (string) $elementNode['CONTENTIDS'] : '');

                    // Get the file representations from fileSec node.
                    foreach ($elementNode->children('http://www.loc.gov/METS/')->fptr as $fptr) {

                        // Check if file has valid @USE attribute.
                        if (!empty($fileUse[(string) $fptr->attributes()->FILEID])) {

                            $this->physicalStructureInfo[$elements[(int) $elementNode['ORDER']]]['files'][$fileUse[(string) $fptr->attributes()->FILEID]] = (string) $fptr->attributes()->FILEID;

                        }

                    }

                }

                // Sort array by keys (= @ORDER).
                if (ksort($elements)) {

                    // Set total number of pages/tracks.
                    $this->numPages = count($elements);

                    // Merge and re-index the array to get nice numeric indexes.
                    $this->physicalStructure = array_merge($physSeq, $elements);

                }

            }

            $this->physicalStructureLoaded = TRUE;

        }

        return $this->physicalStructure;

    }

    /**
     * This gives an array of the document's physical structure metadata
     *
     * @access	protected
     *
     * @return	array		Array of elements' type, label and file representations ordered by @ID attribute
     */
    protected function _getPhysicalStructureInfo() {

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
     * @access	protected
     *
     * @return	integer		The PID of the document or zero if not in database
     */
    protected function _getPid() {

        return $this->pid;

    }

    /**
     * This returns $this->ready via __get()
     *
     * @access	protected
     *
     * @return	boolean		Is the document instantiated successfully?
     */
    protected function _getReady() {

        return $this->ready;

    }

    /**
     * This returns $this->recordId via __get()
     *
     * @access	protected
     *
     * @return	mixed		The METS file's record identifier
     */
    protected function _getRecordId() {

        return $this->recordId;

    }

    /**
     * This returns $this->rootId via __get()
     *
     * @access	protected
     *
     * @return	integer		The UID of the root document or zero if not applicable
     */
    protected function _getRootId() {

        if (!$this->rootIdLoaded) {

            if ($this->parentId) {

                $parent = self::getInstance($this->parentId, $this->pid);

                $this->rootId = $parent->rootId;

            }

            $this->rootIdLoaded = TRUE;

        }

        return $this->rootId;

    }

    /**
     * This returns the smLinks between logical and physical structMap
     *
     * @access	protected
     *
     * @return	array		The links between logical and physical nodes
     */
    protected function _getSmLinks() {

        if (!$this->smLinksLoaded) {

            $smLinks = $this->mets->xpath('./mets:structLink/mets:smLink');

            foreach ($smLinks as $smLink) {

                $this->smLinks['l2p'][(string) $smLink->attributes('http://www.w3.org/1999/xlink')->from][] = (string) $smLink->attributes('http://www.w3.org/1999/xlink')->to;

                $this->smLinks['p2l'][(string) $smLink->attributes('http://www.w3.org/1999/xlink')->to][] = (string) $smLink->attributes('http://www.w3.org/1999/xlink')->from;

            }

            $this->smLinksLoaded = TRUE;

        }

        return $this->smLinks;

    }

    /**
     * This builds an array of the document's logical structure
     *
     * @access	protected
     *
     * @return	array		Array of structure nodes' id, label, type and physical page indexes/mptr link with original hierarchy preserved
     */
    protected function _getTableOfContents() {

        // Is there no logical structure array yet?
        if (!$this->tableOfContentsLoaded) {

            // Get all logical structures.
            $this->getLogicalStructure('', TRUE);

            $this->tableOfContentsLoaded = TRUE;

        }

        return $this->tableOfContents;

    }

    /**
     * This returns the document's thumbnail location
     *
     * @access	protected
     *
     * @param	boolean		$forceReload: Force reloading the thumbnail instead of returning the cached value
     *
     * @return	string		The document's thumbnail location
     */
    protected function _getThumbnail($forceReload = FALSE) {

        if (!$this->thumbnailLoaded || $forceReload) {

            // Retain current PID.
            $cPid = ($this->cPid ? $this->cPid : $this->pid);

            if (!$cPid) {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->_getThumbnail()] Invalid PID "'.$cPid.'" for structure definitions', self::$extKey, SYSLOG_SEVERITY_ERROR);

                }

                $this->thumbnailLoaded = TRUE;

                return $this->thumbnail;

            }

            // Load extension configuration.
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

            if (empty($extConf['fileGrpThumbs'])) {

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->_getThumbnail()] No fileGrp for thumbnails specified', self::$extKey, SYSLOG_SEVERITY_WARNING);

                }

                $this->thumbnailLoaded = TRUE;

                return $this->thumbnail;

            }

            $strctId = $this->_getToplevelId();

            $metadata = $this->getTitledata($cPid);

            // Get structure element to get thumbnail from.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_structures.thumbnail AS thumbnail',
                'tx_dlf_structures',
                'tx_dlf_structures.pid='.intval($cPid).' AND tx_dlf_structures.index_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($metadata['type'][0], 'tx_dlf_structures').tx_dlf_helper::whereClause('tx_dlf_structures'),
                '',
                '',
                '1'
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

                $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

                // Get desired thumbnail structure if not the toplevel structure itself.
                if (!empty($resArray['thumbnail'])) {

                    $strctType = tx_dlf_helper::getIndexName($resArray['thumbnail'], 'tx_dlf_structures', $cPid);

                    // Check if this document has a structure element of the desired type.
                    $strctIds = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@TYPE="'.$strctType.'"]/@ID');

                    if (!empty($strctIds)) {

                        $strctId = (string) $strctIds[0];

                    }

                }

                // Load smLinks.
                $this->_getSmLinks();

                // Get thumbnail location.
                if ($this->_getPhysicalStructure() && !empty($this->smLinks['l2p'][$strctId])) {

                    $this->thumbnail = $this->getFileLocation($this->physicalStructureInfo[$this->smLinks['l2p'][$strctId][0]]['files'][$extConf['fileGrpThumbs']]);

                } else {

                    $this->thumbnail = $this->getFileLocation($this->physicalStructureInfo[$this->physicalStructure[1]]['files'][$extConf['fileGrpThumbs']]);

                }

            } elseif (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->_getThumbnail()] No structure of type "'.$metadata['type'][0].'" found in database', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            $this->thumbnailLoaded = TRUE;

        }

        return $this->thumbnail;

    }

    /**
     * This returns the ID of the toplevel logical structure node
     *
     * @access	protected
     *
     * @return	string		The logical structure node's ID
     */
    protected function _getToplevelId() {

        if (empty($this->toplevelId)) {

            // Get all logical structure nodes with metadata, but without associated METS-Pointers.
            if (($divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID and not(./mets:mptr)]'))) {

                // Load smLinks.
                $this->_getSmLinks();

                foreach ($divs as $div) {

                    $id = (string) $div['ID'];

                    // Are there physical structure nodes for this logical structure?
                    if (array_key_exists($id, $this->smLinks['l2p'])) {

                        // Yes. That's what we're looking for.
                        $this->toplevelId = $id;

                        break;

                    } elseif (empty($this->toplevelId)) {

                        // No. Remember this anyway, but keep looking for a better one.
                        $this->toplevelId = $id;

                    }

                }

            }

        }

        return $this->toplevelId;

    }

    /**
     * This returns $this->uid via __get()
     *
     * @access	protected
     *
     * @return	mixed		The UID or the URL of the document
     */
    protected function _getUid() {

        return $this->uid;

    }

    /**
     * This sets $this->cPid via __set()
     *
     * @access	protected
     *
     * @param	integer		$value: The new PID for the metadata definitions
     *
     * @return	void
     */
    protected function _setCPid($value) {

        $this->cPid = max(intval($value), 0);

    }

    /**
     * This magic method is invoked each time a clone is called on the object variable
     * (This method is defined as private/protected because singleton objects should not be cloned)
     *
     * @access	protected
     *
     * @return	void
     */
    protected function __clone() {}

    /**
     * This is a singleton class, thus the constructor should be private/protected
     * (Get an instance of this class by calling tx_dlf_document::getInstance())
     *
     * @access	protected
     *
     * @param	integer		$uid: The UID of the document to parse or URL to XML file
     * @param	integer		$pid: If > 0, then only document with this PID gets loaded
     *
     * @return	void
     */
    protected function __construct($uid, $pid) {

        // Prepare to check database for the requested document.
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {

            $whereClause = 'tx_dlf_documents.uid='.intval($uid).tx_dlf_helper::whereClause('tx_dlf_documents');

        } else {

            // Try to load METS file.
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($uid) && $this->load($uid)) {

                // Initialize core METS object.
                $this->init();

                if ($this->mets !== NULL) {

                    // Cast to string for safety reasons.
                    $location = (string) $uid;

                    // Check for METS object @ID.
                    if (!empty($this->mets['OBJID'])) {

                        $this->recordId = (string) $this->mets['OBJID'];

                    }

                    // Get hook objects.
                    $hookObjects = tx_dlf_helper::getHookObjects('common/class.tx_dlf_document.php');

                    // Apply hooks.
                    foreach ($hookObjects as $hookObj) {

                        if (method_exists($hookObj, 'construct_postProcessRecordId')) {

                            $hookObj->construct_postProcessRecordId($this->xml, $this->recordId);

                        }

                    }

                } else {

                    // No METS part found.
                    return;

                }

            } else {

                // Loading failed.
                return;

            }

            if (!empty($location) && !empty($this->recordId)) {

                // Try to match record identifier or location (both should be unique).
                $whereClause = '(tx_dlf_documents.location='.$GLOBALS['TYPO3_DB']->fullQuoteStr($location, 'tx_dlf_documents').' OR tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->recordId, 'tx_dlf_documents').')'.tx_dlf_helper::whereClause('tx_dlf_documents');

            } else {

                // Can't persistently identify document, don't try to match at all.
                $whereClause = '1=-1';

            }

        }

        // Check for PID if needed.
        if ($pid) {

            $whereClause .= ' AND tx_dlf_documents.pid='.intval($pid);

        }

        // Get document PID and location from database.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_documents.uid AS uid,tx_dlf_documents.pid AS pid,tx_dlf_documents.record_id AS record_id,tx_dlf_documents.partof AS partof,tx_dlf_documents.thumbnail AS thumbnail,tx_dlf_documents.location AS location',
            'tx_dlf_documents',
            $whereClause,
            '',
            '',
            '1'
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

            list ($this->uid, $this->pid, $this->recordId, $this->parentId, $this->thumbnail, $this->location) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

            $this->thumbnailLoaded = TRUE;

            // Load XML file if necessary...
            if ($this->mets === NULL && $this->load($this->location)) {

                // ...and set some basic properties.
                $this->init();

            }

            // Do we have a METS object now?
            if ($this->mets !== NULL) {

                // Set new location if necessary.
                if (!empty($location)) {

                    $this->location = $location;

                }

                // Document ready!
                $this->ready = TRUE;

            }

        } elseif ($this->mets !== NULL) {

            // Set location as UID for documents not in database.
            $this->uid = $location;

            $this->location = $location;

            // Document ready!
            $this->ready = TRUE;

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->__construct('.$uid.', '.$pid.')] No document with UID "'.$uid.'" found or document not accessible', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

        }

    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access	public
     *
     * @param	string		$var: Name of variable to get
     *
     * @return	mixed		Value of $this->$var
     */
    public function __get($var) {

        $method = '_get'.ucfirst($var);

        if (!property_exists($this, $var) || !method_exists($this, $method)) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->__get('.$var.')] There is no getter function for property "'.$var.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

            }

            return;

        } else {

            return $this->$method();

        }

    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access	public
     *
     * @param	string		$var: Name of variable to set
     * @param	mixed		$value: New value of variable
     *
     * @return	void
     */
    public function __set($var, $value) {

        $method = '_set'.ucfirst($var);

        if (!property_exists($this, $var) || !method_exists($this, $method)) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->__set('.$var.', '.$value.')] There is no setter function for property "'.$var.'"', self::$extKey, SYSLOG_SEVERITY_WARNING);

            }

        } else {

            $this->$method($value);

        }

    }

    /**
     * This magic method is executed prior to any serialization of the object
     * @see __wakeup()
     *
     * @access	public
     *
     * @return	array		Properties to be serialized
     */
    public function __sleep() {

        // SimpleXMLElement objects can't be serialized, thus save the XML as string for serialization
        $this->asXML = $this->xml->asXML();

        return array ('uid', 'pid', 'recordId', 'parentId', 'asXML');

    }

    /**
     * This magic method is used for setting a string value for the object
     *
     * @access	public
     *
     * @return	string		String representing the METS object
     */
    public function __toString() {

        $xml = new DOMDocument('1.0', 'utf-8');

        $xml->appendChild($xml->importNode(dom_import_simplexml($this->mets), TRUE));

        $xml->formatOutput = TRUE;

        return $xml->saveXML();

    }

    /**
     * This magic method is executed after the object is deserialized
     * @see __sleep()
     *
     * @access	public
     *
     * @return	void
     */
    public function __wakeup() {

        // Turn off libxml's error logging.
        $libxmlErrors = libxml_use_internal_errors(TRUE);

        // Reload XML from string.
        $xml = @simplexml_load_string($this->asXML);

        // Reset libxml's error logging.
        libxml_use_internal_errors($libxmlErrors);

        if ($xml !== FALSE) {

            $this->asXML = '';

            $this->xml = $xml;

            // Rebuild the unserializable properties.
            $this->init();

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_document->__wakeup()] Could not load XML after deserialization', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

        }

    }

}
