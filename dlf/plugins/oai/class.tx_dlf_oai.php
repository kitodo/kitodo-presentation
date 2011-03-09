<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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
 * Plugin 'DLF: OAI-PMH Interface' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2010, Sebastian Meyer, SLUB Dresden
 * @version	$Id: class.tx_dlf_search.php 315 2010-10-07 13:50:33Z smeyer $
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_oai extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/oai/class.tx_dlf_oai.php';

	/**
	 * Did an error occur?
	 *
	 * @var	boolean
	 * @access protected
	 */
	protected $error = FALSE;

	/**
	 * This holds the configuration for all supported metadata prefixes
	 *
	 * @var	array
	 * @access protected
	 */
	protected $formats = array (
//		'oai_dc' => array (
//			'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
//			'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
//		),
//		'epicur' => array (
//			'schema' => 'http://www.persistent-identifier.de/xepicur/version1.0/xepicur.xsd',
//			'namespace' => 'urn:nbn:de:1111-2004033116',
//		),
//		'ese' => array (
//			'schema' => 'http://www.europeana.eu/schemas/ese/ESE-V3.3.xsd',
//			'namespace' => 'http://www.europeana.eu/schemas/ese/',
//		),
		'mets' => array (
			'schema' => 'http://www.loc.gov/standards/mets/version17/mets.v1-7.xsd',
			'namespace' => 'http://www.loc.gov/METS/',
		)
	);

	/**
	 * Delete expired resumption tokens
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function deleteExpiredTokens() {

		// Delete expired resumption tokens.
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_dlf_tokens',
			'tx_dlf_tokens.ident="oai" AND tx_dlf_tokens.tstamp<'.intval($GLOBALS['EXEC_TIME'] - $this->conf['expired'])
		);

		if ($GLOBALS['TYPO3_DB']->sql_affected_rows($_result) === -1) {

			// Deletion failed.
			trigger_error('Could not delete expired resumption tokens', E_USER_ERROR);

		}

	}

	/**
	 * Process error
	 *
	 * @access	protected
	 *
	 * @param	string		$type: Error type
	 *
	 * @return	string		Substitution for subpart "###ERROR###"
	 */
	protected function error($type) {

		$this->error = TRUE;

		$markerArray = array (
			'###ERROR_CODE###' => $type,
			'###ERROR_MESSAGE###' => $this->pi_getLL($type, $type, TRUE)
		);

		return $this->cObj->substituteMarkerArray($this->cObj->getSubpart($this->template, '###ERROR###'), $markerArray);

	}

	/**
	 * Load URL parameters
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function getUrlParams() {

		$_allowedParams = array (
			'verb',
			'identifier',
			'metadataPrefix',
			'from',
			'until',
			'set',
			'resumptionToken'
		);

		// Clear plugin variables.
		$this->piVars = array ();

		// Set only allowed parameters.
		foreach ($_allowedParams as $_param) {

			if (t3lib_div::_GP($_param)) {

				$this->piVars[$_param] = t3lib_div::_GP($_param);

			}

		}

	}

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	void
	 */
	public function main($content, $conf) {

		// Initialize plugin.
		$this->init($conf);

		// Turn cache off.
		$this->setCache(FALSE);

		// Get GET and POST variables.
		$this->getUrlParams();

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/oai/template.tmpl'), '###TEMPLATE###');

		}

		// Delete expired resumption tokens.
		$this->deleteExpiredTokens();

		switch ($this->piVars['verb']) {

			case 'GetRecord':

				$response = $this->verbGetRecord();

				break;

			case 'Identify':

				$response = $this->verbIdentify();

				break;

			case 'ListIdentifiers':

				$response = $this->verbListIdentifiers();

				break;

			case 'ListMetadataFormats':

				$response = $this->verbListMetadataFormats();

				break;

			case 'ListRecords':

				$response = $this->verbListRecords();

				break;

			case 'ListSets':

				$response = $this->verbListSets();

				break;

			default:

				$response = $this->error('badVerb');

		}

		// Set response date, base url and request.
		$markerArray = array (
			'###RESPONSEDATE###' => gmdate('Y-m-d\TH:i:s\Z', $GLOBALS['EXEC_TIME']),
			'###BASE_URL###' => t3lib_div::locationHeaderUrl($this->pi_getPageLink($GLOBALS['TSFE']->id)),
			'###REQUEST###' => ''
		);

		if (!$this->error) {

			foreach ($this->piVars as $key => $value) {

				$markerArray['###REQUEST###'] .= ' '.$key.'="'.$value.'"';

			}

		}

		// Set XSL transformation stylesheet.
		if (!empty($this->conf['stylesheet'])) {

			// Resolve "EXT:" prefix in file path.
			if (substr($this->conf['stylesheet'], 0, 4) == 'EXT:') {

				list ($_extKey, $_filePath) = explode('/', substr($this->conf['stylesheet'], 4), 2);

				if (t3lib_extMgm::isLoaded($_extKey)) {

					$this->conf['stylesheet'] = t3lib_extMgm::siteRelPath($_extKey).$_filePath;

				}

			}

			$markerArray['###STYLESHEET###'] = t3lib_div::locationHeaderUrl($this->conf['stylesheet']);

		} else {

			$markerArray['###STYLESHEET###'] = t3lib_div::locationHeaderUrl(t3lib_extMgm::siteRelPath($this->extKey).'plugins/oai/transform.xsl');

		}

		// Substitute markers and subparts.
		$content = trim($this->cObj->substituteSubpart($this->cObj->substituteMarkerArray($this->template, $markerArray), '###RESPONSE###', $response, TRUE));

		// Send headers.
		header('HTTP/1.1 200 OK');

		header('Cache-Control: no-cache');

		header('Content-Length: '.strlen($content));

		header('Content-Type: text/xml; charset=utf-8');

		header('Date: '.date('r', $GLOBALS['EXEC_TIME']));

		header('Expires: '.date('r', $GLOBALS['EXEC_TIME']));

		echo $content;

		// Flush output buffer and end script processing.
		ob_end_flush();

		exit;

	}

	/**
	 * Continue with resumption token
	 *
	 * @access	protected
	 *
	 * @return	string		Substitution for subpart "###RESPONSE###"
	 */
	protected function resume() {

		// Get resumption token.
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_tokens.options AS options',
			'tx_dlf_tokens',
			'tx_dlf_tokens.ident="oai" AND tx_dlf_tokens.token='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['resumptionToken'], 'tx_dlf_tokens'),
			'',
			'',
			'1'
		);

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

			// No resumption token found or resumption token expired.
			return $this->error('badResumptionToken');

		}

		$_resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

		$resultSet = unserialize($_resArray['options']);

		$piVars = json_decode($resultSet->metadata['piVars']);

		// Get template and fill with data.
		if ($piVars['verb'] == 'ListRecords') {

			$_itemTemplate = $this->cObj->getSubpart($this->template, '###LISTRECORDS_ITEM###');

		} else {

			$_itemTemplate = $this->cObj->getSubpart($this->template, '###LISTIDENTIFIERS_ITEM###');

		}

		$_specTemplate = $this->cObj->getSubpart($_itemTemplate, '###RECORD_SETSPEC_ITEM###');

		$_recordTemplate = $this->cObj->getSubpart($_itemTemplate, '###RECORD_METADATA###');

		$content = '';

		$complete = FALSE;

		for ($i = $resultSet->metadata['offset']; $i < intval($resultSet->metadata['offset'] + $this->conf['limit']); $i++) {

			$markerArray = array (
				'###RECORD_DELETED###' => '',
				'###RECORD_IDENTIFIER###' => $resultSet->elements[$i]['record_id'],
				'###RECORD_DATESTAMP###' => gmdate('Y-m-d\TH:i:s\Z', $resultSet->elements[$i]['tstamp']),
			);

			$subpartArray = array (
				'###RECORD_SETSPEC_ITEM###' => '',
				'###RECORD_METADATA###' => '',
			);

			// Check if document is deleted or hidden.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_dlf_documents',
				'tx_dlf_documents.uid='.intval($resultSet->elements[$i]['uid']).tx_dlf_helper::whereClause('tx_dlf_documents'),
				'',
				'',
				'1'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				foreach (explode(' ', $resultSet->elements[$i]['collections']) as $_spec) {

					$subpartArray['###RECORD_SETSPEC_ITEM###'] .= $this->cObj->substituteMarkerArray($_specTemplate, array ('###RECORD_SETSPEC###' => $_spec));

				}

				if ($piVars['verb'] == 'ListRecords') {

					switch ($this->piVars['metadataPrefix']) {

						case 'oai_dc':

							// TODO!

							break;

						case 'epicur':

							// TODO!

							break;

						case 'ese':

							// TODO!

							break;

						case 'mets':

							$xml = simplexml_load_file($resultSet->elements[$i]['location']);

							$xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');

							$mets = $xml->xpath('//mets:mets');

							$recordMarker['###RECORD_XML###'] = trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $mets[0]->asXML()));

							break;

					}

					$subpartArray['###RECORD_METADATA###'] = $this->cObj->substituteMarkerArray($_recordTemplate, $recordMarker);

				}

			} else {

				$markerArray['###RECORD_DELETED###'] = ' status="deleted"';

			}

			$_content = $this->cObj->substituteSubpartArray($_itemTemplate, $subpartArray);

			$content .= $this->cObj->substituteMarkerArray($_content, $markerArray);

			if (empty($resultSet->elements[$i + 1])) {

				$complete = TRUE;

				break;

			}

		}

		if (!$complete) {

			// Save result set to database and generate resumption token.
			$token = uniqid();

			$resumptionToken['###RESUMPTIONTOKEN###'] = '<resumptionToken completeListSize="'.$resultSet->count.'" cursor="'.$resultSet->metadata['offset'].'">'.$token.'</resumptionToken>';

			$resultSet->metadata = array (
				'offset' => intval($resultSet->metadata['offset'] + $this->conf['limit']),
				'piVars' => json_encode($piVars),
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_dlf_tokens',
				array (
					'tstamp' => $GLOBALS['EXEC_TIME'],
					'token' => $token,
					'options' => serialize($resultSet),
					'ident' => 'oai',
				)
			);

		} else {

			// List completed, no new resumption token needed.
			$resumptionToken['###RESUMPTIONTOKEN###'] = '<resumptionToken completeListSize="'.$resultSet->count.'" cursor="'.$resultSet->metadata['offset'].'" />';

		}

		// Substitute subpart marker.
		if ($piVars['verb'] == 'ListRecords') {

			$template = $this->cObj->getSubpart($this->template, '###LISTRECORDS###');

			$template = $this->cObj->substituteMarkerArray($template, $resumptionToken);

			return $this->cObj->substituteSubpart($template, '###LISTRECORDS_ITEM###', $content, TRUE);

		} else {

			$template = $this->cObj->getSubpart($this->template, '###LISTIDENTIFIERS###');

			$template = $this->cObj->substituteMarkerArray($template, $resumptionToken);

			return $this->cObj->substituteSubpart($template, '###LISTIDENTIFIERS_ITEM###', $content, TRUE);

		}

	}

	/**
	 * Process verb "GetRecord"
	 *
	 * @access	protected
	 *
	 * @return	string		Substitution for subpart "###RESPONSE###"
	 */
	protected function verbGetRecord() {

		// Check for invalid arguments.
		if (count($this->piVars) != 3 || empty($this->piVars['metadataPrefix']) || empty($this->piVars['identifier'])) {

			return $this->error('badArgument');

		} else {

			// Check "metadataPrefix" for valid value.
			if (!in_array($this->piVars['metadataPrefix'], array_keys($this->formats))) {

				return $this->error('cannotDisseminateFormat');

			}

			$where = '';

			// Select records from database.
			if (!$this->conf['show_userdefined']) {

				$where .= ' AND tx_dlf_collections.fe_cruser_id=0';

			}

			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dlf_documents.uid AS uid,tx_dlf_documents.record_id AS record_id,tx_dlf_documents.tstamp AS tstamp,tx_dlf_documents.location AS location,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
				'tx_dlf_documents',
				'tx_dlf_relations',
				'tx_dlf_collections',
				'AND tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['identifier'], 'tx_dlf_documents').' AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
				'tx_dlf_documents.uid',
				'tx_dlf_documents.tstamp',
				'1'
			);

			if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				return $this->error('idDoesNotExist');

			} else {

				$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

				// Get template and fill with data.
				$_itemTemplate = $this->cObj->getSubpart($this->template, '###GETRECORD###');

				$_specTemplate = $this->cObj->getSubpart($_itemTemplate, '###RECORD_SETSPEC_ITEM###');

				$_recordTemplate = $this->cObj->getSubpart($_itemTemplate, '###RECORD_METADATA###');

				$markerArray = array (
					'###RECORD_DELETED###' => '',
					'###RECORD_IDENTIFIER###' => $resArray['record_id'],
					'###RECORD_DATESTAMP###' => gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp']),
				);

				$subpartArray = array (
					'###RECORD_SETSPEC_ITEM###' => '',
					'###RECORD_METADATA###' => '',
				);

				// Check if document is deleted or hidden.
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_dlf_documents',
					'tx_dlf_documents.uid='.intval($resArray['uid']).tx_dlf_helper::whereClause('tx_dlf_documents'),
					'',
					'',
					'1'
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

					foreach (explode(' ', $resArray['collections']) as $_spec) {

						$subpartArray['###RECORD_SETSPEC_ITEM###'] .= $this->cObj->substituteMarkerArray($_specTemplate, array ('###RECORD_SETSPEC###' => $_spec));

					}

					switch ($this->piVars['metadataPrefix']) {

						case 'oai_dc':

							// TODO!

							break;

						case 'epicur':

							// TODO!

							break;

						case 'ese':

							// TODO!

							break;

						case 'mets':

							$xml = simplexml_load_file($resArray['location']);

							$xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');

							$mets = $xml->xpath('//mets:mets');

							$recordMarker['###RECORD_XML###'] = trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $mets[0]->asXML()));

							break;

					}

					$subpartArray['###RECORD_METADATA###'] = $this->cObj->substituteMarkerArray($_recordTemplate, $recordMarker);

				} else {

					$markerArray['###RECORD_DELETED###'] = ' status="deleted"';

				}

				// Substitute subparts and markers.
				$template = $this->cObj->substituteSubpartArray($_itemTemplate, $subpartArray);

				return $this->cObj->substituteMarkerArray($template, $markerArray);

			}

		}

	}

	/**
	 * Process verb "ListIdentifiers"
	 *
	 * @access	protected
	 *
	 * @return	string		Substitution for subpart "###RESPONSE###"
	 */
	protected function verbListIdentifiers() {

		// Check for invalid arguments.
		if (!empty($this->piVars['resumptionToken'])) {

			// "resumptionToken" is an exclusive argument.
			if (count($this->piVars) > 2) {

				return $this->error('badArgument');

			} else {

				return $this->resume();

			}

		} elseif (empty($this->piVars['metadataPrefix']) || !empty($this->piVars['identifier'])) {

			// "metadataPrefix" is required and "identifier" is not allowed.
			return $this->error('badArgument');

		} else {

			$where = '';

			// Check "metadataPrefix" for valid value.
			if (!in_array($this->piVars['metadataPrefix'], array_keys($this->formats))) {

				return $this->error('cannotDisseminateFormat');

			}

			// Check "set" for valid value.
			if (!empty($this->piVars['set'])) {

				// Get set information.
				$_additionalWhere = '';

				if (!$this->conf['show_userdefined']) {

					$_additionalWhere = ' AND tx_dlf_collections.fe_cruser_id=0';

				}

				$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_collections.uid AS uid',
					'tx_dlf_collections',
					'tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.oai_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['set'], 'tx_dlf_collections').$_additionalWhere.tx_dlf_helper::whereClause('tx_dlf_collections'),
					'',
					'',
					'1'
				);

				if (!$GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

					return $this->error('noSetHierarchy');

				} else {

					$_resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

					$where .= ' AND tx_dlf_collections.uid='.intval($_resArray['uid']);

				}

			}

			// Check "from" for valid value.
			if (!empty($this->piVars['from'])) {

				if (is_array($_from = strptime($this->piVars['from'], '%Y-%m-%dT%H:%M:%SZ')) || is_array($_from = strptime($this->piVars['from'], '%Y-%m-%d'))) {

					$_from = gmmktime($_from['tm_hour'], $_from['tm_min'], $_from['tm_sec'], $_from['tm_mon'] + 1, $_from['tm_mday'], $_from['tm_year'] + 1900);

				} else {

					return $this->error('badArgument');

				}

				$where .= ' AND tx_dlf_documents.tstamp>='.intval($_from);

			}

			// Check "until" for valid value.
			if (!empty($this->piVars['until'])) {

				if (is_array($_until = strptime($this->piVars['until'], '%Y-%m-%dT%H:%M:%SZ')) || is_array($_until = strptime($this->piVars['until'], '%Y-%m-%d'))) {

					$_until = gmmktime($_until['tm_hour'], $_until['tm_min'], $_until['tm_sec'], $_until['tm_mon'] + 1, $_until['tm_mday'], $_until['tm_year'] + 1900);

				} else {

					return $this->error('badArgument');

				}

				if (!empty($_from) && $_from > $_until) {

					return $this->error('badArgument');

				}

				$where .= ' AND tx_dlf_documents.tstamp<='.intval($_until);

			}

		}

		// Select records from database.
		if (!$this->conf['show_userdefined']) {

			$where .= ' AND tx_dlf_collections.fe_cruser_id=0';

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_documents.uid AS uid,tx_dlf_documents.record_id AS record_id,tx_dlf_documents.tstamp AS tstamp,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
			'tx_dlf_documents.uid',
			'tx_dlf_documents.tstamp',
			''
		);

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

			return $this->error('noRecordsMatch');

		} else {

			// Build result set.
			$results = array ();

			while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

				$results[] = $resArray;

			}

			// Save result set as list object.
			$resultSet = t3lib_div::makeInstance('tx_dlf_list');

			$resultSet->reset();

			$resultSet->add($results);

			// Get template and fill with data.
			$_itemTemplate = $this->cObj->getSubpart($this->template, '###LISTIDENTIFIERS_ITEM###');

			$_specTemplate = $this->cObj->getSubpart($_itemTemplate, '###RECORD_SETSPEC_ITEM###');

			$content = '';

			$complete = FALSE;

			for ($i = 0; $i < intval($this->conf['limit']); $i++) {

				$markerArray = array (
					'###RECORD_DELETED###' => '',
					'###RECORD_IDENTIFIER###' => $resultSet->elements[$i]['record_id'],
					'###RECORD_DATESTAMP###' => gmdate('Y-m-d\TH:i:s\Z', $resultSet->elements[$i]['tstamp']),
				);

				$subpartArray = array (
					'###RECORD_SETSPEC_ITEM###' => '',
				);

				// Check if document is deleted or hidden.
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_dlf_documents',
					'tx_dlf_documents.uid='.intval($resultSet->elements[$i]['uid']).tx_dlf_helper::whereClause('tx_dlf_documents'),
					'',
					'',
					'1'
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

					foreach (explode(' ', $resultSet->elements[$i]['collections']) as $_spec) {

						$subpartArray['###RECORD_SETSPEC_ITEM###'] .= $this->cObj->substituteMarkerArray($_specTemplate, array ('###RECORD_SETSPEC###' => $_spec));

					}

				} else {

					$markerArray['###RECORD_DELETED###'] = ' status="deleted"';

				}

				$_content = $this->cObj->substituteSubpartArray($_itemTemplate, $subpartArray);

				$content .= $this->cObj->substituteMarkerArray($_content, $markerArray);

				if (empty($resultSet->elements[$i + 1])) {

					$complete = TRUE;

					break;

				}

			}

			if (!$complete) {

				// Save result set to database and generate resumption token.
				$token = uniqid();

				$resumptionToken['###RESUMPTIONTOKEN###'] = '<resumptionToken completeListSize="'.$resultSet->count.'" cursor="0">'.$token.'</resumptionToken>';

				$resultSet->metadata = array (
					'offset' => intval($this->conf['limit']),
					'piVars' => json_encode($this->piVars),
				);

				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_dlf_tokens',
					array (
						'tstamp' => $GLOBALS['EXEC_TIME'],
						'token' => $token,
						'options' => serialize($resultSet),
						'ident' => 'oai',
					)
				);

			} else {

				// Complete list returned, no resumption token needed.
				$resumptionToken['###RESUMPTIONTOKEN###'] = '';

			}

			// Substitute subpart marker.
			$template = $this->cObj->getSubpart($this->template, '###LISTIDENTIFIERS###');

			$template = $this->cObj->substituteMarkerArray($template, $resumptionToken);

			return $this->cObj->substituteSubpart($template, '###LISTIDENTIFIERS_ITEM###', $content, TRUE);

		}

	}

	/**
	 * Process verb "Identify"
	 *
	 * @access	protected
	 *
	 * @return	string		Substitution for subpart "###RESPONSE###"
	 */
	protected function verbIdentify() {

		// Check for invalid arguments.
		if (count($this->piVars) > 1) {

			return $this->error('badArgument');

		}

		// Get repository name and administrative contact.
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_libraries.oai_label AS oai_label,tx_dlf_libraries.contact AS contact',
			'tx_dlf_libraries',
			'tx_dlf_libraries.pid='.intval($this->conf['pages']).' AND tx_dlf_libraries.uid='.intval($this->conf['library']).tx_dlf_helper::whereClause('tx_dlf_libraries'),
			'',
			'',
			''
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

			$markerArray = array (
				'###IDENTIFY_NAME###' => htmlspecialchars($resArray['oai_label'], ENT_NOQUOTES, 'UTF-8'),
				'###IDENTIFY_URL###' => t3lib_div::locationHeaderUrl($this->pi_getPageLink($GLOBALS['TSFE']->id)),
				'###IDENTIFY_MAIL###' => trim(str_replace('mailto:', '', $resArray['contact']))
			);

		} else {

			trigger_error('OAI interface needs more configuration', E_USER_ERROR);

			exit;

		}

		// Get earliest datestamp.
		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_documents.tstamp AS tstamp',
			'tx_dlf_documents',
			'tx_dlf_documents.pid='.intval($this->conf['pages']),
			'',
			'tx_dlf_documents.tstamp ASC',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

			$markerArray['###IDENTIFY_DATE###'] = gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp']);

		} else {

			$markerArray['###IDENTIFY_DATE###'] = '0000-00-00T00:00:00Z';

			trigger_error('No records found with PID '.$this->conf['pages'], E_USER_WARNING);

		}

		return $this->cObj->substituteMarkerArray($this->cObj->getSubpart($this->template, '###IDENTIFY###'), $markerArray);

	}

	/**
	 * Process verb "ListMetadataFormats"
	 *
	 * @access	protected
	 *
	 * @return	string		Substitution for subpart "###RESPONSE###"
	 */
	protected function verbListMetadataFormats() {

		// Check for invalid arguments.
		if (count($this->piVars) > 1) {

			if (empty($this->piVars['identifier']) || count($this->piVars) > 2) {

				return $this->error('badArgument');

			} else {

				// Check given identifier.
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_documents.*',
					'tx_dlf_documents',
					'tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['identifier'], 'tx_dlf_documents'),
					'',
					'',
					'1'
				);

				if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

					return $this->error('idDoesNotExist');

				}

			}

		}

		// Get template and fill with data.
		$_itemTemplate = $this->cObj->getSubpart($this->template, '###LISTMETADATAFORMATS_ITEM###');

		$content = '';

		foreach ($this->formats as $prefix => $details) {

			$markerArray = array (
				'###LISTMETADATAFORMATS_PREFIX###' => $prefix,
				'###LISTMETADATAFORMATS_SCHEMA###' => $details['schema'],
				'###LISTMETADATAFORMATS_NAMESPACE###' => $details['namespace']
			);

			$content .= $this->cObj->substituteMarkerArray($_itemTemplate, $markerArray);

		}

		return $this->cObj->substituteSubpart($this->cObj->getSubpart($this->template, '###LISTMETADATAFORMATS###'), '###LISTMETADATAFORMATS_ITEM###', $content, TRUE);

	}

	/**
	 * Process verb "ListRecords"
	 *
	 * @access	protected
	 *
	 * @return	string		Substitution for subpart "###RESPONSE###"
	 */
	protected function verbListRecords() {

		// Check for invalid arguments.
		if (!empty($this->piVars['resumptionToken'])) {

			// "resumptionToken" is an exclusive argument.
			if (count($this->piVars) > 2) {

				return $this->error('badArgument');

			} else {

				return $this->resume();

			}

		} elseif (empty($this->piVars['metadataPrefix']) || !empty($this->piVars['identifier'])) {

			// "metadataPrefix" is required and "identifier" is not allowed.
			return $this->error('badArgument');

		} else {

			$where = '';

			// Check "metadataPrefix" for valid value.
			if (!in_array($this->piVars['metadataPrefix'], array_keys($this->formats))) {

				return $this->error('cannotDisseminateFormat');

			}

			// Check "set" for valid value.
			if (!empty($this->piVars['set'])) {

				// Get set information.
				$_additionalWhere = '';

				if (!$this->conf['show_userdefined']) {

					$_additionalWhere = ' AND tx_dlf_collections.fe_cruser_id=0';

				}

				$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_collections.uid AS uid',
					'tx_dlf_collections',
					'tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.oai_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['set'], 'tx_dlf_collections').$_additionalWhere.tx_dlf_helper::whereClause('tx_dlf_collections'),
					'',
					'',
					'1'
				);

				if (!$GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

					return $this->error('noSetHierarchy');

				} else {

					$_resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

					$where .= ' AND tx_dlf_collections.uid='.intval($_resArray['uid']);

				}

			}

			// Check "from" for valid value.
			if (!empty($this->piVars['from'])) {

				if (is_array($_from = strptime($this->piVars['from'], '%Y-%m-%dT%H:%M:%SZ')) || is_array($_from = strptime($this->piVars['from'], '%Y-%m-%d'))) {

					$_from = gmmktime($_from['tm_hour'], $_from['tm_min'], $_from['tm_sec'], $_from['tm_mon'] + 1, $_from['tm_mday'], $_from['tm_year'] + 1900);

				} else {

					return $this->error('badArgument');

				}

				$where .= ' AND tx_dlf_documents.tstamp>='.intval($_from);

			}

			// Check "until" for valid value.
			if (!empty($this->piVars['until'])) {

				if (is_array($_until = strptime($this->piVars['until'], '%Y-%m-%dT%H:%M:%SZ')) || is_array($_until = strptime($this->piVars['until'], '%Y-%m-%d'))) {

					$_until = gmmktime($_until['tm_hour'], $_until['tm_min'], $_until['tm_sec'], $_until['tm_mon'] + 1, $_until['tm_mday'], $_until['tm_year'] + 1900);

				} else {

					return $this->error('badArgument');

				}

				if (!empty($_from) && $_from > $_until) {

					return $this->error('badArgument');

				}

				$where .= ' AND tx_dlf_documents.tstamp<='.intval($_until);

			}

		}

		// Select records from database.
		if (!$this->conf['show_userdefined']) {

			$where .= ' AND tx_dlf_collections.fe_cruser_id=0';

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_documents.uid AS uid,tx_dlf_documents.record_id AS record_id,tx_dlf_documents.tstamp AS tstamp,tx_dlf_documents.location AS location,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
			'tx_dlf_documents.uid',
			'tx_dlf_documents.tstamp',
			''
		);

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

			return $this->error('noRecordsMatch');

		} else {

			// Build result set.
			$results = array ();

			while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

				$results[] = $resArray;

			}

			// Save result set as list object.
			$resultSet = t3lib_div::makeInstance('tx_dlf_list');

			$resultSet->reset();

			$resultSet->add($results);

			// Get template and fill with data.
			$_itemTemplate = $this->cObj->getSubpart($this->template, '###LISTRECORDS_ITEM###');

			$_specTemplate = $this->cObj->getSubpart($_itemTemplate, '###RECORD_SETSPEC_ITEM###');

			$_recordTemplate = $this->cObj->getSubpart($_itemTemplate, '###RECORD_METADATA###');

			$content = '';

			$complete = FALSE;

			for ($i = 0; $i < intval($this->conf['limit']); $i++) {

				$markerArray = array (
					'###RECORD_DELETED###' => '',
					'###RECORD_IDENTIFIER###' => $resultSet->elements[$i]['record_id'],
					'###RECORD_DATESTAMP###' => gmdate('Y-m-d\TH:i:s\Z', $resultSet->elements[$i]['tstamp']),
				);

				$subpartArray = array (
					'###RECORD_SETSPEC_ITEM###' => '',
					'###RECORD_METADATA###' => '',
				);

				// Check if document is deleted or hidden.
				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_dlf_documents',
					'tx_dlf_documents.uid='.intval($resultSet->elements[$i]['uid']).tx_dlf_helper::whereClause('tx_dlf_documents'),
					'',
					'',
					'1'
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

					foreach (explode(' ', $resultSet->elements[$i]['collections']) as $_spec) {

						$subpartArray['###RECORD_SETSPEC_ITEM###'] .= $this->cObj->substituteMarkerArray($_specTemplate, array ('###RECORD_SETSPEC###' => $_spec));

					}

					switch ($this->piVars['metadataPrefix']) {

						case 'oai_dc':

							// TODO!

							break;

						case 'epicur':

							// TODO!

							break;

						case 'ese':

							// TODO!

							break;

						case 'mets':

							$xml = simplexml_load_file($resultSet->elements[$i]['location']);

							$xml->registerXPathNamespace('mets', 'http://www.loc.gov/METS/');

							$mets = $xml->xpath('//mets:mets');

							$recordMarker['###RECORD_XML###'] = trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $mets[0]->asXML()));

							break;

					}

					$subpartArray['###RECORD_METADATA###'] = $this->cObj->substituteMarkerArray($_recordTemplate, $recordMarker);

				} else {

					$markerArray['###RECORD_DELETED###'] = ' status="deleted"';

				}

				$_content = $this->cObj->substituteSubpartArray($_itemTemplate, $subpartArray);

				$content .= $this->cObj->substituteMarkerArray($_content, $markerArray);

				if (empty($resultSet->elements[$i + 1])) {

					$complete = TRUE;

					break;

				}

			}

			if (!$complete) {

				// Save result set to database and generate resumption token.
				$token = uniqid();

				$resumptionToken['###RESUMPTIONTOKEN###'] = '<resumptionToken completeListSize="'.$resultSet->count.'" cursor="0">'.$token.'</resumptionToken>';

				$resultSet->metadata = array (
					'offset' => intval($this->conf['limit']),
					'piVars' => json_encode($this->piVars),
				);

				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_dlf_tokens',
					array (
						'tstamp' => $GLOBALS['EXEC_TIME'],
						'token' => $token,
						'options' => serialize($resultSet),
						'ident' => 'oai',
					)
				);

			} else {

				// Complete list returned, no resumption token needed.
				$resumptionToken['###RESUMPTIONTOKEN###'] = '';

			}

			// Substitute subpart marker.
			$template = $this->cObj->getSubpart($this->template, '###LISTRECORDS###');

			$template = $this->cObj->substituteMarkerArray($template, $resumptionToken);

			return $this->cObj->substituteSubpart($template, '###LISTRECORDS_ITEM###', $content, TRUE);

		}

	}

	/**
	 * Process verb "ListSets"
	 *
	 * @access	protected
	 *
	 * @return	string		Substitution for subpart "###RESPONSE###"
	 */
	protected function verbListSets() {

		// Check for invalid arguments.
		if (count($this->piVars) > 1) {

			if (!empty($this->piVars['resumptionToken'])) {

				return $this->error('badResumptionToken');

			} else {

				return $this->error('badArgument');

			}

		}

		// Get set information.
		$additionalWhere = '';

		if (!$this->conf['show_userdefined']) {

			$additionalWhere = ' AND tx_dlf_collections.fe_cruser_id=0';

		}

		$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_collections.oai_name AS oai_name,tx_dlf_collections.label AS label',
			'tx_dlf_collections',
			'tx_dlf_collections.sys_language_uid IN (-1,0) AND tx_dlf_collections.pid='.intval($this->conf['pages']).$additionalWhere.tx_dlf_helper::whereClause('tx_dlf_collections'),
			'tx_dlf_collections.oai_name',
			'tx_dlf_collections.oai_name',
			''
		);

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

			return $this->error('noSetHierarchy');

		}

		// Get template and fill with data.
		$_itemTemplate = $this->cObj->getSubpart($this->template, '###LISTSETS_ITEM###');

		$content = '';

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			$markerArray = array (
				'###LISTSETS_SPEC###' => htmlspecialchars($resArray['oai_name']),
				'###LISTSETS_NAME###' => htmlspecialchars($resArray['label'])
			);

			$content .= $this->cObj->substituteMarkerArray($_itemTemplate, $markerArray);

		}

		return $this->cObj->substituteSubpart($this->cObj->getSubpart($this->template, '###LISTSETS###'), '###LISTSETS_ITEM###', $content, TRUE);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_oai.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/search/class.tx_dlf_oai.php']);
}

?>