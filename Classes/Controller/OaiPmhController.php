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

namespace Kitodo\Dlf\Controller;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the plugin 'OAI-PMH Interface' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class OaiPmhController extends AbstractController
{
    /**
     * Initializes the current action
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->request->setFormat('xml');
    }

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
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $parameters = [];

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
                $queryBuilder->expr()->lt('tx_dlf_tokens.tstamp',
                    $queryBuilder->createNamedParameter((int)($GLOBALS['EXEC_TIME'] - $this->settings['expired'])))
            )
            ->execute();

        if ($result === -1) {
            // Deletion failed.
            $this->logger->warning('Could not delete expired resumption tokens');
        }
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
        $this->parameters = [];
        // Set only allowed parameters.
        foreach ($allowedParams as $param) {
            if (GeneralUtility::_GP($param)) {
                $this->parameters[$param] = GeneralUtility::_GP($param);
            }
        }
    }

    /**
     * Get unqualified Dublin Core data.
     * @see http://www.openarchives.org/OAI/openarchivesprotocol.html#dublincore
     *
     * @access protected
     *
     * @param array $record : The full record array
     *
     * @return array $metadata: The mapped metadata array
     */
    protected function getDcData(array $record)
    {
        $metadata = [];

        $metadata[] = ['dc:identifier' => $record['record_id']];

        if (!empty($record['purl'])) {
            $metadata[] = ['dc:identifier' => $record['purl']];
        }
        if (!empty($record['prod_id'])) {
            $metadata[] = ['dc:identifier' => $record['prod_id']];
        }
        if (!empty($record['urn'])) {
            $metadata[] = ['dc:identifier' => $record['urn']];
        }
        if (!empty($record['title'])) {
            $metadata[] = ['dc:title' => $record['title']];
        }
        if (!empty($record['author'])) {
            $metadata[] = ['dc:creator' => $record['author']];
        }
        if (!empty($record['year'])) {
            $metadata[] = ['dc:date' => $record['year']];
        }
        if (!empty($record['place'])) {
            $metadata[] = ['dc:coverage' => $record['place']];
        }
        $record[] = ['dc:format' => $record['application/mets+xml']];
        $record[] = ['dc:type' => $record['Text']];
        if (!empty($record['partof'])) {
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

            if ($partof = $result->fetch()) {
                $metadata[] = ['dc:relation' => $partof['record_id']];
            }
        }
        if (!empty($record['license'])) {
            $metadata[] = ['dc:rights' => $record['license']];
        }
        if (!empty($record['terms'])) {
            $metadata[] = ['dc:rights' => $record['terms']];
        }
        if (!empty($record['restrictions'])) {
            $metadata[] = ['dc:rights' => $record['restrictions']];
        }
        if (!empty($record['out_of_print'])) {
            $metadata[] = ['dc:rights' => $record['out_of_print']];
        }
        if (!empty($record['rights_info'])) {
            $metadata[] = ['dc:rights' => $record['rights_info']];
        }
        return $metadata;
    }


    /**
     * Get METS data.
     * @see http://www.loc.gov/standards/mets/docs/mets.v1-7.html
     *
     * @access protected
     *
     * @param array $record : The full record array
     *
     * @return $metadata: The mapped metadata array
     */
    protected function getMetsData(array $record)
    {
        $mets = null;
        // Load METS file.
        $xml = new \DOMDocument();
        if ($xml->load($record['location'])) {
            // Get root element.
            $root = $xml->getElementsByTagNameNS($this->formats['mets']['namespace'], 'mets');
            if ($root->item(0) instanceof \DOMNode) {
                // Import node into \DOMDocument.
                $mets = $xml->saveXML();
                // Remove leading line
                $mets = substr($mets, strpos($mets, '>'));
            } else {
                $this->logger->error('No METS part found in document with location "' . $record['location'] . '"');
            }
        } else {
            $this->logger->error('Could not load XML file from "' . $record['location'] . '"');
        }
        if ($mets === null) {
            $errorMessage = LocalizationUtility::translate('LLL:EXT:dlf/Resources/Private/Language/OaiPmh.xml:error');
            $mets = $this->oai->createElementNS('http://kitodo.org/', 'kitodo:error',
                htmlspecialchars(
                    (!empty($errorMessage)?  $errorMessage: 'Error!'), ENT_NOQUOTES, 'UTF-8'
                )
            );
        }
        return $mets;
    }

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        // Get allowed GET and POST variables.
        $this->getUrlParams();

        // Get extension configuration.
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');
        // Delete expired resumption tokens.
        $this->deleteExpiredTokens();

        $this->view->assign('parameters', $this->parameters);

        switch ($this->parameters['verb']) {
            case 'GetRecord':
                $response = $this->verbGetRecord();
                break;
            case 'Identify':
                $this->verbIdentify();
                break;
            case 'ListIdentifiers':
                $this->verbListIdentifiers();
                break;
            case 'ListMetadataFormats':
                $this->verbListMetadataFormats();
                break;
            case 'ListRecords':
                $this->verbListRecords();
                break;
            case 'ListSets':
                $this->verbListSets();
                break;
        }

        return;
        // Create XML document.
        // $this->oai = new \DOMDocument('1.0', 'UTF-8');

        // Create root element.
        $root = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'OAI-PMH');

        // Add request.
        $linkConf = [
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => 1,
            'forceAbsoluteUrl.' => [
                'scheme' => !empty($this->extConf['forceAbsoluteUrlHttps']) ? 'https' : 'http'
            ]
        ];
        $request = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'request',
            htmlspecialchars(
                $this->configurationManager->getContentObject()->typoLink_URL($linkConf),
                ENT_NOQUOTES, 'UTF-8'
            )
        );
        if (!$this->error) {
            foreach ($this->parameters as $key => $value) {
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
        header('Expires: ' . date('r', $GLOBALS['EXEC_TIME'] + $this->settings['expired']));

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
                $queryBuilder->expr()->eq('tx_dlf_tokens.token',
                    $queryBuilder->expr()->literal($this->parameters['resumptionToken']))
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $result->fetchAll();

        if (count($allResults) < 1) {
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
        if (count($this->parameters) !== 3 || empty($this->parameters['metadataPrefix']) || empty($this->parameters['identifier'])) {
            return $this->error('badArgument');
        }
        if (!array_key_exists($this->parameters['metadataPrefix'], $this->formats)) {
            return $this->error('cannotDisseminateFormat');
        }
        $where = '';
        if (!$this->settings['show_userdefined']) {
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
            $this->parameters['identifier'],
            $this->settings['pages'],
            $this->settings['pages']
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
        foreach ($this->formats[$this->parameters['metadataPrefix']]['requiredFields'] as $required) {
            if (empty($resArray[$required])) {
                return $this->error('cannotDisseminateFormat');
            }
        }
        $GetRecord = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'GetRecord');
        $recordNode = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');
        $headerNode = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');
        $headerNode->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier',
            htmlspecialchars($resArray['record_id'], ENT_NOQUOTES, 'UTF-8')));
        $headerNode->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp',
            gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp'])));
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
                $headerNode->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'setSpec',
                    htmlspecialchars($spec, ENT_NOQUOTES, 'UTF-8')));
            }
            $recordNode->appendChild($headerNode);
            $metadataNode = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadata');
            switch ($this->parameters['metadataPrefix']) {
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
     * @return void
     */
    protected function verbIdentify()
    {
        // Get repository name and administrative contact.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_libraries');

        $result = $queryBuilder
            ->select(
                'tx_dlf_libraries.oai_label AS oai_label',
                'tx_dlf_libraries.contact AS contact'
            )
            ->from('tx_dlf_libraries')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_libraries.pid', intval($this->settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_libraries.uid', intval($this->settings['library']))
            )
            ->setMaxResults(1)
            ->execute();

        $oaiIdentifyInfo = $result->fetch();
        if (!$oaiIdentifyInfo) {
            $this->logger->notice('Incomplete plugin configuration');
        }

        // Use default values for an installation with incomplete plugin configuration.
        if (empty($oaiIdentifyInfo['oai_label'])) {
            $oaiIdentifyInfo['oai_label'] = 'Kitodo.Presentation OAI-PMH Interface (default configuration)';
            $this->logger->notice('Incomplete plugin configuration (oai_label is missing)');
        }

        if (empty($oaiIdentifyInfo['contact'])) {
            $oaiIdentifyInfo['contact'] = 'unknown@example.org';
            $this->logger->notice('Incomplete plugin configuration (contact is missing)');
        }

        // Get earliest datestamp. Use a default value if that fails.
        $earliestDatestamp = '0000-00-00T00:00:00Z';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $result = $queryBuilder
            ->select('tx_dlf_documents.tstamp AS tstamp')
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->settings['pages']))
            )
            ->orderBy('tx_dlf_documents.tstamp')
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
            $oaiIdentifyInfo['earliestDatestamp'] = gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp']);
        } else {
            $this->logger->notice('No records found with PID ' . $this->settings['pages']);
        }
        $this->view->assign('oaiIdentifyInfo', $oaiIdentifyInfo);
    }

    /**
     * Process verb "ListIdentifiers"
     *
     * @access protected
     *
     * @return void
     */
    protected function verbListIdentifiers()
    {
        // If we have a resumption token we can continue our work
        if (!empty($this->parameters['resumptionToken'])) {
            // "resumptionToken" is an exclusive argument.
            if (count($this->parameters) > 2) {
                return $this->error('badArgument');
            } else {
                return $this->resume();
            }
        }
        // "metadataPrefix" is required and "identifier" is not allowed.
        if (empty($this->parameters['metadataPrefix']) || !empty($this->parameters['identifier'])) {
            return $this->error('badArgument');
        }
        if (!in_array($this->parameters['metadataPrefix'], array_keys($this->formats))) {
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
            'metadataPrefix' => $this->parameters['metadataPrefix'],
        ];

        $resultSet =  $this->generateOutputForDocumentList($resultSet);
        $this->view->assign('listIdentifiers', $resultSet);
    }

    /**
     * Process verb "ListMetadataFormats"
     *
     * @access protected
     *
     * @return void
     */
    protected function verbListMetadataFormats()
    {
        $resArray = [];
        // check for the optional "identifier" parameter
        if (isset($this->parameters['identifier'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Check given identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.*')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->settings['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id',
                    $queryBuilder->expr()->literal($this->parameters['identifier']))
                )
                ->orderBy('tx_dlf_documents.tstamp')
                ->setMaxResults(1)
                ->execute();

            $resArray = $result->fetch();
        }

        foreach ($this->formats as $prefix => $details) {
            if (!empty($resArray)) {
                // check, if all required fields are available for a given identifier
                foreach ($details['requiredFields'] as $required) {
                    if (empty($resArray[$required])) {
                        // Skip metadata formats whose requirements are not met.
                        continue 2;
                    }
                }
            }
            $details['prefix'] = $prefix;
            $resultSet[] = $details;
        }
        $this->view->assign('metadataFormats', $resultSet);

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
        if (!empty($this->parameters['resumptionToken'])) {
            // "resumptionToken" is an exclusive argument.
            if (count($this->parameters) > 2) {
                return $this->error('badArgument');
            } else {
                return $this->resume();
            }
        }
        if (empty($this->parameters['metadataPrefix']) || !empty($this->parameters['identifier'])) {
            // "metadataPrefix" is required and "identifier" is not allowed.
            return $this->error('badArgument');
        }
        // Check "metadataPrefix" for valid value.
        if (!in_array($this->parameters['metadataPrefix'], array_keys($this->formats))) {
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
            'metadataPrefix' => $this->parameters['metadataPrefix'],
        ];

        $resultSet =  $this->generateOutputForDocumentList($resultSet);
        $this->view->assign('listRecords', $resultSet);
    }

    /**
     * Process verb "ListSets"
     *
     * @access protected
     *
     * @return void
     */
    protected function verbListSets()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        // Check for invalid arguments.
        if (count($this->parameters) > 1) {
            if (!empty($this->parameters['resumptionToken'])) {
                return $this->error('badResumptionToken');
            } else {
                return $this->error('badArgument');
            }
        }
        $where = '';
        if (!$this->settings['show_userdefined']) {
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
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->settings['pages'])),
                $queryBuilder->expr()->neq('tx_dlf_collections.oai_name', $queryBuilder->createNamedParameter('')),
                $where,
                Helper::whereExpression('tx_dlf_collections')
            )
            ->orderBy('tx_dlf_collections.oai_name')
            ->execute();

        $allResults = $result->fetchAll();

        $this->view->assign('oaiSets', $allResults);
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
        if (!$this->settings['show_userdefined']) {
            $where = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        }
        // Check "set" for valid value.
        if (!empty($this->parameters['set'])) {
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
                    $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->settings['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_collections.oai_name',
                        $queryBuilder->expr()->literal($this->parameters['set'])),
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
        foreach ($this->formats[$this->parameters['metadataPrefix']]['requiredFields'] as $required) {
            $solr_query .= ' NOT ' . $required . ':""';
        }
        // toplevel="true" is always required
        $solr_query .= ' AND toplevel:true';
        $from = "*";
        // Check "from" for valid value.
        if (!empty($this->parameters['from'])) {
            // Is valid format?
            if (
                is_array($date_array = strptime($this->parameters['from'], '%Y-%m-%dT%H:%M:%SZ'))
                || is_array($date_array = strptime($this->parameters['from'], '%Y-%m-%d'))
            ) {
                $timestamp = gmmktime($date_array['tm_hour'], $date_array['tm_min'], $date_array['tm_sec'],
                    $date_array['tm_mon'] + 1, $date_array['tm_mday'], $date_array['tm_year'] + 1900);
                $from = date("Y-m-d", $timestamp) . 'T' . date("H:i:s", $timestamp) . '.000Z';
            } else {
                throw new \Exception('badArgument');
            }
        }
        $until = "*";
        // Check "until" for valid value.
        if (!empty($this->parameters['until'])) {
            // Is valid format?
            if (
                is_array($date_array = strptime($this->parameters['until'], '%Y-%m-%dT%H:%M:%SZ'))
                || is_array($date_array = strptime($this->parameters['until'], '%Y-%m-%d'))
            ) {
                $timestamp = gmmktime($date_array['tm_hour'], $date_array['tm_min'], $date_array['tm_sec'],
                    $date_array['tm_mon'] + 1, $date_array['tm_mday'], $date_array['tm_year'] + 1900);
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
            !empty($this->parameters['from'])
            && !empty($this->parameters['until'])
        ) {
            if (strlen($this->parameters['from']) != strlen($this->parameters['until'])) {
                throw new \Exception('badArgument');
            }
        }
        $solr_query .= ' AND timestamp:[' . $from . ' TO ' . $until . ']';
        $documentSet = [];
        $solr = Solr::getInstance($this->settings['solrcore']);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return $documentSet;
        }
        if (intval($this->settings['solr_limit']) > 0) {
            $solr->limit = intval($this->settings['solr_limit']);
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
        $documentsToProcess = $documentListSet->removeRange(0, (int)$this->settings['limit']);
        $verb = $this->parameters['verb'];

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
            $this->settings['pages'],
            $this->settings['pages'],
            $this->settings['limit']
        ];
        $types = [
            Connection::PARAM_INT_ARRAY,
            Connection::PARAM_INT,
            Connection::PARAM_INT,
            Connection::PARAM_INT
        ];
        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        $documents = $connection->executeQuery($sql, $values, $types);

        //$output = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', $verb);
        while ($resArray = $documents->fetch()) {
            // we need the collections as array later
            $resArray['collections'] = explode(' ', $resArray['collections']);

            if ($verb === 'ListRecords') {
                // Add metadata node.
                $metadataPrefix = $this->parameters['metadataPrefix'];
                if (!$metadataPrefix) {
                    // If we resume an action the metadataPrefix is stored with the documentSet
                    $metadataPrefix = $documentListSet->metadata['metadataPrefix'];
                }
                switch ($metadataPrefix) {
                    case 'oai_dc':
                        $resArray['metadata'] = $this->getDcData($resArray);
                        break;
                    case 'epicur':
                        $resArray['metadata'] = $resArray;
                        break;
                    case 'mets':
                        $resArray['metadata'] = $this->getMetsData($resArray);
                        break;
                }
            }

            $records[] = $resArray;
            continue;
            // Add header node.
            // $header = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'header');
            // $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'identifier',
            //     htmlspecialchars($resArray['record_id'], ENT_NOQUOTES, 'UTF-8')));
            // $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'datestamp',
            //     gmdate('Y-m-d\TH:i:s\Z', $resArray['tstamp'])));
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
                        $header->appendChild($this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/',
                            'setSpec', htmlspecialchars($spec, ENT_NOQUOTES, 'UTF-8')));
                    }
                }
                if ($verb === 'ListRecords') {
                    // Add record node.
                    $record = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'record');
                    $record->appendChild($header);
                    // Add metadata node.
                    $metadata = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'metadata');
                    $metadataPrefix = $this->parameters['metadataPrefix'];
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
        return $records;


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
                $resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/',
                    'resumptionToken', htmlspecialchars($token, ENT_NOQUOTES, 'UTF-8'));
            } else {
                $this->logger->error('Could not create resumption token');
                return $this->error('badResumptionToken');
            }
        } else {
            // Result set complete. We don't need a token.
            $resumptionToken = $this->oai->createElementNS('http://www.openarchives.org/OAI/2.0/', 'resumptionToken');
        }
        $resumptionToken->setAttribute('cursor',
            intval($documentListSet->metadata['completeListSize']) - count($documentListSet));
        $resumptionToken->setAttribute('completeListSize', $documentListSet->metadata['completeListSize']);
        $resumptionToken->setAttribute('expirationDate',
            gmdate('Y-m-d\TH:i:s\Z', $GLOBALS['EXEC_TIME'] + $this->settings['expired']));
        return $resumptionToken;
    }
}
