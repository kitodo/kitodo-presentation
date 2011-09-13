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
 * Plugin 'DLF: OAI-PMH Interface' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
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
	 * This holds the OAI DOM object
	 *
	 * @var	DOMDocument
	 * @access protected
	 */
	protected $oai;

	/**
	 * This holds the configuration for all supported metadata prefixes
	 *
	 * @var	array
	 * @access protected
	 */
	protected $formats = array (
		'oai_dc' => array (
			'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
			'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
			'requiredFields' => array ('record_id'),
		),
		'epicur' => array (
			'schema' => 'http://www.persistent-identifier.de/xepicur/version1.0/xepicur.xsd',
			'namespace' => 'urn:nbn:de:1111-2004033116',
			'requiredFields' => array ('purl', 'urn'),
		),
		'mets' => array (
			'schema' => 'http://www.loc.gov/standards/mets/version17/mets.v1-7.xsd',
			'namespace' => 'http://www.loc.gov/METS/',
			'requiredFields' => array ('location'),
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
		$_result = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
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
	 * @return	DOMElement		XML node to add to the OAI response
	 */
	protected function error($type) {

		$this->error = TRUE;

		$error = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'error', $this->pi_getLL($type, $type, TRUE));

		$error->setAttribute('code', $type);

		return $error;

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
	 * Get unqualified Dublin Core data.
	 * @see http://www.openarchives.org/OAI/openarchivesprotocol.html#dublincore
	 *
	 * @access	protected
	 *
	 * @param	array		$metadata: The metadata array
	 *
	 * @return	DOMElement		XML node to add to the OAI response
	 */
	protected function getDcData(array $metadata) {

		$oai_dc = $this->oai->createElementNS($this->formats['oai_dc']['namespace'], 'oai_dc:dc');

		$oai_dc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');

		$oai_dc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

		$oai_dc->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', $this->formats['oai_dc']['namespace'].' '.$this->formats['oai_dc']['schema']);

		$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:identifier', htmlspecialchars($metadata['record_id'], ENT_NOQUOTES, 'UTF-8')));

		if (!empty($metadata['purl'])) {

			$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:identifier', htmlspecialchars($metadata['purl'], ENT_NOQUOTES, 'UTF-8')));

		}

		if (!empty($metadata['urn'])) {

			$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:identifier', htmlspecialchars($metadata['urn'], ENT_NOQUOTES, 'UTF-8')));

		}

		if (!empty($metadata['title'])) {

			$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:title', htmlspecialchars($metadata['title'], ENT_NOQUOTES, 'UTF-8')));

		}

		if (!empty($metadata['author'])) {

			$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:creator', htmlspecialchars($metadata['author'], ENT_NOQUOTES, 'UTF-8')));

		}

		if (!empty($metadata['year'])) {

			$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:date', htmlspecialchars($metadata['year'], ENT_NOQUOTES, 'UTF-8')));

		}

		if (!empty($metadata['place'])) {

			$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:coverage', htmlspecialchars($metadata['place'], ENT_NOQUOTES, 'UTF-8')));

		}

		$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:format', 'application/mets+xml'));

		$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:type', 'text'));

		if (!empty($metadata['partof'])) {

			$_result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_documents.record_id',
				'tx_dlf_documents',
				'tx_dlf_documents.uid='.intval($metadata['partof']).tx_dlf_helper::whereClause('tx_dlf_documents'),
				'',
				'',
				'1'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

				$_partof = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

				$oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:relation', htmlspecialchars($_partof['record_id'], ENT_NOQUOTES, 'UTF-8')));

			}

		}

		return $oai_dc;

	}

	/**
	 * Get epicur data.
	 * @see http://www.persistent-identifier.de/?link=210
	 *
	 * @access	protected
	 *
	 * @param	array		$metadata: The metadata array
	 *
	 * @return	DOMElement		XML node to add to the OAI response
	 */
	protected function getEpicurData(array $metadata) {

		// Create epicur element.
		$epicur = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:epicur');

		$epicur->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

		$epicur->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', $this->formats['epicur']['namespace'].' '.$this->formats['epicur']['schema']);

		// Add administrative data.
		$admin = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:administrative_data');

		$delivery = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:delivery');

		$update = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:update_status');

		// Do we update an URN or register a new one?
		if ($metadata['tstamp'] == $metadata['crdate']) {

			$update->setAttribute('type', 'urn_new');

		} else {

			$update->setAttribute('type', 'url_update_general');

		}

		$delivery->appendChild($update);

		$transfer = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:transfer');

		$transfer->setAttribute('type', 'http');

		$delivery->appendChild($transfer);

		$admin->appendChild($delivery);

		$epicur->appendChild($admin);

		// Add record data.
		$record = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:record');

		$identifier = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:identifier', htmlspecialchars($metadata['urn'], ENT_NOQUOTES, 'UTF-8'));

		$identifier->setAttribute('scheme', 'urn:nbn:de');

		$record->appendChild($identifier);

		$resource = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:resource');

		$ident = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:identifier', htmlspecialchars($metadata['purl'], ENT_NOQUOTES, 'UTF-8'));

		$ident->setAttribute('scheme', 'url');

		$ident->setAttribute('type', 'frontpage');

		$ident->setAttribute('role', 'primary');

		$resource->appendChild($ident);

		$format = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:format', 'text/html');

		$format->setAttribute('scheme', 'imt');

		$resource->appendChild($format);

		$record->appendChild($resource);

		$epicur->appendChild($record);

		return $epicur;

	}

	/**
	 * Get METS data.
	 * @see http://www.loc.gov/standards/mets/docs/mets.v1-7.html
	 *
	 * @access	protected
	 *
	 * @param	array		$metadata: The metadata array
	 *
	 * @return	DOMElement		XML node to add to the OAI response
	 */
	protected function getMetsData(array $metadata) {

		// Load METS file.
		$xml = new DOMDocument();

		$xml->load($metadata['location']);

		// Get root element.
		$root = $xml->getElementsByTagNameNS($this->formats['mets']['namespace'], 'mets');

		// Import node into DOMDocument.
		$mets = $this->oai->importNode($root->item(0), TRUE);

		return $mets;

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

		// Delete expired resumption tokens.
		$this->deleteExpiredTokens();

		// Create XML document.
		$this->oai = new DOMDocument('1.0', 'utf-8');

		// Add processing instruction (aka XSL stylesheet).
		if (!empty($this->conf['stylesheet'])) {

			// Resolve "EXT:" prefix in file path.
			if (substr($this->conf['stylesheet'], 0, 4) == 'EXT:') {

				list ($_extKey, $_filePath) = explode('/', substr($this->conf['stylesheet'], 4), 2);

				if (t3lib_extMgm::isLoaded($_extKey)) {

					$this->conf['stylesheet'] = t3lib_extMgm::siteRelPath($_extKey).$_filePath;

				}

			}

			$_stylesheet = t3lib_div::locationHeaderUrl($this->conf['stylesheet']);

		} else {

			// Use default stylesheet if no custom stylesheet is given.
			$_stylesheet = t3lib_div::locationHeaderUrl(t3lib_extMgm::siteRelPath($this->extKey).'plugins/oai/transform.xsl');

		}

		$this->oai->appendChild($this->oai->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="'.htmlspecialchars($_stylesheet, ENT_NOQUOTES, 'UTF-8').'"'));

		// Create root element.
		$root = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'OAI-PMH');

		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

		$root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd');

		// Add response date.
		$root->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'responseDate', gmdate('Y-m-d\TH:i:s\Z', $GLOBALS['EXEC_TIME'])));

		// Get response data.
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

		// Add request.
		$request = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'request', htmlspecialchars(t3lib_div::locationHeaderUrl($this->pi_getPageLink($GLOBALS['TSFE']->id)), ENT_NOQUOTES, 'UTF-8'));

		if (!$this->error) {

			foreach ($this->piVars as $key => $value) {

				$request->setAttribute($key, htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8'));

			}

		}

		$root->appendChild($request);

		// Add response data.
		$root->appendChild($response);

		// Build XML output.
		$this->oai->appendChild($root);

		$content = $this->oai->saveXML();

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

		$complete = FALSE;

		$todo = array ();

		$resume = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', $this->piVars['verb']);

		for ($i = $resultSet->metadata['offset'], $j = intval($resultSet->metadata['offset'] + $this->conf['limit']); $i < $j; $i++) {

			$todo[] = $resultSet->elements[$i]['uid'];

			if (empty($resultSet->elements[$i + 1])) {

				$complete = TRUE;

				break;

			}

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_documents.*,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
			'tx_dlf_documents',
			'tx_dlf_relations',
			'tx_dlf_collections',
			'AND tx_dlf_documents.uid IN ('.implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($todo)).') AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
			'tx_dlf_documents.uid',
			'tx_dlf_documents.tstamp',
			''
		);

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			// Add header node.
			$header = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');

			$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier', htmlspecialchars($resArray['record_id'], ENT_NOQUOTES, 'UTF-8')));

			$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp', gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp'])));

			// Check if document is deleted or hidden.
			// TODO: Use TYPO3 API functions here!
			if ($resArray['deleted'] || $resArray['hidden']) {

				// Add "deleted" status.
				$header->setAttribute('status', 'deleted');

				if ($this->piVars['verb'] == 'ListRecords') {

					// Add record node.
					$record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');

					$record->appendChild($header);

					$resume->appendChild($record);

				} elseif ($this->piVars['verb'] == 'ListIdentifiers') {

					$resume->appendChild($header);

				}

			} else {

				// Add sets.
				foreach (explode(' ', $resArray['collections']) as $_spec) {

					$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($_spec, ENT_NOQUOTES, 'UTF-8')));

				}

				if ($this->piVars['verb'] == 'ListRecords') {

					// Add record node.
					$record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');

					$record->appendChild($header);

					// Add metadata node.
					$metadata = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadata');

					switch ($this->piVars['metadataPrefix']) {

						case 'oai_dc':

							$metadata->appendChild($this->getDcData($resArray));

							break;

						case 'epicur':

							$metadata->appendChild($this->getEpicurData($resArray));

							break;

						case 'mets':

							$metadata->appendChild($this->getMetsData($resArray));

							break;

					}

					$record->appendChild($metadata);

					$resume->appendChild($record);

				} elseif ($this->piVars['verb'] == 'ListIdentifiers') {

					$resume->appendChild($header);

				}

			}

		}

		if (!$complete) {

			// Save result set to database and generate resumption token.
			$token = uniqid();

			$resultSet->metadata = array (
				'offset' => intval($resultSet->metadata['offset'] + $this->conf['limit']),
				'metadataPrefix' => $resultSet->metadata['metadataPrefix'],
			);

			$_result = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_dlf_tokens',
				array (
					'tstamp' => $GLOBALS['EXEC_TIME'],
					'token' => $token,
					'options' => serialize($resultSet),
					'ident' => 'oai',
				)
			);

			if ($GLOBALS['TYPO3_DB']->sql_affected_rows($_result) == 1) {

				$resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken', htmlspecialchars($token, ENT_NOQUOTES, 'UTF-8'));

			} else {

				trigger_error('Could not create resumption token', E_USER_ERROR);

			}

		} else {

			// Result set complete. No more resumption token needed.
			$resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken');

		}

		$resumptionToken->setAttribute('cursor', $resultSet->metadata['offset']);

		$resumptionToken->setAttribute('completeListSize', $resultSet->count);

		$resume->appendChild($resumptionToken);

		return $resume;

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

			$_result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dlf_documents.*,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
				'tx_dlf_documents',
				'tx_dlf_relations',
				'tx_dlf_collections',
				'AND tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['identifier'], 'tx_dlf_documents').' AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
				'tx_dlf_documents.uid',
				'tx_dlf_documents.tstamp',
				'1'
			);

			if (!$GLOBALS['TYPO3_DB']->sql_num_rows($_result)) {

				return $this->error('idDoesNotExist');

			} else {

				$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result);

				// Check for required fields.
				foreach ($this->formats[$this->piVars['metadataPrefix']]['requiredFields'] as $required) {

					if (empty($resArray[$required])) {

						return $this->error('cannotDisseminateFormat');

					}

				}

				// Add record node.
				$GetRecord = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'GetRecord');

				$record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');

				// Add header node.
				$header = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');

				$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier', htmlspecialchars($resArray['record_id'], ENT_NOQUOTES, 'UTF-8')));

				$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp', gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp'])));

				// Handle deleted documents.
				// TODO: Use TYPO3 API functions here!
				if ($resArray['deleted'] || $resArray['hidden']) {

					$header->setAttribute('status', 'deleted');

					$record->appendChild($header);

				} else {

					foreach (explode(' ', $resArray['collections']) as $_spec) {

						$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($_spec, ENT_NOQUOTES, 'UTF-8')));

					}

					$record->appendChild($header);

					// Add metadata node.
					$metadata = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadata');

					switch ($this->piVars['metadataPrefix']) {

						case 'oai_dc':

							$metadata->appendChild($this->getDcData($resArray));

							break;

						case 'epicur':

							$metadata->appendChild($this->getEpicurData($resArray));

							break;

						case 'mets':

							$metadata->appendChild($this->getMetsData($resArray));

							break;

					}

					$record->appendChild($metadata);

				}

				$GetRecord->appendChild($record);

				return $GetRecord;

			}

		}

	}

	/**
	 * Process verb "Identify"
	 *
	 * @access	protected
	 *
	 * @return	DOMElement		XML node to add to the OAI response
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

				list ($_timestamp) = $GLOBALS['TYPO3_DB']->sql_fetch_row($_result);

				$datestamp = gmdate('Y-m-d\TH:i:s\Z', $_timestamp);

			} else {

				$datestamp = '0000-00-00T00:00:00Z';

				trigger_error('No records found with PID '.$this->conf['pages'], E_USER_WARNING);

			}

			// Add identification node.
			$Identify = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'Identify');

			$Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'repositoryName', htmlspecialchars($resArray['oai_label'], ENT_NOQUOTES, 'UTF-8')));

			$Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'baseURL', htmlspecialchars(t3lib_div::locationHeaderUrl($this->pi_getPageLink($GLOBALS['TSFE']->id)), ENT_NOQUOTES, 'UTF-8')));

			$Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'protocolVersion', '2.0'));

			$Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'adminEmail', htmlspecialchars(trim(str_replace('mailto:', '', $resArray['contact'])), ENT_NOQUOTES, 'UTF-8')));

			$Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'earliestDatestamp', $datestamp));

			$Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'deletedRecord', 'transient'));

			$Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'granularity', 'YYYY-MM-DDThh:mm:ssZ'));

			return $Identify;

		} else {

			trigger_error('OAI interface needs more configuration', E_USER_ERROR);

			exit;

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

			// Check "from" and "until" for same granularity.
			if (!empty($this->piVars['from']) && !empty($this->piVars['until'])) {

				if (strlen($this->piVars['from']) != strlen($this->piVars['until'])) {

					return $this->error('badArgument');

				}

			}

		}

		// Select records from database.
		if (!$this->conf['show_userdefined']) {

			$where .= ' AND tx_dlf_collections.fe_cruser_id=0';

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_documents.*,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
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

				// Check for required fields.
				foreach ($this->formats[$this->piVars['metadataPrefix']]['requiredFields'] as $required) {

					if (empty($resArray[$required])) {

						// Skip documents with missing required fields.
						continue 2;

					}

				}

				$results[] = $resArray;

				// Save only UIDs for resumption token.
				$results['resumptionList'][] = array ('uid' => $resArray['uid']);

			}

			if (empty($results)) {

				return $this->error('noRecordsMatch');

			}

			$complete = FALSE;

			$ListIdentifiers = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'ListIdentifiers');

			for ($i = 0, $j = intval($this->conf['limit']); $i < $j; $i++) {

				$header = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');

				$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier', htmlspecialchars($results[$i]['record_id'], ENT_NOQUOTES, 'UTF-8')));

				$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp', gmdate('Y-m-d\TH:i:s\Z', $results[$i]['tstamp'])));

				// Check if document is deleted or hidden.
				// TODO: Use TYPO3 API functions here!
				if ($results[$i]['deleted'] || $results[$i]['hidden']) {

					// Add "deleted" status.
					$header->setAttribute('status', 'deleted');

				} else {

					// Add sets.
					foreach (explode(' ', $results[$i]['collections']) as $_spec) {

						$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($_spec, ENT_NOQUOTES, 'UTF-8')));

					}

				}

				$ListIdentifiers->appendChild($header);

				if (empty($results[$i + 1])) {

					$complete = TRUE;

					break;

				}

			}

			if (!$complete) {

				// Save result set as list object.
				$resultSet = t3lib_div::makeInstance('tx_dlf_list');

				$resultSet->reset();

				$resultSet->add($results['resumptionList']);

				// Save result set to database and generate resumption token.
				$token = uniqid();

				$resultSet->metadata = array (
					'offset' => intval($this->conf['limit']),
					'metadataPrefix' => $this->piVars['metadataPrefix'],
				);

				$_result = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_dlf_tokens',
					array (
						'tstamp' => $GLOBALS['EXEC_TIME'],
						'token' => $token,
						'options' => serialize($resultSet),
						'ident' => 'oai',
					)
				);

				if ($GLOBALS['TYPO3_DB']->sql_affected_rows($_result) == 1) {

					$resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken', htmlspecialchars($token, ENT_NOQUOTES, 'UTF-8'));

					$resumptionToken->setAttribute('cursor', '0');

					$resumptionToken->setAttribute('completeListSize', $resultSet->count);

					$ListIdentifiers->appendChild($resumptionToken);

				} else {

					trigger_error('Could not create resumption token', E_USER_ERROR);

				}

			}

			return $ListIdentifiers;

		}

	}

	/**
	 * Process verb "ListMetadataFormats"
	 *
	 * @access	protected
	 *
	 * @return	DOMElement		XML node to add to the OAI response
	 */
	protected function verbListMetadataFormats() {

		$resArray = array ();

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

				} else {

					$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

				}

			}

		}

		// Add metadata formats node.
		$ListMetadaFormats = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'ListMetadataFormats');

		foreach ($this->formats as $prefix => $details) {

			// Check for required fields.
			if (!empty($resArray)) {

				foreach ($details['requiredFields'] as $required) {

					if (empty($resArray[$required])) {

						// Skip metadata formats whose requirements are not met.
						continue 2;

					}

				}

			}

			// Add format node.
			$format = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadataFormat');

			$format->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadataPrefix', htmlspecialchars($prefix, ENT_NOQUOTES, 'UTF-8')));

			$format->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'schema', htmlspecialchars($details['schema'], ENT_NOQUOTES, 'UTF-8')));

			$format->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadataNamespace', htmlspecialchars($details['namespace'], ENT_NOQUOTES, 'UTF-8')));

			$ListMetadaFormats->appendChild($format);

		}

		return $ListMetadaFormats;

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

			// Check "from" and "until" for same granularity.
			if (!empty($this->piVars['from']) && !empty($this->piVars['until'])) {

				if (strlen($this->piVars['from']) != strlen($this->piVars['until'])) {

					return $this->error('badArgument');

				}

			}

		}

		// Select records from database.
		if (!$this->conf['show_userdefined']) {

			$where .= ' AND tx_dlf_collections.fe_cruser_id=0';

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_dlf_documents.*,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
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

				foreach ($this->formats[$this->piVars['metadataPrefix']]['requiredFields'] as $required) {

					if (empty($resArray[$required])) {

						// Skip records which do not meet the requirements.
						continue 2;

					}

				}

				$results[] = $resArray;

				// Save only UIDs for resumption token.
				$results['resumptionList'][] = array ('uid' => $resArray['uid']);

			}

			$complete = FALSE;

			$ListRecords = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'ListRecords');

			for ($i = 0, $j = intval($this->conf['limit']); $i < $j; $i++) {

				// Add record node.
				$record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');

				// Add header node.
				$header = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');

				$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier', htmlspecialchars($results[$i]['record_id'], ENT_NOQUOTES, 'UTF-8')));

				$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp', gmdate('Y-m-d\TH:i:s\Z', $results[$i]['tstamp'])));

				// Check if document is deleted or hidden.
				// TODO: Use TYPO3 API functions here!
				if ($results[$i]['deleted'] || $results[$i]['hidden']) {

					// Add "deleted" status.
					$header->setAttribute('status', 'deleted');

					$record->appendChild($header);

				} else {

					// Add sets.
					foreach (explode(' ', $results[$i]['collections']) as $_spec) {

						$header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($_spec, ENT_NOQUOTES, 'UTF-8')));

					}

					$record->appendChild($header);

					// Add metadata node.
					$metadata = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadata');

					switch ($this->piVars['metadataPrefix']) {

						case 'oai_dc':

							$metadata->appendChild($this->getDcData($results[$i]));

							break;

						case 'epicur':

							$metadata->appendChild($this->getEpicurData($results[$i]));

							break;

						case 'mets':

							$metadata->appendChild($this->getMetsData($results[$i]));

							break;

					}

					$record->appendChild($metadata);

				}

				$ListRecords->appendChild($record);

				if (empty($results[$i + 1])) {

					$complete = TRUE;

					break;

				}

			}

			if (!$complete) {

				// Save result set as list object.
				$resultSet = t3lib_div::makeInstance('tx_dlf_list');

				$resultSet->reset();

				$resultSet->add($results['resumptionList']);

				// Save result set to database and generate resumption token.
				$token = uniqid();

				$resultSet->metadata = array (
					'offset' => intval($this->conf['limit']),
					'metadataPrefix' => $this->piVars['metadataPrefix'],
				);

				$_result = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_dlf_tokens',
					array (
						'tstamp' => $GLOBALS['EXEC_TIME'],
						'token' => $token,
						'options' => serialize($resultSet),
						'ident' => 'oai',
					)
				);

				if ($GLOBALS['TYPO3_DB']->sql_affected_rows($_result) == 1) {

					$resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken', htmlspecialchars($token, ENT_NOQUOTES, 'UTF-8'));

					$resumptionToken->setAttribute('cursor', '0');

					$resumptionToken->setAttribute('completeListSize', $resultSet->count);

					$ListRecords->appendChild($resumptionToken);

				} else {

					trigger_error('Could not create resumption token', E_USER_ERROR);

				}

			}

			return $ListRecords;

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

		// Add set list node.
		$ListSets = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'ListSets');

		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($_result)) {

			// Add set node.
			$set = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'set');

			$set->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($resArray['oai_name'], ENT_NOQUOTES, 'UTF-8')));

			$set->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setName', htmlspecialchars($resArray['label'], ENT_NOQUOTES, 'UTF-8')));

			$ListSets->appendChild($set);

		}

		return $ListSets;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/oai/class.tx_dlf_oai.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/oai/class.tx_dlf_oai.php']);
}

?>