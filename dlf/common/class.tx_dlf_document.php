<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Document class 'tx_dlf_document' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_document {

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
	 * @var string
	 * @access public
	 */
	public $extKey = 'dlf';

	/**
	 * This holds the configuration for all supported metadata encodings
	 * @see loadFormats()
	 *
	 * @var	array
	 * @access protected
	 */
	protected $formats = array (
		'OTHER' => array (
			'DVRIGHTS' => array (
				'rootElement' => 'rights',
				'namespaceURI' => 'http://dfg-viewer.de/',
			),
			'DVLINKS' => array (
				'rootElement' => 'links',
				'namespaceURI' => 'http://dfg-viewer.de/',
			)
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
	 * This holds the hook objects for this class
	 *
	 * @var	array
	 * @access protected
	 */
	protected $hookObjects = array ();

	/**
	 * Is the hook objects array loaded?
	 * @see $hookObjects
	 *
	 * @var	boolean
	 * @access protected
	 */
	protected $hookObjectsLoaded = FALSE;

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
	 * @var integer
	 * @access protected
	 */
	protected $numPages = 0;

	/**
	 * This holds the UID of the parent document or zero if not multi-volumed
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $parentid = 0;

	/**
	 * This holds the physical pages
	 *
	 * @var	array
	 * @access protected
	 */
	protected $physicalPages = array ();

	/**
	 * This holds the physical pages' metadata
	 *
	 * @var	array
	 * @access protected
	 */
	protected $physicalPagesInfo = array ();

	/**
	 * Are the physical pages loaded?
	 * @see $physicalPages
	 *
	 * @var	boolean
	 * @access protected
	 */
	protected $physicalPagesLoaded = FALSE;

	/**
	 * This holds the PID of the document or zero if not in database
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $pid = 0;

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
	protected $recordid;

	/**
	 * This holds the singleton object of each document with its UID as array key
	 *
	 * @var	array(tx_dlf_document)
	 * @access protected
	 */
	protected static $registry = array ();

	/**
	 * This holds the smLinks between logical and physical structMap
	 *
	 * @var array
	 * @access protected
	 */
	protected $smLinks = array ('l2p' => array (), 'p2l' => array ());

	/**
	 * Are the smLinks loaded?
	 * @see $smLinks
	 *
	 * @var boolean
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
	 * This gets the location of a file representing a physical page
	 *
	 * @access	public
	 *
	 * @param	string		$id: The @ID attribute of the file node
	 *
	 * @return	string		The file's location as URL
	 */
	public function getFileLocation($id) {

		if (($_location = $this->mets->xpath('./mets:fileSec/mets:fileGrp/mets:file[@ID="'.$id.'"]/mets:FLocat[@LOCTYPE="URL"]'))) {

			return (string) $_location[0]->attributes('http://www.w3.org/1999/xlink')->href;

		} else {

			trigger_error('There is no file node with @ID '.$id, E_USER_WARNING);

			return '';

		}

	}

	/**
	 * This gets the registered hook objects for this class.
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function getHookObjects() {

		if (!$this->hookObjectsLoaded && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/common/class.tx_dlf_document.php']['hookClass'])) {

			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/common/class.tx_dlf_document.php']['hookClass'] as $_classRef) {

				$this->hookObjects[] = t3lib_div::getUserObj($_classRef);

			}

			$this->hookObjectsLoaded = TRUE;

		}

	}

	/**
	 * This is a singleton class, thus instances must be created by this method
	 *
	 * @access	public
	 *
	 * @param	mixed		$uid: The unique identifier of the document to parse or URL of XML file
	 * @param	integer		$pid: If > 0, then only document with this PID gets loaded
	 * @param	boolean		$forceReload: Force reloading the document instead of returning the cached instance
	 *
	 * @return	tx_dlf_document		Instance of this class
	 */
	public static function getInstance($uid, $pid = 0, $forceReload = FALSE) {

		// Sanitize input.
		$pid = max(intval($pid), 0);

		if (!$forceReload && is_object(self::$registry[$uid]) && self::$registry[$uid] instanceof self) {

			// Check if instance has given PID.
			if (($pid && self::$registry[$uid]->pid == $pid) || !$pid) {

				// Return singleton instance if available.
				return self::$registry[$uid];

			}

		} elseif (!$forceReload) {

			// Check the user's session...
			$sessionData = tx_dlf_helper::loadFromSession(get_called_class());

			if (is_object($sessionData[$uid]) && $sessionData[$uid] instanceof self) {

				// Check if instance has given PID.
				if (($pid && $sessionData[$uid]->pid == $pid) || !$pid) {

					// ...and restore registry.
					self::$registry[$uid] = $sessionData[$uid];

					return self::$registry[$uid];

				}

			}

		}

		// Create new instance...
		$instance = new self($uid, $pid);

		// ...and save it to registry.
		if ($instance->ready) {

			self::$registry[$instance->uid] = $instance;

			// Load extension configuration
			$_extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

			// Save document to session if caching is enabled.
			if (!empty($_extConf['caching'])) {

				tx_dlf_helper::saveToSession(self::$registry, get_class($instance));

			}

		}

		// Return new instance.
		return $instance;

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

		// Extract identity information.
		$details = array ();

		$details['id'] = $attributes['ID'];

		$details['dmdId'] = (isset($attributes['DMDID']) ?  $attributes['DMDID'] : '');

		$details['label'] = (isset($attributes['LABEL']) ? $attributes['LABEL'] : '');

		$details['volume'] = '';

		if (empty($details['label']) && $details['id'] == $this->_getToplevelId()) {

			$metadata = $this->getMetadata($details['id']);

			if (!empty($metadata['volume'][0])) {

				$details['volume'] = $metadata['volume'][0];

			}

		}

		$details['pagination'] = '';

		$details['type'] = $attributes['TYPE'];

		// Get the physical page or external file this structure element is pointing at.
		$details['points'] = '';

		// Is there a mptr node?
		if (!empty($structure->children('http://www.loc.gov/METS/')->mptr)) {

			// Yes. Get the file reference.
			$details['points'] = (string) $structure->children('http://www.loc.gov/METS/')->mptr[0]->attributes('http://www.w3.org/1999/xlink')->href;

		// Are there any physical pages and is this logical unit linked to at least one of them?
		} elseif ($this->_getPhysicalPages() && array_key_exists($details['id'], $this->smLinks['l2p'])) {

			$details['points'] = max(intval(array_search($this->smLinks['l2p'][$details['id']][0], $this->physicalPages, TRUE)), 1);

			// Get page number of the first page related to this structure element.
			$details['pagination'] = $this->physicalPagesInfo[$id]['label'];

		// Is this the toplevel structure?
		} elseif ($details['id'] == $this->_getToplevelId()) {

			// Yes. Point to itself.
			$details['points'] = 1;

		}

		// Keep for later usage.
		$this->logicalUnits[$details['id']] = $details;

		// Walk the structure recursively? And are there any children of the current element?
		if ($recursive && !empty($structure->children('http://www.loc.gov/METS/')->div)) {

			$details['children'] = array ();

			foreach ($structure->children('http://www.loc.gov/METS/')->div as $child) {

				// Repeat for all children.
				$details['children'][] = $this->getLogicalStructureInfo($child, $recursive);

			}

		}

		return $details;

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
	public function getLogicalStructure($id = '', $recursive = FALSE) {

		// Is the requested logical unit already loaded?
		if (!$recursive && !empty($id) && !empty($this->logicalUnits[$id])) {

			// Yes. Return it.
			return $this->logicalUnits[$id];

		} elseif (!empty($id)) {

			// Get specified logical unit.
			$div = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]');

		} elseif ($recursive) {

			// Get all logical units at top level.
			$div = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div');

		}

		if (!empty($div)) {

			// Load smLinks.
			$this->_getSmLinks();

			foreach ($div as $structure) {

				// Get logical unit's details.
				$this->tableOfContents[] = $this->getLogicalStructureInfo($structure, $recursive);

			}

		}

		if (!empty($this->logicalUnits[$id])) {

			return $this->logicalUnits[$id];

		} else {

//			trigger_error('There is no logical structure node with @ID '.$id, E_USER_WARNING);

			return array ();

		}

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

		// Make sure $cPid is a non-negative integer.
		$cPid = max(intval($cPid), 0);

		// If $cPid is not given, try to get it elsewhere.
		if (!$cPid && ($this->cPid || $this->pid)) {

			// Retain current PID.
			$cPid = ($this->cPid ? $this->cPid : $this->pid);

		} elseif (!$cPid) {

			trigger_error('Invalid PID ('.$cPid.') for metadata definitions', E_USER_WARNING);

			return array ();

		}

		// Get metadata from parsed metadata array if available.
		if (!empty($this->metadataArray[$id]) && $this->metadataArray[0] == $cPid) {

			return $this->metadataArray[$id];

		}

		// Initialize metadata array with empty values.
		$_metadata = array (
			'title' => array (),
			'title_sorting' => array (),
			'author' => array (),
			'author_sorting' => array (),
			'place' => array (),
			'place_sorting' => array (),
			'year' => array (),
			'year_sorting' => array (),
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
		);

		// Get the logical structure node's DMDID.
		if (!empty($this->logicalUnits[$id])) {

			$_dmdId = $this->logicalUnits[$id]['dmdId'];

		} else {

			$_dmdId = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]/@DMDID');

			$_dmdId = (string) $_dmdId[0];

		}

		if (!empty($_dmdId)) {

			// Load available metadata formats and dmdSecs.
			$this->loadFormats();

			$this->_getDmdSec();

			// Is this metadata format supported?
			if (!empty($this->formats[$this->dmdSec[$_dmdId]['type']]['class'])) {

				$_class = $this->formats[$this->dmdSec[$_dmdId]['type']]['class'];

			} elseif (!empty($this->formats['OTHER'][$this->dmdSec[$_dmdId]['type']]['class'])) {

				$_class = $this->formats['OTHER'][$this->dmdSec[$_dmdId]['type']]['class'];

			} else {

				trigger_error('Unsupported metadata format or dmdSec with @ID '.$_dmdId.' not found', E_USER_WARNING);

				return array ();

			}

			// Get the metadata from class.
			if (class_exists($_class) && ($obj = t3lib_div::makeInstance($_class)) instanceof tx_dlf_format) {

				$obj->extractMetadata($this->dmdSec[$_dmdId]['xml'], $_metadata);

			} else {

				trigger_error('Invalid class/method '.$_class.'->extractMetadata()', E_USER_ERROR);

				return array ();

			}

			// Get the structure's type.
			if (!empty($this->logicalUnits[$id])) {

				$_metadata['type'] = array ($this->logicalUnits[$id]['type']);

			} else {

				$_struct = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]/@TYPE');

				$_metadata['type'] = array ((string) $_struct[0]);

			}

			// Get the additional metadata from database.
			$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.xpath AS xpath,tx_dlf_metadata.xpath_sorting AS xpath_sorting,tx_dlf_metadata.is_sortable AS is_sortable,tx_dlf_metadata.default_value AS default_value',
				'tx_dlf_metadata,tx_dlf_formats',
				'tx_dlf_metadata.pid='.$cPid.' AND ((tx_dlf_metadata.encoded=tx_dlf_formats.uid AND tx_dlf_formats.type='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->dmdSec[$_dmdId]['type'], 'tx_dlf_formats').') OR tx_dlf_metadata.encoded=0)'.tx_dlf_helper::whereClause('tx_dlf_metadata', TRUE).tx_dlf_helper::whereClause('tx_dlf_formats'),
				'',
				'',
				''
			);

			// We need a DOMDocument here, because SimpleXML doesn't support XPath functions properly.
			$_domNode = dom_import_simplexml($this->dmdSec[$_dmdId]['xml']);

			$_domXPath = new DOMXPath($_domNode->ownerDocument);

			$this->registerNamespaces($_domXPath);

			// OK, now make the XPath queries.
			while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

				// Set metadata field's value(s).
				if ($resArray['xpath'] && ($_values = $_domXPath->evaluate($resArray['xpath'], $_domNode))) {

					if ($_values instanceof DOMNodeList && $_values->length > 0) {

						$_metadata[$resArray['index_name']] = array ();

						foreach ($_values as $_value) {

							$_metadata[$resArray['index_name']][] = trim((string) $_value->nodeValue);

						}

					} elseif (!($_values instanceof DOMNodeList)) {

						$_metadata[$resArray['index_name']] = array (trim((string) $_values));

					}

				}

				// Set default value if applicable.
				if (empty($_metadata[$resArray['index_name']][0]) && $resArray['default_value']) {

					$_metadata[$resArray['index_name']] = array ($resArray['default_value']);

				}

				// Set sorting value if applicable.
				if (!empty($_metadata[$resArray['index_name']]) && $resArray['is_sortable']) {

					if ($resArray['xpath_sorting'] && ($_values = $_domXPath->evaluate($resArray['xpath_sorting'], $_domNode))) {

						if ($_values instanceof DOMNodeList && $_values->length > 0) {

							$_metadata[$resArray['index_name'].'_sorting'][0] = trim((string) $_values->item(0)->nodeValue);

						} elseif (!($_values instanceof DOMNodeList)) {

							$_metadata[$resArray['index_name'].'_sorting'][0] = trim((string) $_values);

						}

					}

					if (empty($_metadata[$resArray['index_name'].'_sorting'][0])) {

						$_metadata[$resArray['index_name'].'_sorting'][0] = $_metadata[$resArray['index_name']][0];

					}

				}

			}

			// Set title to empty string if not present.
			if (empty($_metadata['title'][0])) {

				$_metadata['title'][0] = '';

				$_metadata['title_sorting'][0] = '';

			}

		} else {

			// There is no dmdSec for this structure node.
			return array ();

		}

		return $_metadata;

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
				if ($recursive && empty($title) && intval($partof)) {

					$title = self::getTitle($partof, TRUE);

				}

			} else {

				trigger_error('No document with UID '.$uid.' found', E_USER_WARNING);

			}

		} else {

			trigger_error('No UID given for document', E_USER_ERROR);

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

		// Set record identifier for METS file.
		array_unshift($titledata['record_id'], $this->recordid);

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

		// Get hook objects for this class.
		$this->getHookObjects();

		// Get METS node from XML file.
		$this->registerNamespaces($this->xml);

		$_mets = $this->xml->xpath('//mets:mets');

		if ($_mets) {

			$this->mets = $_mets[0];

			// Register namespaces.
			$this->registerNamespaces($this->mets);

			// Instantiation successful.
			$this->ready = TRUE;

		} else {

			trigger_error('No valid METS part found in document with UID '.$this->uid, E_USER_ERROR);

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
		if (t3lib_div::isValidUrl($location)
			// There is a bug in filter_var($var, FILTER_VALIDATE_URL) in PHP < 5.3.3 which causes
			// the function to validate URLs containing whitespaces and invalidate URLs containing
			// hyphens. (see https://bugs.php.net/bug.php?id=51192)
			|| version_compare(phpversion(), '5.3.3', '<')) {

			// Load extension configuration
			$_extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

			// Set user-agent to identify self when fetching XML data.
			if (!empty($_extConf['useragent'])) {

				@ini_set('user_agent', $_extConf['useragent']);

			}

			// Load XML from file...
			$_libxmlErrors = libxml_use_internal_errors(TRUE);

			$_xml = @simplexml_load_file($location);

			libxml_use_internal_errors($_libxmlErrors);

			// ...and set some basic properties.
			if ($_xml !== FALSE) {

				$this->xml = $_xml;

				return TRUE;

			} else {

				trigger_error('Could not load XML file from '.$location, E_USER_ERROR);
				// TODO: libxml_get_errors() || libxml_get_last_error() || libxml_clear_errors()

			}

		} else {

			trigger_error('File location "'.$location.'" is not a valid URL', E_USER_ERROR);

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
			$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_formats.type AS type,tx_dlf_formats.other_type AS other_type,tx_dlf_formats.root AS root,tx_dlf_formats.namespace AS namespace,tx_dlf_formats.class AS class',
				'tx_dlf_formats',
				'tx_dlf_formats.pid=0'.tx_dlf_helper::whereClause('tx_dlf_formats'),
				'',
				'',
				''
			);

			while ($_resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

				if (!$_resArray['other_type']) {

					// Update format registry.
					$this->formats[$_resArray['type']] = array (
						'rootElement' => $_resArray['root'],
						'namespaceURI' => $_resArray['namespace'],
						'class' => $_resArray['class']
					);

				} else {

					// Update format registry.
					$this->formats['OTHER'][$_resArray['type']] = array (
						'rootElement' => $_resArray['root'],
						'namespaceURI' => $_resArray['namespace'],
						'class' => $_resArray['class']
					);

				}

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

			$_method = 'registerXPathNamespace';

		} elseif ($obj instanceof DOMXPath) {

			$_method = 'registerNamespace';

		} else {

			trigger_error('No SimpleXMLElement or DOMXPath object given', E_USER_WARNING);

			return;

		}

		// Register mandatory METS' and XLINK's namespaces.
		$obj->$_method('mets', 'http://www.loc.gov/METS/');

		// This one can become a problem, because MODS uses its own custom XLINK schema.
		// @see http://comments.gmane.org/gmane.comp.text.mods/1126
		$obj->$_method('xlink', 'http://www.w3.org/1999/xlink');

		// Register metadata format's namespaces.
		foreach ($this->formats as $enc => $conf) {

			if ($enc != 'OTHER') {

				$obj->$_method(strtolower($enc), $conf['namespaceURI']);

			} else {

				foreach ($conf as $otherEnc => $otherConf) {

					$obj->$_method(strtolower($otherEnc), $otherConf['namespaceURI']);

				}

			}

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

		if (TYPO3_MODE !== 'BE') {

			trigger_error('Saving documents is only allowed in the backend!', E_USER_ERROR);

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

			trigger_error('Invalid PID ('.$pid.') given to save document', E_USER_WARNING);

			return FALSE;

		}

		// Set PID for metadata definitions.
		$this->cPid = $pid;

		// Set location if inserting new document.
		$location = '';

		if (!t3lib_div::testInt($this->uid)) {

			$location = $this->uid;

		}

		// Set UID placeholder if not updating existing record.
		if ($pid != $this->pid) {

			$this->uid = uniqid('NEW');

		}

		// Get metadata array.
		$metadata = $this->getTitledata($pid);

		// Check for record identifier.
		if (empty($metadata['record_id'][0])) {

			trigger_error('No record identifier found to avoid duplication', E_USER_WARNING);

			return FALSE;

		}

		// Get UID for structure type.
		$structure = 0;

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

			trigger_error('Could not identify structure type', E_USER_ERROR);

			return FALSE;

		}

		$metadata['type'][0] = $structure;

		// Get UIDs for collections.
		$collections = array ();

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_collections.index_name AS index_name,tx_dlf_collections.uid AS uid',
			'tx_dlf_collections',
			'tx_dlf_collections.pid='.intval($pid).' AND tx_dlf_collections.fe_cruser_id=0'.tx_dlf_helper::whereClause('tx_dlf_collections'),
			'',
			'',
			''
		);

		for ($i = 0, $j = $GLOBALS['TYPO3_DB']->sql_num_rows($result); $i < $j; $i++) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			$_collUid[$resArray['index_name']] = $resArray['uid'];

		}

		foreach ($metadata['collection'] as $collection) {

			if (!empty($_collUid[$collection])) {

				// Add existing collection's UID.
				$collections[] = $_collUid[$collection];

			} else {

				// Insert new collection.
				$_collNewUid = uniqid('NEW');

				$_collData['tx_dlf_collections'][$_collNewUid] = array (
					'pid' => $pid,
					'label' => $collection,
					'index_name' => $collection,
					'oai_name' => $collection,
					'description' => '',
					'documents' => 0,
					'owner' => 0,
					'status' => 0,
				);

				$_substUid = tx_dlf_helper::processDB($_collData);

				// Prevent double insertion.
				unset ($_collData);

				// Add new collection's UID.
				$collections[] = $_substUid[$_collNewUid];

				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('flash.newCollection'), $collection, $_substUid[$_collNewUid])),
					$GLOBALS['LANG']->getLL('flash.attention', TRUE),
					t3lib_FlashMessage::INFO,
					TRUE
				);

				t3lib_FlashMessageQueue::addMessage($_message);

			}

		}

		// Preserve user-defined collections.
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_collections.uid AS uid',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_documents.pid='.intval($pid).' AND tx_dlf_collections.pid='.intval($pid).' AND tx_dlf_documents.uid='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->uid, 'tx_dlf_documents').' AND NOT tx_dlf_collections.fe_cruser_id=0',
			'',
			'',
			''
		);

		for ($i = 0, $j = $GLOBALS['TYPO3_DB']->sql_num_rows($result); $i < $j; $i++) {

			list ($collections[]) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

		}

		$metadata['collection'] = $collections;

		// Get UID for owner.
		$owner = 0;

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_libraries.uid AS uid',
			'tx_dlf_libraries',
			'tx_dlf_libraries.pid='.intval($pid).' AND tx_dlf_libraries.index_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($metadata['owner'][0], 'tx_dlf_libraries').tx_dlf_helper::whereClause('tx_dlf_libraries'),
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

			list ($owner) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

		} else {

			// Insert new library.
			$_libNewUid = uniqid('NEW');

			$_libData['tx_dlf_libraries'][$_libNewUid] = array (
				'pid' => $pid,
				'label' => $metadata['owner'][0],
				'index_name' => $metadata['owner'][0],
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

			$_substUid = tx_dlf_helper::processDB($_libData);

			// Add new library's UID.
			$owner = $_substUid[$_libNewUid];

			$_message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('flash.newLibrary'), $metadata['owner'][0], $_substUid[$_libNewUid])),
				$GLOBALS['LANG']->getLL('flash.attention', TRUE),
				t3lib_FlashMessage::INFO,
				TRUE
			);

			t3lib_FlashMessageQueue::addMessage($_message);

		}

		$metadata['owner'][0] = $owner;

		// Load table of contents.
		$this->_getTableOfContents();

		// Get UID of superior document.
		$partof = 0;

		if (!empty($this->tableOfContents[0]['points']) && !t3lib_div::testInt($this->tableOfContents[0]['points'])) {

			$superior = tx_dlf_document::getInstance($this->tableOfContents[0]['points']);

			if ($superior->ready) {

				if ($superior->pid != $pid) {

					$superior->save($pid, $core);

				}

				$partof = $superior->uid;

			}

		}

		// Get metadata for lists.
		$listed = array ();

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name',
			'tx_dlf_metadata',
			'tx_dlf_metadata.is_listed=1 AND tx_dlf_metadata.pid='.intval($pid).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			if (!empty($metadata[$resArray['index_name']])) {

				$listed[$resArray['index_name']] = $metadata[$resArray['index_name']];

			}

		}

		// Fill data array.
		$data['tx_dlf_documents'][$this->uid] = array (
			'pid' => $pid,
			'prod_id' => $metadata['prod_id'][0],
			'record_id' => $metadata['record_id'][0],
			'opac_id' => $metadata['opac_id'][0],
			'union_id' => $metadata['union_id'][0],
			'urn' => $metadata['urn'][0],
			'purl' => $metadata['purl'][0],
			'title' => $metadata['title'][0],
			'title_sorting' => $metadata['title_sorting'][0],
			'author' => $metadata['author'][0],
			'author_sorting' => $metadata['author_sorting'][0],
			'year' => $metadata['year'][0],
			'year_sorting' => $metadata['year_sorting'][0],
			'place' => $metadata['place'][0],
			'place_sorting' => $metadata['place_sorting'][0],
			'metadata' => json_encode($listed),
			'structure' => $metadata['type'][0],
			'partof' => $partof,
			'volume' => $metadata['volume'][0],
			'volume_sorting' => $metadata['volume_sorting'][0],
			'collections' => $metadata['collection'],
			'owner' => $metadata['owner'][0],
			'solrcore' => $core,
			'status' => 0,
		);

		if ($location) {

			$data['tx_dlf_documents'][$this->uid]['location'] = $location;

		}

		// Process data.
		$newIds = tx_dlf_helper::processDB($data);

		// Replace placeholder with actual UID.
		if (strpos($this->uid, 'NEW') === 0) {

			$this->uid = $newIds[$this->uid];

			$this->pid = $pid;

			$this->parentid = $partof;

		}

		// Add document to index.
		if ($core) {

			tx_dlf_indexing::add($this, $core);

		} else {

			trigger_error('Invalid UID for Solr core ('.$core.') given to index document', E_USER_NOTICE);

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
			$_dmdIds = $this->mets->xpath('./mets:dmdSec/@ID');

			foreach ($_dmdIds as $_dmdId) {

				$_type = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $_dmdId.'"]/mets:mdWrap/@MDTYPE');

				if ($_type && !empty($this->formats[(string) $_type[0]]) && $_type[0] != 'OTHER') {

					$_type = (string) $_type[0];

					$_xml = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $_dmdId.'"]/mets:mdWrap[@MDTYPE="'.$_type.'"]/mets:xmlData/'.strtolower($_type).':'.$this->formats[$_type]['rootElement']);

					if ($_xml) {

						$this->dmdSec[(string) $_dmdId]['type'] = $_type;

						$this->dmdSec[(string) $_dmdId]['xml'] = $_xml[0];

						$this->registerNamespaces($this->dmdSec[(string) $_dmdId]['xml']);

					}

				} elseif ($_type && $_type[0] == 'OTHER') {

					$_otherType = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $_dmdId.'"]/mets:mdWrap[@MDTYPE="OTHER"]/@OTHERMDTYPE');

					if ($_otherType && !empty($this->formats['OTHER'][(string) $_otherType[0]])) {

						$_otherType = (string) $_otherType[0];

						$_xml = $this->mets->xpath('./mets:dmdSec[@ID="'.(string) $_dmdId.'"]/mets:mdWrap[@MDTYPE="OTHER"][@OTHERMDTYPE="'.$_otherType.'"]/mets:xmlData/'.strtolower($_otherType).':'.$this->formats['OTHER'][$_otherType]['rootElement']);

						if ($_xml) {

							$this->dmdSec[(string) $_dmdId]['type'] = $_otherType;

							$this->dmdSec[(string) $_dmdId]['xml'] = $_xml[0];

							$this->registerNamespaces($this->dmdSec[(string) $_dmdId]['xml']);

						}

					}

				}

			}

			$this->dmdSecLoaded = TRUE;

		}

		return $this->dmdSec;

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

			trigger_error('No PID for metadata definitions found', E_USER_ERROR);

			return array ();

		}

		if (!$this->metadataArrayLoaded || $this->metadataArray[0] != $cPid) {

			// Get all logical structure nodes with metadata
			if (($_ids = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID]/@ID'))) {

				foreach ($_ids as $_id) {

					$this->metadataArray[(string) $_id] = $this->getMetadata((string) $_id, $cPid);

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
	 * @return	integer		The total number of pages
	 */
	protected function _getNumPages() {

		$this->_getPhysicalPages();

		return $this->numPages;

	}

	/**
	 * This returns $this->parentid via __get()
	 *
	 * @access	protected
	 *
	 * @return	integer		The UID of the parent document or zero if not applicable
	 */
	protected function _getParentid() {

		return $this->parentid;

	}

	/**
	 * This builds an array of the document's physical pages
	 *
	 * @access	protected
	 *
	 * @return	array		Array of pages' id, type, label and file representations ordered by @ORDER attribute
	 */
	protected function _getPhysicalPages() {

		// Is there no physical pages array yet?
		if (!$this->physicalPagesLoaded) {

			// Does the document have a structMap node of type "PHYSICAL"?
			$_pageNodes = $this->mets->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div[@TYPE="physSequence"]/mets:div[@TYPE="page"]');

			if ($_pageNodes) {

				$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

				$_useGrps = t3lib_div::trimExplode(',', $extConf['fileGrps']);

				// Yes. Get concordance of @FILEID and @USE attributes.
				$_fileUse = array ();

				$_fileGrps = $this->mets->xpath('./mets:fileSec/mets:fileGrp');

				foreach ($_fileGrps as $_fileGrp) {

					if (in_array((string) $_fileGrp['USE'], $_useGrps)) {

						foreach ($_fileGrp->children('http://www.loc.gov/METS/')->file as $_file) {

							$_fileUse[(string) $_file->attributes()->ID] = (string) $_fileGrp['USE'];

						}

					}

				}

				// Get the physical sequence's metadata.
				$_physNode = $this->mets->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div[@TYPE="physSequence"]');

				$_physSeq[0] = (string) $_physNode[0]['ID'];

				$this->physicalPagesInfo[$_physSeq[0]]['dmdId'] = (isset($_physNode[0]['DMDID']) ? (string) $_physNode[0]['DMDID'] : '');

				$this->physicalPagesInfo[$_physSeq[0]]['label'] = (isset($_physNode[0]['ORDERLABEL']) ? (string) $_physNode[0]['ORDERLABEL'] : '');

				$this->physicalPagesInfo[$_physSeq[0]]['type'] = (string) $_physNode[0]['TYPE'];

				// Get the file representations from fileSec node.
				foreach ($_physNode[0]->children('http://www.loc.gov/METS/')->fptr as $_fptr) {

					// Check if file has valid @USE attribute.
					if (!empty($_fileUse[(string) $_fptr->attributes()->FILEID])) {

						$this->physicalPagesInfo[$_physSeq[0]]['files'][strtolower($_fileUse[(string) $_fptr->attributes()->FILEID])] = (string) $_fptr->attributes()->FILEID;

					}

				}

				// Build the physical pages' array from the physical structMap node.
				foreach ($_pageNodes as $_pageNode) {

					$_pages[(int) $_pageNode['ORDER']] = (string) $_pageNode['ID'];

					$this->physicalPagesInfo[$_pages[(int) $_pageNode['ORDER']]]['dmdId'] = (isset($_pageNode['DMDID']) ? (string) $_pageNode['DMDID'] : '');

					$this->physicalPagesInfo[$_pages[(int) $_pageNode['ORDER']]]['label'] = (isset($_pageNode['ORDERLABEL']) ? (string) $_pageNode['ORDERLABEL'] : '');

					$this->physicalPagesInfo[$_pages[(int) $_pageNode['ORDER']]]['type'] = (string) $_pageNode['TYPE'];

					// Get the file representations from fileSec node.
					foreach ($_pageNode->children('http://www.loc.gov/METS/')->fptr as $_fptr) {

						// Check if file has valid @USE attribute.
						if (!empty($_fileUse[(string) $_fptr->attributes()->FILEID])) {

							$this->physicalPagesInfo[$_pages[(int) $_pageNode['ORDER']]]['files'][strtolower($_fileUse[(string) $_fptr->attributes()->FILEID])] = (string) $_fptr->attributes()->FILEID;

						}

					}

				}

				// Sort array by keys (= @ORDER).
				if (ksort($_pages)) {

					// Set total number of pages.
					$this->numPages = count($_pages);

					// Merge and re-index the array to get nice numeric indexes.
					$this->physicalPages = array_merge($_physSeq, $_pages);

				}

			}

			$this->physicalPagesLoaded = TRUE;

		}

		return $this->physicalPages;

	}

	/**
	 * This gives an array of the document's physical pages metadata
	 *
	 * @access	protected
	 *
	 * @return	array		Array of pages' type, label and file representations ordered by @ID attribute
	 */
	protected function _getPhysicalPagesInfo() {

		// Is there no physical pages array yet?
		if (!$this->physicalPagesLoaded) {

			// Build physical pages array.
			$this->_getPhysicalPages();

		}

		return $this->physicalPagesInfo;

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
	 * This returns $this->recordid via __get()
	 *
	 * @access	protected
	 *
	 * @return	mixed		The METS file's record identifier
	 */
	protected function _getRecordid() {

		return $this->recordid;

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
	 * This returns the ID of the toplevel logical structure node
	 *
	 * @access	protected
	 *
	 * @return	string		The logical structure node's ID
	 */
	protected function _getToplevelId() {

		if (empty($this->toplevelId)) {

			// Get all logical structure nodes with metadata.
			if (($divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID]'))) {

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
		if (t3lib_div::testInt($uid)) {

			$whereClause = 'tx_dlf_documents.uid='.intval($uid).tx_dlf_helper::whereClause('tx_dlf_documents');

		} else {

			// Cast to string for safety reasons.
			$location = (string) $uid;

			// Get record identifier to check with database.
			$_libxmlErrors = libxml_use_internal_errors(TRUE);

			$xml = @simplexml_load_file($location);

			libxml_use_internal_errors($_libxmlErrors);

			if ($xml !== FALSE) {

				$xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');

				$_objId = $xml->xpath('//mets:mets');

				if (!empty($_objId[0]['OBJID'])) {

					$this->recordid = (string) $_objId[0]['OBJID'];

				}

				// Check for post-processing hooks.
				$this->getHookObjects();

				foreach($this->hookObjects as $hookObj) {

					if (method_exists($hookObj, 'construct_postProcessRecordId')) {

						$this->recordid = $hookObj->construct_postProcessRecordId($xml, $this->recordid);

					}

				}

			}

			if (!empty($this->recordid)) {

				$whereClause = 'tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->recordid, 'tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_documents');

			} else {

				// There is no record identifier and there should be no hit in the database.
				$whereClause = '1=-1';

			}

		}

		// Check for PID if needed.
		if ($pid) {

			$whereClause .= ' AND tx_dlf_documents.pid='.$pid;

		}

		// Get document PID and location from database.
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_documents.uid AS uid,tx_dlf_documents.pid AS pid,tx_dlf_documents.record_id AS record_id,tx_dlf_documents.partof AS partof,tx_dlf_documents.location AS location',
			'tx_dlf_documents',
			$whereClause,
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result) > 0) {

			list ($this->uid, $this->pid, $this->recordid, $this->parentid, $location) = $GLOBALS['TYPO3_DB']->sql_fetch_row($_result);

			// Load XML file...
			if ($this->load($location)) {

				// ...and set some basic properties.
				$this->init();

			}

		} elseif (!empty($location)) {

			$this->uid = $location;

			// Load XML file...
			if ($this->load($location)) {

				// ...and set some basic properties.
				$this->init();

			}

		} else {

			trigger_error('There is no record with UID '.$uid.' or you are not allowed to access it', E_USER_ERROR);

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

		$_method = '_get'.ucfirst($var);

		if (!property_exists($this, $var) || !method_exists($this, $_method)) {

			trigger_error('There is no get function for property '.$var, E_USER_ERROR);

			return;

		} else {

			return $this->$_method();

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

		$_method = '_set'.ucfirst($var);

		if (!property_exists($this, $var) || !method_exists($this, $_method)) {

			trigger_error('There is no set function for property '.$var, E_USER_ERROR);

		} else {

			$this->$_method($value);

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

		return array ('uid', 'pid', 'parentid', 'asXML');

	}

	/**
	 * This magic method is used for setting a string value for the object
	 *
	 * @access	public
	 *
	 * @return	string		String representing the METS object
	 */
	public function __toString() {

		$_xml = new DOMDocument('1.0', 'utf-8');

		$_xml->appendChild($_xml->importNode(dom_import_simplexml($this->mets), TRUE));

		$_xml->formatOutput = TRUE;

		return $_xml->saveXML();

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

		$_libxmlErrors = libxml_use_internal_errors(TRUE);

		// Reload XML from string...
		$_xml = @simplexml_load_string($this->asXML);

		libxml_use_internal_errors($_libxmlErrors);

		if ($_xml !== FALSE) {

			$this->asXML = '';

			$this->xml = $_xml;

			// ...and rebuild the unserializable properties.
			$this->init();

		} else {

			trigger_error('Could not reload XML from session', E_USER_ERROR);
			// TODO: libxml_get_errors() || libxml_get_last_error() || libxml_clear_errors()

		}

	}

}

/* No xclasses allowed for this class!
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_document.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_document.php']);
}
*/

?>