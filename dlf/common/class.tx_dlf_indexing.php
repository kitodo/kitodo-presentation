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
 * Indexing class 'tx_dlf_indexing' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_indexing {

	/**
	 * The extension key
	 *
	 * @var string
	 * @access public
	 */
	public static $extKey = 'dlf';

	/**
	 * Array of facets
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $facets = array ();

	/**
	 * Array of fields' boost values
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $fieldboost = array ();

	/**
	 * Array of indexed metadata
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $indexed = array ();

	/**
	 * List of already processed documents
	 *
	 * @var array
	 * @access protected
	 */
	protected static $processedDocs = array ();

	/**
	 * Instance of Apache_Solr_Service class
	 *
	 * @var Apache_Solr_Service
	 * @access protected
	 */
	protected static $solr;

	/**
	 * Array of sortable metadata
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $sortables = array ();

	/**
	 * Array of stored metadata
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $stored = array ();

	/**
	 * Array of tokenized metadata
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $tokenized = array ();

	/**
	 * Array of toplevel structure elements
	 * @see loadIndexConf()
	 *
	 * @var array
	 * @access protected
	 */
	protected static $toplevel = array ();

	/**
	 * Insert given document into Solr index
	 *
	 * @access	public
	 *
	 * @param	tx_dlf_document		&$doc: The document to index
	 * @param	integer		$core: UID of the Solr core to use
	 *
	 * @return	void
	 */
	public static function addToIndex(tx_dlf_document &$doc, $core = 1) {

		if (in_array($doc->uid, self::$processedDocs)) {

			return 0;

		} elseif (self::solrConnect($core, $doc->pid)) {

			$errors = 0;

			// Handle multi-volume documents.
			if ($doc->parentid) {

				$errors = self::addToIndex(tx_dlf_document::getInstance($doc->parentid, 0, TRUE), $core);

			}

			try {

				// Add document to list of processed documents.
				self::$processedDocs[] = $doc->uid;

				// Index every logical unit as separate Solr document.
				foreach ($doc->tableOfContents as $logicalUnit) {

					if (!$errors) {

						$errors = self::process($doc, $logicalUnit);

					} else {

						break;

					}

				}

				self::$solr->commit();

				// Get document title from database.
				$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_documents.title AS title',
					'tx_dlf_documents',
					'tx_dlf_documents.uid='.intval($doc->uid).tx_dlf_helper::whereClause('tx_dlf_documents'),
					'',
					'',
					'1'
				);

				$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

				if (!defined('TYPO3_cliMode')) {

					$_message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('flash.documentIndexed'), $resArray['title'], $doc->uid)),
						$GLOBALS['LANG']->getLL('flash.done', TRUE),
						t3lib_FlashMessage::OK,
						TRUE
					);

					t3lib_FlashMessageQueue::addMessage($_message);

				}

				return $errors;

			} catch (Exception $e) {

				if (!defined('TYPO3_cliMode')) {

					$_message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('flash.solrException', TRUE).'<br />'.htmlspecialchars($e->getMessage()),
						$GLOBALS['LANG']->getLL('flash.error', TRUE),
						t3lib_FlashMessage::ERROR,
						TRUE
					);

					t3lib_FlashMessageQueue::addMessage($_message);

				}

				trigger_error('Apache Solr exception thrown: '.$e->getMessage(), E_USER_ERROR);

				return 1;

			}

		} else {

			if (!defined('TYPO3_cliMode')) {

				$_message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('flash.solrNoConnection', TRUE),
					$GLOBALS['LANG']->getLL('flash.error', TRUE),
					t3lib_FlashMessage::ERROR,
					TRUE
				);

				t3lib_FlashMessageQueue::addMessage($_message);

			}

			trigger_error('Could not connect to Apache Solr server', E_USER_ERROR);

			return 1;

		}

	}

	/**
	 * Load indexing configuration
	 *
	 * @access	protected
	 *
	 * @param	integer		$pid: The configuration page's UID
	 *
	 * @return	void
	 */
	protected static function loadIndexConf($pid) {

		// Get the list of toplevel structures.
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_structures.index_name AS index_name',
			'tx_dlf_structures',
			'tx_dlf_structures.toplevel=1 AND tx_dlf_structures.pid='.intval($pid).tx_dlf_helper::whereClause('tx_dlf_structures'),
			'',
			'',
			''
		);

		while ($_toplevel = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			self::$toplevel[] = $_toplevel['index_name'];

		}

		// Get the metadata indexing options.
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.tokenized AS tokenized,tx_dlf_metadata.stored AS stored,tx_dlf_metadata.indexed AS indexed,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.boost AS boost',
			'tx_dlf_metadata',
			'tx_dlf_metadata.pid='.intval($pid).tx_dlf_helper::whereClause('tx_dlf_metadata'),
			'',
			'',
			''
		);

		while ($_indexing = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			if ($_indexing['tokenized']) {

				self::$tokenized[] = $_indexing['index_name'];

			}

			if ($_indexing['stored'] || $_indexing['is_listed']) {

				self::$stored[] = $_indexing['index_name'];

			}

			if ($_indexing['indexed']) {

				self::$indexed[] = $_indexing['index_name'];

			}

			if ($_indexing['boost'] > 0.0) {

				self::$fieldboost[$_indexing['index_name']] = $_indexing['boost'];

			} else {

				self::$fieldboost[$_indexing['index_name']] = FALSE;

			}

		}

	}

	/**
	 * Processes a logical unit (and its children) for the Solr index
	 *
	 * @access	protected
	 *
	 * @param	tx_dlf_document		&$doc: The METS document
	 * @param	array		$logicalUnit: Array of the logical unit to process
	 *
	 * @return	void
	 */
	protected static function process(tx_dlf_document &$doc, array $logicalUnit) {

		$errors = 0;

		// Get metadata for logical unit.
		$metadata = $doc->metadataArray[$logicalUnit['id']];

		if (!empty($metadata)) {

			// Load class.
			if (!class_exists('Apache_Solr_Document')) {

				require_once(t3lib_div::getFileAbsFileName('EXT:'.self::$extKey.'/lib/SolrPhpClient/Apache/Solr/Document.php'));

			}

			// Create new Solr document.
			$solrDoc = new Apache_Solr_Document();

			// Create unique identifier from document's UID and unit's XML ID.
			$solrDoc->setField('id', $doc->uid.$logicalUnit['id']);

			$solrDoc->setField('uid', $doc->uid);

			$solrDoc->setField('pid', $doc->pid);

			$solrDoc->setField('page', $logicalUnit['points']);

			$solrDoc->setField('partof', $doc->parentid);

			$solrDoc->setField('sid', $logicalUnit['id']);

			$solrDoc->setField('toplevel', in_array($logicalUnit['type'], self::$toplevel));

			$solrDoc->setField('type', $logicalUnit['type'], self::$fieldboost['type']);

			unset ($metadata['type']);

			$solrDoc->setField('title', $metadata['title'][0], self::$fieldboost['title']);

			unset ($metadata['title']);

			$solrDoc->setField('title_sorting', $metadata['title_sorting'][0]);

			unset ($metadata['title_sorting']);

			$solrDoc->setField('author', $metadata['author'], self::$fieldboost['author']);

			$solrDoc->setField('author_faceting', $metadata['author']);

			unset ($metadata['author']);

			if (!empty($metadata['author_sorting'][0])) {

				$solrDoc->setField('author_sorting', $metadata['author_sorting'][0]);

			}

			unset ($metadata['author_sorting']);

			$solrDoc->setField('year', $metadata['year'], self::$fieldboost['year']);

			$solrDoc->setField('year_faceting', $metadata['year']);

			unset ($metadata['year']);

			if (!empty($metadata['year_sorting'][0])) {

				$solrDoc->setField('year_sorting', $metadata['year_sorting'][0]);

			}

			unset ($metadata['year_sorting']);

			$solrDoc->setField('place', $metadata['place'], self::$fieldboost['place']);

			$solrDoc->setField('place_faceting', $metadata['place']);

			unset ($metadata['place']);

			if (!empty($metadata['place_sorting'][0])) {

				$solrDoc->setField('place_sorting', $metadata['place_sorting'][0]);

			}

			unset ($metadata['place_sorting']);

			foreach ($metadata as $index_name => $data) {

				if (!empty($data)) {

					$suffix = (in_array($index_name, self::$tokenized) ? 't' : 'u');

					$suffix .= (in_array($index_name, self::$stored) ? 's' : 'u');

					$suffix .= (in_array($index_name, self::$indexed) ? 'i' : 'u');

					$solrDoc->setField($index_name.'_'.$suffix, $data, self::$fieldboost[$index_name]);

					// Add sortable fields to index.
					$solrDoc->setField($index_name.'_sorting', $data[0]);

					// Add facets to index.
					$solrDoc->setField($index_name.'_faceting', $data);

				}

			}

			try {

				self::$solr->addDocument($solrDoc);

			} catch (Exception $e) {

				if (!defined('TYPO3_cliMode')) {

					$_message = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('flash.solrException', TRUE).'<br />'.htmlspecialchars($e->getMessage()),
						$GLOBALS['LANG']->getLL('flash.error', TRUE),
						t3lib_FlashMessage::ERROR,
						TRUE
					);

					t3lib_FlashMessageQueue::addMessage($_message);

				}

				return 1;

			}

		}

		// Check for child elements...
		if (!empty($logicalUnit['children'])) {

			foreach ($logicalUnit['children'] as $child) {

				if (!$errors) {

					// ...and process them, too.
					$errors = self::process($doc, $child);

				} else {

					break;

				}

			}

		}

		return $errors;

	}

	/**
	 * Connects to Solr server.
	 *
	 * @access	protected
	 *
	 * @param	integer		$core: UID of the Solr core
	 * @param	integer		$pid: UID of the configuration page
	 *
	 * @return	boolean		TRUE on success or FALSE on failure
	 */
	protected static function solrConnect($core, $pid) {

		if (!self::$solr) {

			// Connect to Solr server.
			if (self::$solr = tx_dlf_solr::solrConnect($core)) {

				// Load indexing configuration.
				self::loadIndexConf($pid);

			} else {

				return FALSE;

			}

		}

		return TRUE;

	}


	/**
	 * This is a static class, thus no instances should be created
	 *
	 * @access	protected
	 */
	protected function __construct() {}

}

/* No xclasses for static classes!
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_indexing.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_indexing.php']);
}
*/

?>