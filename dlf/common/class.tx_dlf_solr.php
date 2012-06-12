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
 * Solr class 'tx_dlf_solr' for the 'dlf' extension.
*
* @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
* @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
* @package	TYPO3
* @subpackage	tx_dlf
* @access	public
*/
class tx_dlf_solr {

	/**
	 * The extension key
	 *
	 * @var string
	 * @access public
	 */
	public static $extKey = 'dlf';

	/**
	 * Get SolrPhpClient service object and establish connection to Solr server
	 * @see EXT:dlf/lib/SolrPhpClient/Apache/Solr/Service.php
	 *
	 * @access	public
	 *
	 * @param	mixed		$core: Name or UID of the core to load
	 *
	 * @return	mixed		Instance of Apache_Solr_Service or NULL on failure
	 */
	public static function solrConnect($core = 0) {

		// Load class.
		if (!class_exists('Apache_Solr_Service')) {

			require_once(t3lib_div::getFileAbsFileName('EXT:'.self::$extKey.'/lib/SolrPhpClient/Apache/Solr/Service.php'));

		}

		// Get Solr credentials.
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

		$host = ($conf['solrHost'] ? $conf['solrHost'] : 'localhost');

		// Prepend username and password to hostname.
		if ($conf['solrUser'] && $conf['solrPass']) {

			$host = $conf['solrUser'].':'.$conf['solrPass'].'@'.$host;

		}

		// Set port if not set.
		$port = t3lib_div::intInRange($conf['solrPort'], 1, 65535, 8180);

		// Get core name if UID is given.
		if (t3lib_div::testInt($core)) {

			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_solrcores.index_name AS index_name',
					'tx_dlf_solrcores',
					'tx_dlf_solrcores.uid='.intval($core).tx_dlf_helper::whereClause('tx_dlf_solrcores'),
					'',
					'',
					'1'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				list ($core) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

			} else {

				trigger_error('Could not find Solr core with UID '.$core, E_USER_NOTICE);

				return;

			}

		}

		// Append core name to path.
		$path = trim($conf['solrPath'], '/').'/'.$core;

		// Instantiate Apache_Solr_Service class.
		$solr = t3lib_div::makeInstance('Apache_Solr_Service', $host, $port, $path);

		// Check if connection is established.
		if ($solr->ping() !== FALSE) {

			// Do not collapse single value arrays.
			$solr->setCollapseSingleValueArrays = FALSE;

			return $solr;

		} else {

			trigger_error('Could not connect to Solr server with core "'.$core.'"', E_USER_ERROR);

			return;

		}

	}

	/**
	 * Get next unused Solr core number
	 *
	 * @access	public
	 *
	 * @param	integer		$start: Number to start with
	 *
	 * @return	integer		First unused core number found
	 */
	public static function solrGetCoreNumber($start = 0) {

		$start = max(intval($start), 0);

		// Check if core already exists.
		if (self::solrConnect('dlfCore'.$start) === NULL) {

			return $start;

		} else {

			return self::solrGetCoreNumber($start + 1);

		}

	}

	/**
	 *
	 *
	 * @access	public
	 *
	 * @param	tx_dlf_solr_search		$search: Search info
	 *
	 * @return	integer		First unused core number found
	 */
	public static function search($searchStruct) {

		t3lib_div::devLog('[search]   search='.$searchStruct, 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

		$solr = self::solrConnect($searchStruct->core);

		if ($solr === NULL) {

			return NULL;

		}

		// Extract facet queries.
		$facetParams = array();

		if (count($searchStruct->filterQuery) > 0) {

			$facetParams['facet'] = 'true';

			$facetParams['fq'] = $searchStruct->filterQuery;

			t3lib_div::devLog('[search]   using facetParams='.tx_dlf_helper::array_toString($facetParams), 'dlf', t3lib_div::SYSLOG_SEVERITY_INFO);

		}

		// Perform search.
		$query = $solr->search($searchStruct->queryString, 0, $searchStruct->limit, $facetParams);

		$_list = array ();

		$toplevel = array ();

		$check = array ();

		// Process results.
		foreach ($query->response->docs as $doc) {

			// Split toplevel documents from subparts.
			if ($doc->toplevel == 1) {

				$toplevel[$doc->uid] = array (
						'uid' => $doc->uid,
						'page' => $doc->page,
						'title' => (is_array($doc->title) ? $doc->title : array ($doc->title)),
						'volume' => (is_array($doc->volume) ? $doc->volume : array ($doc->volume)),
						'author' => (is_array($doc->author) ? $doc->author : array ($doc->author)),
						'year' => (is_array($doc->year) ? $doc->year : array ($doc->year)),
						'place' => (is_array($doc->place) ? $doc->place : array ($doc->place)),
						'type' => (is_array($doc->type) ? $doc->type : array ($doc->type)),
						'subparts' => (!empty($toplevel[$doc->uid]['subparts']) ? $toplevel[$doc->uid]['subparts'] : array ())
				);

			} else {

				$toplevel[$doc->uid]['subparts'][] = array (
						'uid' => $doc->uid,
						'page' => $doc->page,
						'title' => (is_array($doc->title) ? $doc->title : array ($doc->title)),
						'volume' => (is_array($doc->volume) ? $doc->volume : array ($doc->volume)),
						'author' => (is_array($doc->author) ? $doc->author : array ($doc->author)),
						'year' => (is_array($doc->year) ? $doc->year : array ($doc->year)),
						'place' => (is_array($doc->place) ? $doc->place : array ($doc->place)),
						'type' => (is_array($doc->type) ? $doc->type : array ($doc->type))
				);

				if (!in_array($doc->uid, $check)) {

					$check[] = $doc->uid;

				}

			}

		}

		// Check if the toplevel documents have metadata.
		foreach ($check as $_check) {

			if (empty($toplevel[$_check]['uid'])) {

				// Get information for toplevel document.
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'tx_dlf_documents.uid AS uid,tx_dlf_documents.title AS title,tx_dlf_documents.volume AS volume,tx_dlf_documents.author AS author,tx_dlf_documents.place AS place,tx_dlf_documents.year AS year,tx_dlf_documents.structure AS type',
						'tx_dlf_documents',
						'tx_dlf_documents.uid='.intval($_check).tx_dlf_helper::whereClause('tx_dlf_documents'),
						'',
						'',
						'1'
				);

				// Process results.
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

					$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

					$toplevel[$_check] = array (
							'uid' => $resArray['uid'],
							'page' => 1,
							'title' => array ($resArray['title']),
							'volume' => array ($resArray['volume']),
							'author' => array ($resArray['author']),
							'year' => array ($resArray['year']),
							'place' => array ($resArray['place']),
							'type' => array (tx_dlf_helper::getIndexName($resArray['type'], 'tx_dlf_structures', $searchStruct->pid)),
							'subparts' => $toplevel[$_check]['subparts']
					);

				} else {

					// Clear entry if there is no (accessible) toplevel document.
					unset ($toplevel[$_check]);

				}

			}

		}

		// Save list of documents.
		$list = t3lib_div::makeInstance('tx_dlf_list');

		$list->reset();

		$list->add(array_values($toplevel));

		$hitCount = count($query->response->docs);

		// Set metadata for search.
		$_metadata = array (
				'label' => empty($searchStruct->label) ?
						sprintf(tx_dlf_helper::getLL(get_class().'.searchfor'), $searchStruct->queryString)
						: $searchStruct->label,
				'description' => empty($searchStruct->description) ?
						sprintf(tx_dlf_helper::getLL(get_class().'.hits'), $hitCount)
						: $searchStruct->description,
				'options' => array (
						'source' => $searchStruct->source,
						'select' => $searchStruct->queryString,
						'filter.query' => $searchStruct->filterQuery,
						'order' => $searchStruct->order
				),
				'result' => array (
						'hitCount' => $hitCount
				)
		);

		$list->metadata = $_metadata;

		$list->save();

		return $list;

	}

	/**
	 * This is a static class, thus no instances should be created
	 *
	 * @access	protected
	 */
	protected function __construct() {
	}

}

/* No xclasses for static classes!
 if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php'])	{
include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_solr.php']);
}
*/

?>