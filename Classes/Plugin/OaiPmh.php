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

namespace Kitodo\Dlf\Plugin;

use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Plugin 'OAI-PMH Interface' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class OaiPmh extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/OaiPmh.php';

    /**
     * Did an error occur?
     *
     * @var bool
     * @access protected
     */
    protected $error = false;

    /**
     * This holds the OAI DOM object
     *
     * @var \DOMDocument
     * @access protected
     */
    protected $oai;

    /**
     * This holds the configuration for all supported metadata prefixes
     *
     * @var array
     * @access protected
     */
    protected $formats = [
        'oai_dc' => [
            'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
            'requiredFields' => ['record_id'],
        ],
        'epicur' => [
            'schema' => 'http://www.persistent-identifier.de/xepicur/version1.0/xepicur.xsd',
            'namespace' => 'urn:nbn:de:1111-2004033116',
            'requiredFields' => ['purl', 'urn'],
        ],
        'mets' => [
            'schema' => 'http://www.loc.gov/standards/mets/version17/mets.v1-7.xsd',
            'namespace' => 'http://www.loc.gov/METS/',
            'requiredFields' => ['location'],
        ]
    ];

    /**
     * Delete expired resumption tokens
     *
     * @access protected
     *
     * @return void
     */
    protected function deleteExpiredTokens()
    {
        // Delete expired resumption tokens.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_tokens');

        $result = $queryBuilder
            ->delete('tx_dlf_tokens')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_tokens.ident', $queryBuilder->createNamedParameter('oai')),
                $queryBuilder->expr()->lt('tx_dlf_tokens.tstamp', $queryBuilder->createNamedParameter((int) ($GLOBALS['EXEC_TIME'] - $this->conf['expired'])))
            )
            ->execute();

        if ($result === -1) {
            // Deletion failed.
            $this->logger->warning('Could not delete expired resumption tokens');
        }
    }

    /**
     * Process error
     *
     * @access protected
     *
     * @param string $type: Error type
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function error($type)
    {
        $this->error = true;
        $error = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'error', htmlspecialchars($this->pi_getLL($type, $type), ENT_NOQUOTES, 'UTF-8'));
        $error->setAttribute('code', $type);
        return $error;
    }

    /**
     * Load URL parameters
     *
     * @access protected
     *
     * @return void
     */
    protected function getUrlParams()
    {
        $allowedParams = [
            'verb',
            'identifier',
            'metadataPrefix',
            'from',
            'until',
            'set',
            'resumptionToken'
        ];
        // Clear plugin variables.
        $this->piVars = [];
        // Set only allowed parameters.
        foreach ($allowedParams as $param) {
            if (GeneralUtility::_GP($param)) {
                $this->piVars[$param] = GeneralUtility::_GP($param);
            }
        }
    }

    /**
     * Get unqualified Dublin Core data.
     * @see http://www.openarchives.org/OAI/openarchivesprotocol.html#dublincore
     *
     * @access protected
     *
     * @param array $metadata: The metadata array
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function getDcData(array $metadata)
    {
        $oai_dc = $this->oai->createElementNS($this->formats['oai_dc']['namespace'], 'oai_dc:dc');
        $oai_dc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $oai_dc->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $oai_dc->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', $this->formats['oai_dc']['namespace'] . ' ' . $this->formats['oai_dc']['schema']);
        $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:identifier', htmlspecialchars($metadata['record_id'], ENT_NOQUOTES, 'UTF-8')));
        if (!empty($metadata['purl'])) {
            $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:identifier', htmlspecialchars($metadata['purl'], ENT_NOQUOTES, 'UTF-8')));
        }
        if (!empty($metadata['prod_id'])) {
            $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:identifier', 'kitodo:production:' . htmlspecialchars($metadata['prod_id'], ENT_NOQUOTES, 'UTF-8')));
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
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $result = $queryBuilder
                ->select('tx_dlf_documents.record_id')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.uid', intval($metadata['partof'])),
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->setMaxResults(1)
                ->execute();

            $allResults = $result->fetchAll();

            if (count($allResults) == 1) {
                $partof = $allResults[0];
                $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:relation', htmlspecialchars($partof['record_id'], ENT_NOQUOTES, 'UTF-8')));
            }
        }
        if (!empty($metadata['license'])) {
            $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:rights', htmlspecialchars($metadata['license'], ENT_NOQUOTES, 'UTF-8')));
        }
        if (!empty($metadata['terms'])) {
            $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:rights', htmlspecialchars($metadata['terms'], ENT_NOQUOTES, 'UTF-8')));
        }
        if (!empty($metadata['restrictions'])) {
            $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:rights', htmlspecialchars($metadata['restrictions'], ENT_NOQUOTES, 'UTF-8')));
        }
        if (!empty($metadata['out_of_print'])) {
            $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:rights', htmlspecialchars($metadata['out_of_print'], ENT_NOQUOTES, 'UTF-8')));
        }
        if (!empty($metadata['rights_info'])) {
            $oai_dc->appendChild($this->oai->createElementNS('http://purl.org/dc/elements/1.1/', 'dc:rights', htmlspecialchars($metadata['rights_info'], ENT_NOQUOTES, 'UTF-8')));
        }
        return $oai_dc;
    }

    /**
     * Get epicur data.
     * @see http://www.persistent-identifier.de/?link=210
     *
     * @access protected
     *
     * @param array $metadata: The metadata array
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function getEpicurData(array $metadata)
    {
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
        $epicur->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', $this->formats['epicur']['namespace'] . ' ' . $this->formats['epicur']['schema']);
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
     * @access protected
     *
     * @param array $metadata: The metadata array
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function getMetsData(array $metadata)
    {
        $mets = null;
        // Load METS file.
        $xml = new \DOMDocument();
        if ($xml->load($metadata['location'])) {
            // Get root element.
            $root = $xml->getElementsByTagNameNS($this->formats['mets']['namespace'], 'mets');
            if ($root->item(0) instanceof \DOMNode) {
                // Import node into \DOMDocument.
                $mets = $this->oai->importNode($root->item(0), true);
            } else {
                $this->logger->error('No METS part found in document with location "' . $metadata['location'] . '"');
            }
        } else {
            $this->logger->error('Could not load XML file from "' . $metadata['location'] . '"');
        }
        if ($mets === null) {
            $mets = $this->oai->createElementNS('http://kitodo.org/', 'kitodo:error', htmlspecialchars($this->pi_getLL('error', 'Error!'), ENT_NOQUOTES, 'UTF-8'));
        }
        return $mets;
    }

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return void
     */
    public function main($content, $conf)
    {
        // Initialize plugin.
        $this->init($conf);
        // Turn cache off.
        $this->setCache(false);
        // Get GET and POST variables.
        $this->getUrlParams();
        // Delete expired resumption tokens.
        $this->deleteExpiredTokens();
        // Create XML document.
        $this->oai = new \DOMDocument('1.0', 'UTF-8');
        // Add processing instruction (aka XSL stylesheet).
        if (!empty($this->conf['stylesheet'])) {
            // Resolve "EXT:" prefix in file path.
            if (strpos($this->conf['stylesheet'], 'EXT:') === 0) {
                [$extKey, $filePath] = explode('/', substr($this->conf['stylesheet'], 4), 2);
                if (ExtensionManagementUtility::isLoaded($extKey)) {
                    $this->conf['stylesheet'] = PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($extKey)) . $filePath;
                }
            }
            $stylesheet = GeneralUtility::locationHeaderUrl($this->conf['stylesheet']);
        } else {
            // Use default stylesheet if no custom stylesheet is given.
            $stylesheet = GeneralUtility::locationHeaderUrl(PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($this->extKey)) . 'Resources/Public/Stylesheets/OaiPmh.xsl');
        }
        $this->oai->appendChild($this->oai->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="' . htmlspecialchars($stylesheet, ENT_NOQUOTES, 'UTF-8') . '"'));
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
        $linkConf = [
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http']
        ];
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
        ob_end_clean();
        // Send headers.
        header('HTTP/1.1 200 OK');
        header('Cache-Control: no-cache');
        header('Content-Length: ' . strlen($content));
        header('Content-Type: text/xml; charset=utf-8');
        header('Date: ' . date('r', $GLOBALS['EXEC_TIME']));
        header('Expires: ' . date('r', $GLOBALS['EXEC_TIME'] + $this->conf['expired']));
        echo $content;
        exit;
    }

    /**
     * Continue with resumption token
     *
     * @access protected
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function resume()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_tokens');

        // Get resumption token.
        $result = $queryBuilder
            ->select('tx_dlf_tokens.options AS options')
            ->from('tx_dlf_tokens')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_tokens.ident', $queryBuilder->createNamedParameter('oai')),
                $queryBuilder->expr()->eq('tx_dlf_tokens.token', $queryBuilder->expr()->literal($this->piVars['resumptionToken']))
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $result->fetchAll();

        if (count($allResults) > 1) {
            // No resumption token found or resumption token expired.
            return $this->error('badResumptionToken');
        }
        $resArray = $allResults[0];
        $resultSet = unserialize($resArray['options']);
        return $this->generateOutputForDocumentList($resultSet);
    }

    /**
     * Process verb "GetRecord"
     *
     * @access protected
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function verbGetRecord()
    {
        if (count($this->piVars) !== 3 || empty($this->piVars['metadataPrefix']) || empty($this->piVars['identifier'])) {
            return $this->error('badArgument');
        }
        if (!array_key_exists($this->piVars['metadataPrefix'], $this->formats)) {
            return $this->error('cannotDisseminateFormat');
        }
        $where = '';
        if (!$this->conf['show_userdefined']) {
            $where .= 'AND tx_dlf_collections.fe_cruser_id=0 ';
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_dlf_documents');

        $sql = 'SELECT `tx_dlf_documents`.*, GROUP_CONCAT(DISTINCT `tx_dlf_collections`.`oai_name` ORDER BY `tx_dlf_collections`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `tx_dlf_documents` ' .
            'INNER JOIN `tx_dlf_relations` ON `tx_dlf_relations`.`uid_local` = `tx_dlf_documents`.`uid` ' .
            'INNER JOIN `tx_dlf_collections` ON `tx_dlf_collections`.`uid` = `tx_dlf_relations`.`uid_foreign` ' .
            'WHERE `tx_dlf_documents`.`record_id` = ? ' .
            'AND `tx_dlf_documents`.`pid` = ? ' .
            'AND `tx_dlf_collections`.`pid` = ? ' .
            'AND `tx_dlf_relations`.`ident`="docs_colls" ' .
            $where .
            'AND ' . Helper::whereExpression('tx_dlf_collections');

        $values = [
            $this->piVars['identifier'],
            $this->conf['pages'],
            $this->conf['pages']
        ];
        $types = [
            Connection::PARAM_STR,
            Connection::PARAM_INT,
            Connection::PARAM_INT
        ];
        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        $statement = $connection->executeQuery($sql, $values, $types);

        $resArray = $statement->fetch();

        if (!$resArray['uid']) {
            return $this->error('idDoesNotExist');
        }

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
        if (
            $resArray['deleted']
            || $resArray['hidden']
        ) {
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
     * @access protected
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function verbIdentify()
    {
        // Check for invalid arguments.
        if (count($this->piVars) > 1) {
            return $this->error('badArgument');
        }
        // Get repository name and administrative contact.
        // Use default values for an installation with incomplete plugin configuration.
        $adminEmail = 'unknown@example.org';
        $repositoryName = 'Kitodo.Presentation OAI-PMH Interface (default configuration)';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_libraries');

        $result = $queryBuilder
            ->select(
                'tx_dlf_libraries.oai_label AS oai_label',
                'tx_dlf_libraries.contact AS contact'
            )
            ->from('tx_dlf_libraries')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_libraries.pid', intval($this->conf['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_libraries.uid', intval($this->conf['library'])),
                Helper::whereExpression('tx_dlf_libraries')
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $result->fetchAll();

        if (count($allResults) == 1) {
            $resArray = $allResults[0];
            $adminEmail = htmlspecialchars(trim(str_replace('mailto:', '', $resArray['contact'])), ENT_NOQUOTES);
            $repositoryName = htmlspecialchars($resArray['oai_label'], ENT_NOQUOTES);
        } else {
            $this->logger->notice('Incomplete plugin configuration');
        }
        // Get earliest datestamp. Use a default value if that fails.
        $earliestDatestamp = '0000-00-00T00:00:00Z';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $result = $queryBuilder
            ->select('tx_dlf_documents.tstamp AS tstamp')
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->conf['pages']))
            )
            ->orderBy('tx_dlf_documents.tstamp')
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
            $timestamp = $resArray['tstamp'];
            $earliestDatestamp = gmdate('Y-m-d\TH:i:s\Z', $timestamp);
        } else {
            $this->logger->notice('No records found with PID ' . $this->conf['pages']);
        }
        $linkConf = [
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http']
        ];
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
     * @access protected
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function verbListIdentifiers()
    {
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
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
        $resultSet = GeneralUtility::makeInstance(DocumentList::class);
        $resultSet->reset();
        $resultSet->add($documentSet);
        $resultSet->metadata = [
            'completeListSize' => count($documentSet),
            'metadataPrefix' => $this->piVars['metadataPrefix'],
        ];
        return $this->generateOutputForDocumentList($resultSet);
    }

    /**
     * Process verb "ListMetadataFormats"
     *
     * @access protected
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function verbListMetadataFormats()
    {
        $resArray = [];
        // Check for invalid arguments.
        if (count($this->piVars) > 1) {
            if (empty($this->piVars['identifier']) || count($this->piVars) > 2) {
                return $this->error('badArgument');
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Check given identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.*')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->conf['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($this->piVars['identifier']))
                )
                ->orderBy('tx_dlf_documents.tstamp')
                ->setMaxResults(1)
                ->execute();

            $allResults = $result->fetchAll();

            if (count($allResults) < 1) {
                return $this->error('idDoesNotExist');
            }
            $resArray = $allResults[0];
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
     * @access protected
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function verbListRecords()
    {
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
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
        $resultSet = GeneralUtility::makeInstance(DocumentList::class);
        $resultSet->reset();
        $resultSet->add($documentSet);
        $resultSet->metadata = [
            'completeListSize' => count($documentSet),
            'metadataPrefix' => $this->piVars['metadataPrefix'],
        ];
        return $this->generateOutputForDocumentList($resultSet);
    }

    /**
     * Process verb "ListSets"
     *
     * @access protected
     *
     * @return \DOMElement XML node to add to the OAI response
     */
    protected function verbListSets()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

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
            $where = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        }

        $result = $queryBuilder
            ->select(
                'tx_dlf_collections.oai_name AS oai_name',
                'tx_dlf_collections.label AS label'
            )
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()->in('tx_dlf_collections.sys_language_uid', [-1, 0]),
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->conf['pages'])),
                $queryBuilder->expr()->neq('tx_dlf_collections.oai_name', $queryBuilder->createNamedParameter('')),
                $where,
                Helper::whereExpression('tx_dlf_collections')
            )
            ->orderBy('tx_dlf_collections.oai_name')
            ->execute();

        $allResults = $result->fetchAll();

        if (count($allResults) < 1) {
            return $this->error('noSetHierarchy');
        }
        $ListSets = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'ListSets');
        foreach ($allResults as $resArray) {
            $set = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'set');
            $set->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($resArray['oai_name'], ENT_NOQUOTES, 'UTF-8')));
            $set->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setName', htmlspecialchars($resArray['label'], ENT_NOQUOTES, 'UTF-8')));
            $ListSets->appendChild($set);
        }
        return $ListSets;
    }

    /**
     * Fetch records
     *
     * @access protected
     *
     * @return array Array of matching records
     * @throws \Exception
     */
    protected function fetchDocumentUIDs()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        $solr_query = '';
        $where = '';
        if (!$this->conf['show_userdefined']) {
            $where = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        }
        // Check "set" for valid value.
        if (!empty($this->piVars['set'])) {
            // For SOLR we need the index_name of the collection,
            // For DB Query we need the UID of the collection
            $result = $queryBuilder
                ->select(
                    'tx_dlf_collections.index_name AS index_name',
                    'tx_dlf_collections.uid AS uid',
                    'tx_dlf_collections.index_search as index_query'
                )
                ->from('tx_dlf_collections')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->conf['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_collections.oai_name', $queryBuilder->expr()->literal($this->piVars['set'])),
                    $where,
                    Helper::whereExpression('tx_dlf_collections')
                )
                ->setMaxResults(1)
                ->execute();

            $allResults = $result->fetchAll();

            if (count($allResults) < 1) {
                throw new \Exception('noSetHierarchy');
            }
            $resArray = $allResults[0];
            if ($resArray['index_query'] != "") {
                $solr_query .= '(' . $resArray['index_query'] . ')';
            } else {
                $solr_query .= 'collection:' . '"' . $resArray['index_name'] . '"';
            }
        } else {
            // If no set is specified we have to query for all collections
            $solr_query .= 'collection:* NOT collection:""';
        }
        // Check for required fields.
        foreach ($this->formats[$this->piVars['metadataPrefix']]['requiredFields'] as $required) {
            $solr_query .= ' NOT ' . $required . ':""';
        }
        // toplevel="true" is always required
        $solr_query .= ' AND toplevel:true';
        $from = "*";
        // Check "from" for valid value.
        if (!empty($this->piVars['from'])) {
            // Is valid format?
            if (
                is_array($date_array = strptime($this->piVars['from'], '%Y-%m-%dT%H:%M:%SZ'))
                || is_array($date_array = strptime($this->piVars['from'], '%Y-%m-%d'))
            ) {
                $timestamp = gmmktime($date_array['tm_hour'], $date_array['tm_min'], $date_array['tm_sec'], $date_array['tm_mon'] + 1, $date_array['tm_mday'], $date_array['tm_year'] + 1900);
                $from = date("Y-m-d", $timestamp) . 'T' . date("H:i:s", $timestamp) . '.000Z';
            } else {
                throw new \Exception('badArgument');
            }
        }
        $until = "*";
        // Check "until" for valid value.
        if (!empty($this->piVars['until'])) {
            // Is valid format?
            if (
                is_array($date_array = strptime($this->piVars['until'], '%Y-%m-%dT%H:%M:%SZ'))
                || is_array($date_array = strptime($this->piVars['until'], '%Y-%m-%d'))
            ) {
                $timestamp = gmmktime($date_array['tm_hour'], $date_array['tm_min'], $date_array['tm_sec'], $date_array['tm_mon'] + 1, $date_array['tm_mday'], $date_array['tm_year'] + 1900);
                $until = date("Y-m-d", $timestamp) . 'T' . date("H:i:s", $timestamp) . '.999Z';
                if ($from != "*" && $from > $until) {
                    throw new \Exception('badArgument');
                }
            } else {
                throw new \Exception('badArgument');
            }
        }
        // Check "from" and "until" for same granularity.
        if (
            !empty($this->piVars['from'])
            && !empty($this->piVars['until'])
        ) {
            if (strlen($this->piVars['from']) != strlen($this->piVars['until'])) {
                throw new \Exception('badArgument');
            }
        }
        $solr_query .= ' AND timestamp:[' . $from . ' TO ' . $until . ']';
        $documentSet = [];
        $solr = Solr::getInstance($this->conf['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return $documentSet;
        }
        if (intval($this->conf['solr_limit']) > 0) {
            $solr->limit = intval($this->conf['solr_limit']);
        }
        // We only care about the UID in the results and want them sorted
        $parameters = [
            "fields" => "uid",
            "sort" => [
                "uid" => "asc"
            ]
        ];
        $result = $solr->search_raw($solr_query, $parameters);
        if (empty($result)) {
            throw new \Exception('noRecordsMatch');
        }
        foreach ($result as $doc) {
            $documentSet[] = $doc->uid;
        }
        return $documentSet;
    }

    /**
     * Fetch more information for document list
     * @access protected
     *
     * @param \Kitodo\Dlf\Common\DocumentList $documentListSet
     *
     * @return \DOMElement XML of enriched records
     */
    protected function generateOutputForDocumentList(DocumentList $documentListSet)
    {
        $documentsToProcess = $documentListSet->removeRange(0, (int) $this->conf['limit']);
        $verb = $this->piVars['verb'];

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_documents');

        $sql = 'SELECT `tx_dlf_documents`.*, GROUP_CONCAT(DISTINCT `tx_dlf_collections`.`oai_name` ORDER BY `tx_dlf_collections`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `tx_dlf_documents` ' .
            'INNER JOIN `tx_dlf_relations` ON `tx_dlf_relations`.`uid_local` = `tx_dlf_documents`.`uid` ' .
            'INNER JOIN `tx_dlf_collections` ON `tx_dlf_collections`.`uid` = `tx_dlf_relations`.`uid_foreign` ' .
            'WHERE `tx_dlf_documents`.`uid` IN ( ? ) ' .
            'AND `tx_dlf_documents`.`pid` = ? ' .
            'AND `tx_dlf_collections`.`pid` = ? ' .
            'AND `tx_dlf_relations`.`ident`="docs_colls" ' .
            'AND ' . Helper::whereExpression('tx_dlf_collections') . ' ' .
            'GROUP BY `tx_dlf_documents`.`uid` ' .
            'LIMIT ?';

        $values = [
            $documentsToProcess,
            $this->conf['pages'],
            $this->conf['pages'],
            $this->conf['limit']
        ];
        $types = [
            Connection::PARAM_INT_ARRAY,
            Connection::PARAM_INT,
            Connection::PARAM_INT,
            Connection::PARAM_INT
        ];
        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        $documents = $connection->executeQuery($sql, $values, $types);

        $output = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', $verb);
        while ($resArray = $documents->fetch()) {
            // Add header node.
            $header = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');
            $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier', htmlspecialchars($resArray['record_id'], ENT_NOQUOTES, 'UTF-8')));
            $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp', gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp'])));
            // Check if document is deleted or hidden.
            // TODO: Use TYPO3 API functions here!
            if (
                $resArray['deleted']
                || $resArray['hidden']
            ) {
                // Add "deleted" status.
                $header->setAttribute('status', 'deleted');
                if ($verb === 'ListRecords') {
                    // Add record node.
                    $record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');
                    $record->appendChild($header);
                    $output->appendChild($record);
                } elseif ($verb === 'ListIdentifiers') {
                    $output->appendChild($header);
                }
            } else {
                // Add sets but only if oai_name field is not empty.
                foreach (explode(' ', $resArray['collections']) as $spec) {
                    if ($spec) {
                        $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec', htmlspecialchars($spec, ENT_NOQUOTES, 'UTF-8')));
                    }
                }
                if ($verb === 'ListRecords') {
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
                } elseif ($verb === 'ListIdentifiers') {
                    $output->appendChild($header);
                }
            }
        }
        $output->appendChild($this->generateResumptionTokenForDocumentListSet($documentListSet));
        return $output;
    }

    /**
     * Generate resumption token
     *
     * @access protected
     *
     * @param \Kitodo\Dlf\Common\DocumentList $documentListSet
     *
     * @return \DOMElement XML for resumption token
     */
    protected function generateResumptionTokenForDocumentListSet(DocumentList $documentListSet)
    {
        if ($documentListSet->count() !== 0) {
            $token = uniqid('', false);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_tokens');
            $affectedRows = $queryBuilder
                ->insert('tx_dlf_tokens')
                ->values([
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'token' => $token,
                    'options' => serialize($documentListSet),
                    'ident' => 'oai',
                ])
                ->execute();

            if ($affectedRows === 1) {
                $resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken', htmlspecialchars($token, ENT_NOQUOTES, 'UTF-8'));
            } else {
                $this->logger->error('Could not create resumption token');
                return $this->error('badResumptionToken');
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
}
