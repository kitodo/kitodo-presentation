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
 * Plugin 'DLF: OAI-PMH Interface' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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
        $result = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            'tx_dlf_tokens',
            'tx_dlf_tokens.ident="oai" AND tx_dlf_tokens.tstamp<'.intval($GLOBALS['EXEC_TIME'] - $this->conf['expired'])
        );

        if ($GLOBALS['TYPO3_DB']->sql_affected_rows() === -1) {
            // Deletion failed.
            $this->devLog('[tx_dlf_oai->deleteExpiredTokens()] Could not delete expired resumption tokens', SYSLOG_SEVERITY_WARNING);
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

        $error = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'error', htmlspecialchars($this->pi_getLL($type, $type, FALSE), ENT_NOQUOTES, 'UTF-8'));
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

        $allowedParams = array (
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
        foreach ($allowedParams as $param) {
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP($param)) {
                $this->piVars[$param] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($param);
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
        $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:type', 'Text'));

        if (!empty($metadata['partof'])) {
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_documents.record_id',
                'tx_dlf_documents',
                'tx_dlf_documents.uid='.intval($metadata['partof']).tx_dlf_helper::whereClause('tx_dlf_documents'),
                '',
                '',
                '1'
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
                $partof = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

                $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:relation', htmlspecialchars($partof['record_id'], ENT_NOQUOTES, 'UTF-8')));
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

        // Define all XML elements with or without qualified namespace.
        if (empty($this->conf['unqualified_epicur'])) {
            $epicur = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:epicur');

            $admin = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:administrative_data');

            $delivery = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:delivery');

            $update = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:update_status');

            $transfer = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:transfer');

            $format = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:format', 'text/html');

            $record = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:record');

            $identifier = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:identifier', htmlspecialchars($metadata['urn'], ENT_NOQUOTES, 'UTF-8'));

            $resource = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:resource');

            $ident = $this->oai->createElementNS($this->formats['epicur']['namespace'], 'epicur:identifier', htmlspecialchars($metadata['purl'], ENT_NOQUOTES, 'UTF-8'));

        } else {
            $epicur = $this->oai->createElement('epicur');
            $epicur->setAttribute('xmlns', $this->formats['epicur']['namespace']);

            $admin = $this->oai->createElement('administrative_data');

            $delivery = $this->oai->createElement('delivery');

            $update = $this->oai->createElement('update_status');

            $transfer = $this->oai->createElement('transfer');

            $format = $this->oai->createElement('format', 'text/html');

            $record = $this->oai->createElement('record');

            $identifier = $this->oai->createElement('identifier', htmlspecialchars($metadata['urn'], ENT_NOQUOTES, 'UTF-8'));

            $resource = $this->oai->createElement('resource');

            $ident = $this->oai->createElement('identifier', htmlspecialchars($metadata['purl'], ENT_NOQUOTES, 'UTF-8'));
        }

        // Add attributes and build XML tree.
        $epicur->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $epicur->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', $this->formats['epicur']['namespace'].' '.$this->formats['epicur']['schema']);

        // Do we update an URN or register a new one?
        if ($metadata['tstamp'] == $metadata['crdate']) {
            $update->setAttribute('type', 'urn_new');
        } else {
            $update->setAttribute('type', 'url_update_general');
        }

        $delivery->appendChild($update);

        $transfer->setAttribute('type', 'http');

        $delivery->appendChild($transfer);

        $admin->appendChild($delivery);

        $epicur->appendChild($admin);

        $identifier->setAttribute('scheme', 'urn:nbn:de');

        $record->appendChild($identifier);

        $ident->setAttribute('scheme', 'url');
        $ident->setAttribute('type', 'frontpage');
        $ident->setAttribute('role', 'primary');

        $resource->appendChild($ident);

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

        $mets = NULL;

        // Load METS file.
        $xml = new DOMDocument();

        if ($xml->load($metadata['location'])) {
            // Get root element.
            $root = $xml->getElementsByTagNameNS($this->formats['mets']['namespace'], 'mets');

            if ($root->item(0) instanceof DOMNode) {
                // Import node into DOMDocument.
                $mets = $this->oai->importNode($root->item(0), TRUE);
            } else {
                    $this->devLog('[tx_dlf_oai->getMetsData([data])] No METS part found in document with location "'.$metadata['location'].'"', SYSLOG_SEVERITY_ERROR, $metadata);
            }
        } else {
            $this->devLog('[tx_dlf_oai->getMetsData([data])] Could not load XML file from "'.$metadata['location'].'"', SYSLOG_SEVERITY_ERROR, $metadata);
        }

        if ($mets === NULL) {
            $mets = $this->oai->createElementNS('http://kitodo.org/', 'kitodo:error', htmlspecialchars($this->pi_getLL('error', 'Error!', FALSE), ENT_NOQUOTES, 'UTF-8'));
        }

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
        $this->oai = new DOMDocument('1.0', 'UTF-8');

        // Add processing instruction (aka XSL stylesheet).
        if (!empty($this->conf['stylesheet'])) {
            // Resolve "EXT:" prefix in file path.
            if (substr($this->conf['stylesheet'], 0, 4) == 'EXT:') {

                list ($extKey, $filePath) = explode('/', substr($this->conf['stylesheet'], 4), 2);

                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey)) {
                    $this->conf['stylesheet'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($extKey).$filePath;
                }
            }

            $stylesheet = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->conf['stylesheet']);

        } else {
            // Use default stylesheet if no custom stylesheet is given.
            $stylesheet = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'plugins/oai/transform.xsl');
        }

        $this->oai->appendChild($this->oai->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="'.htmlspecialchars($stylesheet, ENT_NOQUOTES, 'UTF-8').'"'));

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
        $linkConf = array (
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1
        );

        $request = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'request', htmlspecialchars($this->cObj->typoLink_URL($linkConf), ENT_NOQUOTES, 'UTF-8'));

        if (!$this->error) {
            foreach ($this->piVars as $key => $value) {
                $request->setAttribute($key, htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8'));
            }
        }

        $root->appendChild($request);
        $root->appendChild($response);

        $this->oai->appendChild($root);

        $content = $this->oai->saveXML();

        // Clean output buffer.
        \TYPO3\CMS\Core\Utility\GeneralUtility::cleanOutputBuffers();

        // Send headers.
        header('HTTP/1.1 200 OK');
        header('Cache-Control: no-cache');
        header('Content-Length: '.strlen($content));
        header('Content-Type: text/xml; charset=utf-8');
        header('Date: '.date('r', $GLOBALS['EXEC_TIME']));
        header('Expires: '.date('r', $GLOBALS['EXEC_TIME'] + $this->conf['expired']));

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
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_tokens.options AS options',
            'tx_dlf_tokens',
            'tx_dlf_tokens.ident="oai" AND tx_dlf_tokens.token='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['resumptionToken'], 'tx_dlf_tokens'),
            '',
            '',
            '1'
        );

        if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            // No resumption token found or resumption token expired.
            return $this->error('badResumptionToken');
        }

        $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

        $resultSet = unserialize($resArray['options']);

        return $this->generateOutputForDocumentList($resultSet);

    }

    /**
     * Process verb "GetRecord"
     *
     * @access	protected
     *
     * @return	string		Substitution for subpart "###RESPONSE###"
     */
    protected function verbGetRecord() {

        if (count($this->piVars) != 3 || empty($this->piVars['metadataPrefix']) || empty($this->piVars['identifier'])) {
            return $this->error('badArgument');
        }

        if (!in_array($this->piVars['metadataPrefix'], array_keys($this->formats))) {
            return $this->error('cannotDisseminateFormat');
        }

        $where = '';

        if (!$this->conf['show_userdefined']) {
            $where .= ' AND tx_dlf_collections.fe_cruser_id=0';
        }

        $record = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
            'tx_dlf_documents.*,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
            'tx_dlf_documents',
            'tx_dlf_relations',
            'tx_dlf_collections',
            'AND tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['identifier'], 'tx_dlf_documents').' AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
            'tx_dlf_documents.uid',
            'tx_dlf_documents.tstamp',
            '1'
        );

        if (!$GLOBALS['TYPO3_DB']->sql_num_rows($record)) {
            return $this->error('idDoesNotExist');
        }

        $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($record);

        // Check for required fields.
        foreach ($this->formats[$this->piVars['metadataPrefix']]['requiredFields'] as $required) {
            if (empty($resArray[$required])) {
                return $this->error('cannotDisseminateFormat');
            }
        }

        $GetRecord = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'GetRecord');

        $recordNode = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');

        $headerNode = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');
        $headerNode->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier', htmlspecialchars($resArray['record_id'], ENT_NOQUOTES, 'UTF-8')));
        $headerNode->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp', gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp'])));

        // Handle deleted documents.
        // TODO: Use TYPO3 API functions here!
        if ($resArray['deleted'] || $resArray['hidden']) {
            $headerNode->setAttribute('status', 'deleted');

            $recordNode->appendChild($headerNode);

        } else {
            foreach (explode(' ', $resArray['collections']) as $spec) {
                $headerNode->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($spec, ENT_NOQUOTES, 'UTF-8')));
            }

            $recordNode->appendChild($headerNode);

            $metadataNode = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadata');

            switch ($this->piVars['metadataPrefix']) {
                case 'oai_dc':
                    $metadataNode->appendChild($this->getDcData($resArray));
                    break;

                case 'epicur':
                    $metadataNode->appendChild($this->getEpicurData($resArray));
                    break;

                case 'mets':
                    $metadataNode->appendChild($this->getMetsData($resArray));
                    break;
            }

            $recordNode->appendChild($metadataNode);
        }

        $GetRecord->appendChild($recordNode);

        return $GetRecord;
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
        // Use default values for an installation with incomplete plugin configuration.

        $adminEmail = 'unknown@example.org';
        $repositoryName = 'Kitodo.Presentation OAI-PMH interface (incomplete configuration)';

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_libraries.oai_label AS oai_label,tx_dlf_libraries.contact AS contact',
            'tx_dlf_libraries',
            'tx_dlf_libraries.pid='.intval($this->conf['pages']).' AND tx_dlf_libraries.uid='.intval($this->conf['library']).tx_dlf_helper::whereClause('tx_dlf_libraries'),
            '',
            '',
            ''
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

            $adminEmail = htmlspecialchars(trim(str_replace('mailto:', '', $resArray['contact'])), ENT_NOQUOTES);
            $repositoryName = htmlspecialchars($resArray['oai_label'], ENT_NOQUOTES);

        } else {
            $this->devLog('[tx_dlf_oai->verbIdentify()] Incomplete plugin configuration', SYSLOG_SEVERITY_NOTICE);
        }

        // Get earliest datestamp. Use a default value if that fails.

        $earliestDatestamp = '0000-00-00T00:00:00Z';

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_documents.tstamp AS tstamp',
            'tx_dlf_documents',
            'tx_dlf_documents.pid='.intval($this->conf['pages']),
            '',
            'tx_dlf_documents.tstamp ASC',
            '1'
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            list ($timestamp) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);
            $earliestDatestamp = gmdate('Y-m-d\TH:i:s\Z', $timestamp);
        } else {
            $this->devLog('[tx_dlf_oai->verbIdentify()] No records found with PID "'.$this->conf['pages'].'"', SYSLOG_SEVERITY_NOTICE);
        }

        $linkConf = array (
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1
        );
        $baseURL = htmlspecialchars($this->cObj->typoLink_URL($linkConf), ENT_NOQUOTES);

        // Add identification node.
        $Identify = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'Identify');
        $Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'repositoryName', $repositoryName));
        $Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'baseURL', $baseURL));
        $Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'protocolVersion', '2.0'));
        $Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'adminEmail', $adminEmail));
        $Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'earliestDatestamp', $earliestDatestamp));
        $Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'deletedRecord', 'transient'));
        $Identify->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'granularity', 'YYYY-MM-DDThh:mm:ssZ'));

        return $Identify;
    }

    /**
     * Process verb "ListIdentifiers"
     *
     * @access	protected
     *
     * @return	string		Substitution for subpart "###RESPONSE###"
     */
    protected function verbListIdentifiers() {

        // If we have a resumption token we can continue our work
        if (!empty($this->piVars['resumptionToken'])) {
            // "resumptionToken" is an exclusive argument.
            if (count($this->piVars) > 2) {
                return $this->error('badArgument');
            } else {
                return $this->resume();
            }
        }

        // "metadataPrefix" is required and "identifier" is not allowed.
        if (empty($this->piVars['metadataPrefix']) || !empty($this->piVars['identifier'])) {
            return $this->error('badArgument');
        }

        if (!in_array($this->piVars['metadataPrefix'], array_keys($this->formats))) {
            return $this->error('cannotDisseminateFormat');
        }

        try {
            $documentSet = $this->fetchDocumentUIDs();
        } catch (Exception $exception) {
            return $this->error($exception->getMessage());
        }

        $resultSet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

        $resultSet->reset();
        $resultSet->add($documentSet);
        $resultSet->metadata = array (
            'completeListSize' => count($documentSet),
            'metadataPrefix' => $this->piVars['metadataPrefix'],
        );

        return $this->generateOutputForDocumentList($resultSet);

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
            }

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

            $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
        }

        // Add metadata formats node.
        $ListMetadaFormats = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'ListMetadataFormats');

        foreach ($this->formats as $prefix => $details) {
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

        }

        if (empty($this->piVars['metadataPrefix']) || !empty($this->piVars['identifier'])) {
            // "metadataPrefix" is required and "identifier" is not allowed.
            return $this->error('badArgument');
        }

        // Check "metadataPrefix" for valid value.
        if (!in_array($this->piVars['metadataPrefix'], array_keys($this->formats))) {
            return $this->error('cannotDisseminateFormat');
        }

        try {
            $documentSet = $this->fetchDocumentUIDs();
        } catch (Exception $exception) {
            return $this->error($exception->getMessage());
        }

        $resultSet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_list');

        $resultSet->reset();
        $resultSet->add($documentSet);
        $resultSet->metadata = array (
            'completeListSize' => count($documentSet),
            'metadataPrefix' => $this->piVars['metadataPrefix'],
        );

        return $this->generateOutputForDocumentList($resultSet);
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

        $where = '';

        if (!$this->conf['show_userdefined']) {
            $where = ' AND tx_dlf_collections.fe_cruser_id=0';
        }

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_collections.oai_name AS oai_name,tx_dlf_collections.label AS label',
            'tx_dlf_collections',
            'tx_dlf_collections.sys_language_uid IN (-1,0) AND NOT tx_dlf_collections.oai_name=\'\' AND tx_dlf_collections.pid='.intval($this->conf['pages']).$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
            'tx_dlf_collections.oai_name',
            'tx_dlf_collections.oai_name',
            ''
        );

        if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            return $this->error('noSetHierarchy');
        }

        $ListSets = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'ListSets');

        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

            $set = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'set');

            $set->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($resArray['oai_name'], ENT_NOQUOTES, 'UTF-8')));
            $set->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setName', htmlspecialchars($resArray['label'], ENT_NOQUOTES, 'UTF-8')));

            $ListSets->appendChild($set);
        }

        return $ListSets;
    }



    /**
     * @return array
     * @throws Exception
     */
    private function fetchDocumentUIDs() {
        $solr_query = '';

        if (!$this->conf['show_userdefined']) {
            $where = ' AND tx_dlf_collections.fe_cruser_id=0';
        }

        // Check "set" for valid value.
        if (!empty($this->piVars['set'])) {

            // For SOLR we need the index_name of the collection,
            // For DB Query we need the UID of the collection
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_collections.index_name AS index_name, tx_dlf_collections.uid AS uid, tx_dlf_collections.index_search as index_query ',
                'tx_dlf_collections',
                'tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.oai_name='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['set'],
                    'tx_dlf_collections').$where.tx_dlf_helper::whereClause('tx_dlf_collections'),
                '',
                '',
                '1'
            );

            if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
                throw new Exception('noSetHierarchy');
            }

            $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

            if ($resArray['index_query'] != "") {
                $solr_query .= '('.$resArray['index_query'].')';
            } else {
                $solr_query .= 'collection:'.'"'.$resArray['index_name'].'"';
            }

        } else {
            // If no set is specified we have to query for all collections
            $solr_query .= 'collection:* NOT collection:""';

        }

        // Check for required fields.
        foreach ($this->formats[$this->piVars['metadataPrefix']]['requiredFields'] as $required) {
            $solr_query .= ' NOT '.$required.':""';
        }

        // toplevel="true" is always required
        $solr_query .= ' AND toplevel:"true"';

        $from = "*";
        // Check "from" for valid value.
        if (!empty($this->piVars['from'])) {

            // Is valid format?
            if (is_array($date_array = strptime($this->piVars['from'],
                    '%Y-%m-%dT%H:%M:%SZ')) || is_array($date_array = strptime($this->piVars['from'], '%Y-%m-%d'))) {

                $timestamp = gmmktime($date_array['tm_hour'], $date_array['tm_min'], $date_array['tm_sec'], $date_array['tm_mon'] + 1,
                    $date_array['tm_mday'], $date_array['tm_year'] + 1900);

                $from = date("Y-m-d", $timestamp).'T'.date("H:i:s", $timestamp).'.000Z';

            } else {
                throw new Exception('badArgument');
            }
        }

        $until = "*";
        // Check "until" for valid value.
        if (!empty($this->piVars['until'])) {

            // Is valid format?
            if (is_array($date_array = strptime($this->piVars['until'],
                    '%Y-%m-%dT%H:%M:%SZ')) || is_array($date_array = strptime($this->piVars['until'], '%Y-%m-%d'))) {

                $timestamp = gmmktime($date_array['tm_hour'], $date_array['tm_min'], $date_array['tm_sec'], $date_array['tm_mon'] + 1,
                    $date_array['tm_mday'], $date_array['tm_year'] + 1900);

                $until = date("Y-m-d", $timestamp).'T'.date("H:i:s", $timestamp).'.999Z';

                if ($from != "*" && $from > $until) {
                    throw new Exception('badArgument');
                }

            } else {
                throw new Exception('badArgument');
            }
        }

        // Check "from" and "until" for same granularity.
        if (!empty($this->piVars['from']) && !empty($this->piVars['until'])) {
            if (strlen($this->piVars['from']) != strlen($this->piVars['until'])) {
                throw new Exception('badArgument');
            }
        }

        $solr_query .= ' AND timestamp:['.$from.' TO '.$until.']';

        $documentSet = array ();

        $solr = tx_dlf_solr::getInstance($this->conf['solrcore']);

        if (intval($this->conf['solr_limit']) > 0) {
            $solr->limit = intval($this->conf['solr_limit']);
        }

        // We only care about the UID in the results and want them sorted
        $parameters = array (
            "fields" => "uid",
            "sort" => array (
                "uid" => "asc"
            )
        );

        $result = $solr->search_raw($solr_query, $parameters);

        if (empty($result)) {
            throw new Exception('noRecordsMatch');
        }

        foreach ($result as $doc) {
            $documentSet[] = $doc->uid;
        }

        return $documentSet;
    }

    /**
     * @param tx_dlf_list $documentListSet
     * @return DOMElement
     */
    private function generateOutputForDocumentList($documentListSet) {

            $documentsToProcess = $documentListSet->removeRange(0, intval($this->conf['limit']));
            $verb = $this->piVars['verb'];

        $documents = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
            'tx_dlf_documents.*,GROUP_CONCAT(DISTINCT tx_dlf_collections.oai_name ORDER BY tx_dlf_collections.oai_name SEPARATOR " ") AS collections',
            'tx_dlf_documents',
            'tx_dlf_relations',
            'tx_dlf_collections',
            'AND tx_dlf_documents.uid IN ('.implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($documentsToProcess)).') AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').tx_dlf_helper::whereClause('tx_dlf_collections'),
            'tx_dlf_documents.uid',
            'tx_dlf_documents.tstamp',
            $this->conf['limit']
        );

        $output = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', $verb);

        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($documents)) {
            // Add header node.
            $header = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');

            $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier', htmlspecialchars($resArray['record_id'], ENT_NOQUOTES, 'UTF-8')));
            $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp', gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp'])));

            // Check if document is deleted or hidden.
            // TODO: Use TYPO3 API functions here!
            if ($resArray['deleted'] || $resArray['hidden']) {
                // Add "deleted" status.
                $header->setAttribute('status', 'deleted');

                if ($verb == 'ListRecords') {
                    // Add record node.
                    $record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');

                    $record->appendChild($header);
                    $output->appendChild($record);

                } elseif ($verb == 'ListIdentifiers') {
                    $output->appendChild($header);
                }

            } else {
                // Add sets.
                foreach (explode(' ', $resArray['collections']) as $spec) {
                    $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($spec, ENT_NOQUOTES, 'UTF-8')));
                }

                if ($verb == 'ListRecords') {
                    // Add record node.
                    $record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');

                    $record->appendChild($header);

                    // Add metadata node.
                    $metadata = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadata');

                    $metadataPrefix = $this->piVars['metadataPrefix'];
                    if (!$metadataPrefix) {
                        // If we resume an action the metadataPrefix is stored with the documentSet
                        $metadataPrefix = $documentListSet->metadata['metadataPrefix'];
                    }

                    switch ($metadataPrefix) {
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
                    $output->appendChild($record);

                } elseif ($verb == 'ListIdentifiers') {
                    $output->appendChild($header);
                }
            }
        }

        $output->appendChild($this->generateResumptionTokenForDocumentListSet($documentListSet));

        return $output;
    }

    /**
     * @param tx_dlf_list $documentListSet
     * @return DOMElement
     */
    private function generateResumptionTokenForDocumentListSet($documentListSet) {

        if ($documentListSet->count() != 0) {

            $token = uniqid();

            $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                'tx_dlf_tokens',
                array (
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'token' => $token,
                    'options' => serialize($documentListSet),
                    'ident' => 'oai',
                )
            );

            if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 1) {

                $resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken', htmlspecialchars($token, ENT_NOQUOTES, 'UTF-8'));

            } else {
                $this->devLog('[tx_dlf_oai->verb'.$this->piVars['verb'].'()] Could not create resumption token', SYSLOG_SEVERITY_ERROR);

            }
        } else {
            // Result set complete. We don't need a token.
            $resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken');
        }

        $resumptionToken->setAttribute('cursor', intval($documentListSet->metadata['completeListSize']) - count($documentListSet));
        $resumptionToken->setAttribute('completeListSize', $documentListSet->metadata['completeListSize']);
        $resumptionToken->setAttribute('expirationDate', gmdate('Y-m-d\TH:i:s\Z', $GLOBALS['EXEC_TIME'] + $this->conf['expired']));

        return $resumptionToken;
    }

    private function devLog($message, $severity, $data = NULL) {
        if (TYPO3_DLOG) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($message, $this->extKey, $severity, $data);
        }
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/oai/class.tx_dlf_oai.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/oai/class.tx_dlf_oai.php']);

}
