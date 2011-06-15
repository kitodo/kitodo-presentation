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
	 * This holds the PID for the metadata definitions
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $mPid = 0;

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
	 * This holds the singleton object of each document with its UID as array key
	 *
	 * @var	array(tx_dlf_document)
	 * @access protected
	 */
	protected static $registry = array ();

	/**
	 * This holds the PID for the structure definitions
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $sPid = 0;

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
		self::$registry[$instance->uid] = $instance;

		// Load extension configuration
		$_extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

		// Save document to session if caching is enabled.
		if (!empty($_extConf['caching'])) {

			tx_dlf_helper::saveToSession(self::$registry, get_class($instance));

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

		// Is the requested logical unit already loaded?
		if (!$recursive && !empty($this->logicalUnits[$id])) {

			// Yes. Return it.
			return $this->logicalUnits[$id];

		} elseif (($_div = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]'))) {

			// Load physical pages.
			$this->_getPhysicalPages();

			$_struct = $_div[0];

			$this->registerNamespaces($_struct);

			// Extract identity information.
			$_details = array ();

			$_details['id'] = (string) $_struct['ID'];

			$_details['dmdId'] = (isset($_struct['DMDID']) ? (string) $_struct['DMDID'] : '');

			$_details['label'] = (isset($_struct['LABEL']) ? (string) $_struct['LABEL'] : '');

			$_details['pagination'] = '';

			$_details['type'] = (string) $_struct['TYPE'];

			// Get the physical page or external file this structure element is pointing at.
			$_details['points'] = '';

			// Is there a mptr node?
			if (($_mptr = $_struct->xpath('./mets:mptr[@LOCTYPE="URL"]'))) {

				// Yes. Get the file reference.
				$_details['points'] = (string) $_mptr[0]->attributes('http://www.w3.org/1999/xlink')->href;

			// Are there any physical pages?
			} elseif ($this->physicalPages) {

				// Yes. Get first physical page related to this structure element.
				if (($_smLink = $this->mets->xpath('./mets:structLink/mets:smLink[@xlink:from="'.(string) $_struct['ID'].'"]'))) {

					$_details['points'] = tx_dlf_helper::array_search_recursive($_smLink[0]->attributes('http://www.w3.org/1999/xlink')->to, $this->physicalPages);

					// Check if smLink points to the "physSequence" element (in which case it should point to the first image).
					$_details['points'] = max(intval($_details['points']), 1);

					// Get page number of the first page related to this structure element.
					$_details['pagination'] = $this->physicalPages[$_details['points']]['label'];

				} else {

					trigger_error('No physical element related to logical element with @ID '.(string) $_struct['ID'], E_USER_WARNING);

				}

			}

			// Keep for later usage.
			$this->logicalUnits[$id] = $_details;

			// Walk the structure recursively? And are there any children of the current element?
			if ($recursive && ($_children = $_struct->xpath('./mets:div/@ID'))) {

				$_details['children'] = array ();

				foreach ($_children as $_child) {

					// Repeat for all children.
					$_details['children'][] = $this->getLogicalStructure((string) $_child, TRUE);

				}

			}

			return $_details;

		} else {

			trigger_error('There is no logical structure node with @ID '.$id, E_USER_WARNING);

			return array ();

		}

	}

	/**
	 * This extracts all the metadata for a logical structure node
	 *
	 * @access	public
	 *
	 * @param	string		$id: The @ID attribute of the logical structure node
	 * @param	integer		$mPid: The PID for the metadata definitions
	 * 						(defaults to $this->mPid or $this->pid)
	 *
	 * @return	array		The logical structure node's parsed metadata array
	 */
	public function getMetadata($id, $mPid = 0) {

		// Set metadata definitions' PID.
		$mPid = ($mPid ? intval($mPid) : ($this->mPid ? $this->mPid : $this->pid));

		if (!$mPid) {

			trigger_error('No PID for metadata definitions found', E_USER_WARNING);

			return array ();

		}

		// Get metadata from parsed metadata array if available.
		if (!empty($this->metadataArray[$id]) && $this->metadataArray[0] == $mPid) {

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
			'volume_sorting' => array (),
			'collection' => array (),
			'owner' => array (),
		);

		// Get the logical structure node.
		$_structure = $this->getLogicalStructure($id);

		if (($_dmdId = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]/@DMDID'))) {

			// Load available metadata formats and dmdSecs.
			$this->loadFormats();

			$this->_getDmdSec();

			$_dmdId = (string) $_dmdId[0];

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

				exit;

			}

			// Get the structure.
			if (($_struct = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="'.$id.'"]/@TYPE'))) {

				$_metadata['type'] = array ((string) $_struct[0]);

			}

			// Get the additional metadata from database.
			$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.xpath AS xpath,tx_dlf_metadata.default_value AS default_value',
				'tx_dlf_metadata,tx_dlf_formats',
				'tx_dlf_metadata.pid='.$mPid.' AND ((tx_dlf_metadata.encoded=tx_dlf_formats.uid AND tx_dlf_formats.type='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->dmdSec[$_dmdId]['type'], 'tx_dlf_formats').') OR tx_dlf_metadata.encoded=0)'.tx_dlf_helper::whereClause('tx_dlf_metadata').tx_dlf_helper::whereClause('tx_dlf_formats'),
				'',
				'',
				''
			);

			while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

				if ($resArray['xpath'] && ($_values = $this->dmdSec[$_dmdId]['xml']->xpath($resArray['xpath']))) {

					$_metadata[$resArray['index_name']] = array ();

					foreach ($_values as $_value) {

						$_metadata[$resArray['index_name']][] = (string) $_value;

					}

				}

				if (empty($_metadata[$resArray['index_name']][0]) && $resArray['default_value']) {

					$_metadata[$resArray['index_name']] = array ($resArray['default_value']);

				}

			}

		} else {

			// There is no dmdSec for this structure node.
			return array ();

		}

		return $_metadata;

	}

	/**
	 * This extracts all the metadata for the toplevel logical structure node
	 *
	 * @access	public
	 *
	 * @param	integer		$mPid: The PID for the metadata definitions
	 *
	 * @return	array		The logical structure node's parsed metadata array
	 */
	public function getTitledata($mPid = 0) {

		return $this->getMetadata($this->getToplevelId(), $mPid);

	}

	/**
	 * This returns the ID of the toplevel logical structure node
	 *
	 * @access	public
	 *
	 * @return	string		The logical structure node's ID
	 */
	public function getToplevelId() {

		$id = '';

		// Get all logical structure nodes with metadata.
		if (($_divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID]'))) {

			foreach ($_divs as $_div) {

				$_id = (string) $_div['ID'];

				// Are there physical structure nodes for this logical structure?
				if ($this->mets->xpath('./mets:structLink/mets:smLink[@xlink:from="'.$_id.'"]')) {

					// Yes. That's what we're looking for.
					return $_id;

				} elseif (!$id) {

					// No. Remember this anyway, but keep looking for a better one.
					$id = $_id;

				}

			}

		}

		return $id;

	}

	/**
	 * This sets some basic class properties
	 *
	 * @access protected
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

		} else {

			trigger_error('No valid METS part found in document with UID '.$this->uid, E_USER_ERROR);

			exit;

		}

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
				'type,other_type,root,namespace,class',
				'tx_dlf_formats',
				'pid=0'.tx_dlf_helper::whereClause('tx_dlf_formats'),
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
	 * @param	SimpleXMLElement		&$obj: SimpleXMLElement object
	 *
	 * @return	void
	 */
	public function registerNamespaces(SimpleXMLElement &$obj) {

		// Register mandatory METS' and XLINK's namespaces.
		$obj->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');

		// This one can become a problem, because MODS uses its own custom XLINK schema.
		// @see http://comments.gmane.org/gmane.comp.text.mods/1126
		$obj->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');

		$this->loadFormats();

		// Register metadata format's namespaces.
		foreach ($this->formats as $enc => $conf) {

			if ($enc != 'OTHER') {

				$obj->registerXPathNamespace(strtolower($enc), $conf['namespaceURI']);

			} else {

				foreach ($conf as $otherEnc => $otherConf) {

					$obj->registerXPathNamespace(strtolower($otherEnc), $otherConf['namespaceURI']);

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
	public function save($pid = 0, $core = 1) {

		if (TYPO3_MODE !== 'BE') {

			trigger_error('Saving documents is only allowed in the backend!', E_USER_ERROR);

			exit;

		}

		// Make sure $pid is a non-negative integer.
		$pid = max(intval($pid), 0);

		// If $pid is not given, try to get it elsewhere.
		if (!$pid && $this->pid) {

			// Retain current PID.
			$pid = $this->pid;

		} elseif (!$pid) {

			trigger_error('No PID given to save document', E_USER_WARNING);

			return FALSE;

		}

		// Load table of contents.
		$this->_getTableOfContents();

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

		}

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

		for ($i = 0; $i < $GLOBALS['TYPO3_DB']->sql_num_rows($result); $i++) {

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
			'tx_dlf_documents.pid='.intval($pid).' AND tx_dlf_collections.pid='.intval($pid).' AND tx_dlf_documents.uid='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->uid, 'tx_dlf_documents').' AND NOT tx_dlf_collections.fe_cruser_id=0',
			'',
			'',
			''
		);

		for ($i = 0; $i < $GLOBALS['TYPO3_DB']->sql_num_rows($result); $i++) {

			list ($collections[]) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

		}

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

		// Get UID of superior document.
		$partof = 0;

		if (!empty($this->tableOfContents[0]['points']) && !t3lib_div::testInt($this->tableOfContents[0]['points'])) {

			$superior = tx_dlf_document::getInstance($this->tableOfContents[0]['points']);

			if ($superior->pid != $pid) {

				$superior->save($pid);

			}

			$partof = $superior->uid;

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
			'structure' => $structure,
			'partof' => $partof,
			'volume_sorting' => $metadata['volume_sorting'][0],
			'collections' => $collections,
			'owner' => $owner,
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

			tx_dlf_indexing::addToIndex($this, $core);

		}

		return TRUE;

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
		$mPid = ($this->mPid ? $this->mPid : $this->pid);

		if (!$mPid) {

			trigger_error('No PID for metadata definitions found', E_USER_ERROR);

			exit;

		}

		if (!$this->metadataArrayLoaded || $this->metadataArray[0] != $mPid) {

			// Get all logical structure nodes with metadata
			if (($_ids = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID]/@ID'))) {

				foreach ($_ids as $_id) {

					$this->metadataArray[(string) $_id] = $this->getMetadata((string) $_id, $mPid);

				}

			}

			// Set current PID for metadata definitions.
			$this->metadataArray[0] = $mPid;

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
	 * This returns $this->mPid via __get()
	 *
	 * @access	protected
	 *
	 * @return	integer		The PID of the metadata definitions
	 */
	protected function _getMPid() {

		return $this->mPid;

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

				$_useGrps = array_merge(array ('THUMBS', 'DEFAULT'), t3lib_div::trimExplode(',', $extConf['fileGrps']));

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

				$_physSeq[0]['id'] = (string) $_physNode[0]['ID'];

				$_physSeq[0]['dmdId'] = (isset($_physNode[0]['DMDID']) ? (string) $_physNode[0]['DMDID'] : '');

				$_physSeq[0]['label'] = (isset($_physNode[0]['ORDERLABEL']) ? (string) $_physNode[0]['ORDERLABEL'] : '');

				$_physSeq[0]['type'] = (string) $_physNode[0]['TYPE'];

				// Get the file representations from fileSec node.
				foreach ($_physNode[0]->children('http://www.loc.gov/METS/')->fptr as $_fptr) {

					$_physSeq[0]['files'][strtolower($_fileUse[(string) $_fptr->attributes()->FILEID])] = (string) $_fptr->attributes()->FILEID;

				}

				// Build the physical pages' array from the physical structMap node.
				foreach ($_pageNodes as $_pageNode) {

					$_pages[(int) $_pageNode['ORDER']]['id'] = (string) $_pageNode['ID'];

					$_pages[(int) $_pageNode['ORDER']]['dmdId'] = (isset($_pageNode['DMDID']) ? (string) $_pageNode['DMDID'] : '');

					$_pages[(int) $_pageNode['ORDER']]['label'] = (isset($_pageNode['ORDERLABEL']) ? (string) $_pageNode['ORDERLABEL'] : '');

					$_pages[(int) $_pageNode['ORDER']]['type'] = (string) $_pageNode['TYPE'];

					// Get the file representations from fileSec node.
					foreach ($_pageNode->children('http://www.loc.gov/METS/')->fptr as $_fptr) {

						$_pages[(int) $_pageNode['ORDER']]['files'][strtolower($_fileUse[(string) $_fptr->attributes()->FILEID])] = (string) $_fptr->attributes()->FILEID;

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
	 * This returns $this->sPid via __get()
	 *
	 * @access	protected
	 *
	 * @return	integer		The PID of the structure definitions
	 */
	protected function _getSPid() {

		return $this->sPid;

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

			// Does the document have a structMap node of type "LOGICAL"?
			$_ids = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div/@ID');

			if ($_ids) {

				// Yes. So build the logical structure array from the logical structMap node.
				foreach ($_ids as $_id) {

					$this->tableOfContents[] = $this->getLogicalStructure((string) $_id, TRUE);

				}

			} else {

				trigger_error('No logical structure found in document', E_USER_WARNING);

			}

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
	 * This sets $this->mPid via __set()
	 *
	 * @access	protected
	 *
	 * @param	integer		$value: The new PID for the metadata definitions
	 *
	 * @return	void
	 */
	protected function _setMPid($value) {

		$this->mPid = max(intval($value), 0);

	}

	/**
	 * This sets $this->sPid via __set()
	 *
	 * @access	protected
	 *
	 * @param	integer		$value: The new PID for the structure definitions
	 *
	 * @return	void
	 */
	protected function _setSPid($value) {

		$this->sPid = max(intval($value), 0);

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
			$record_id = NULL;

			$_libxmlErrors = libxml_use_internal_errors(TRUE);

			$xml = @simplexml_load_file($location);

			libxml_use_internal_errors($_libxmlErrors);

			if ($xml !== FALSE) {

				$xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');

				$_objId = $xml->xpath('//mets:mets');

				if (!empty($_objId[0]['OBJID'])) {

					$record_id = (string) $_objId[0]['OBJID'];

				} elseif (!empty($_objId[0]['ID'])) {

					$record_id = (string) $_objId[0]['ID'];

				}

				// Check for post-processing hooks.
				$this->getHookObjects();

				foreach($this->hookObjects as $hookObj) {

					if (method_exists($hookObj, 'construct_postProcessRecordId')) {

						$hookObj->construct_postProcessRecordId($xml, $record_id);

					}

				}

			}

			if ($record_id) {

				$whereClause = 'tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($record_id, 'tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_documents');

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
			'tx_dlf_documents.uid,tx_dlf_documents.pid,tx_dlf_documents.partof,tx_dlf_documents.location',
			'tx_dlf_documents',
			$whereClause,
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result) > 0) {

			list ($this->uid, $this->pid, $this->parentid, $location) = $GLOBALS['TYPO3_DB']->sql_fetch_row($_result);

		} elseif (!empty($location)) {

			$this->uid = $location;

		} else {

			trigger_error('There is no record with UID '.$uid.' or you are not allowed to access it', E_USER_ERROR);

			exit;

		}

		// Load extension configuration
		$_extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

		// Set user-agent to identify self when fetching XML data.
		if (!empty($_extConf['useragent'])) {

			ini_set('user_agent', $_extConf['useragent']);

		}

		// Load XML from file...
		$_libxmlErrors = libxml_use_internal_errors(TRUE);

		$_xml = @simplexml_load_file($location);

		libxml_use_internal_errors($_libxmlErrors);

		if ($_xml !== FALSE) {

			// ...and set some basic properties.
			$this->xml = $_xml;

			$this->init();

		} else {

			trigger_error('Could not load XML file from '.$location, E_USER_ERROR);
			// TODO: libxml_get_errors() || libxml_get_last_error() || libxml_clear_errors()

			exit;

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

		$_xml = new DOMDocument('1.0');

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

			exit;

		}

	}

}

/* No xclasses allowed for this class!
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_document.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_document.php']);
}
*/

?>