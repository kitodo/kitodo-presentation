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
     * @var string
     * @access protected
     */
    protected $error;

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
     * @return string: The fetched METS XML
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

        switch ($this->parameters['verb']) {
            case 'GetRecord':
                $this->verbGetRecord();
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

        $this->view->assign('parameters', $this->parameters);
        $this->view->assign('error', $this->error);

        return;
    }

    /**
     * Continue with resumption token
     *
     * @access protected
     *
     * @return \Kitodo\Dlf\Common\DocumentList list of uids
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
                    $queryBuilder->expr()->literal($this->parameters['resumptionToken'])
                )
            )
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
            return unserialize($resArray['options']);
        } else {
            // No resumption token found or resumption token expired.
            $this->error = 'badResumptionToken';
            return [];
        }
    }

    /**
     * Process verb "GetRecord"
     *
     * @access protected
     *
     * @return void
     */
    protected function verbGetRecord()
    {
        if (count($this->parameters) !== 3 || empty($this->parameters['metadataPrefix']) || empty($this->parameters['identifier'])) {
            $this->error = 'badArgument'     ;
            return;
        }
        if (!array_key_exists($this->parameters['metadataPrefix'], $this->formats)) {
            $this->error = 'cannotDisseminateFormat'     ;
            return;
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
            $this->error = 'idDoesNotExist';
            return;
        }

        // Check for required fields.
        foreach ($this->formats[$this->parameters['metadataPrefix']]['requiredFields'] as $required) {
            if (empty($resArray[$required])) {
                $this->error = 'cannotDisseminateFormat';
                return;
            }
        }

        // we need the collections as array later
        $resArray['collections'] = explode(' ', $resArray['collections']);

        // Add metadata
        switch ($this->parameters['metadataPrefix']) {
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

        $this->view->assign('record', $resArray);
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
                $this->error = 'badArgument';
                return;
            } else {
                // return next chunk of documents
                $resultSet = $this->resume();
                if ($resultSet instanceof DocumentList) {
                    $listIdentifiers =  $this->generateOutputForDocumentList($resultSet);
                    $this->view->assign('listIdentifiers', $listIdentifiers);
                }
                return;
            }
        }
        // "metadataPrefix" is required and "identifier" is not allowed.
        if (empty($this->parameters['metadataPrefix']) || !empty($this->parameters['identifier'])) {
            $this->error = 'badArgument';
            return;
        }
        if (!in_array($this->parameters['metadataPrefix'], array_keys($this->formats))) {
            $this->error = 'cannotDisseminateFormat';
            return;
        }
        try {
            $documentSet = $this->fetchDocumentUIDs();
        } catch (\Exception $exception) {
            $this->error = 'idDoesNotExist';
            return;
        }
        // create new and empty documentlist
        $resultSet = GeneralUtility::makeInstance(DocumentList::class);
        $resultSet->reset();
        $resultSet->add($documentSet);
        $resultSet->metadata = [
            'completeListSize' => count($documentSet),
            'metadataPrefix' => $this->parameters['metadataPrefix'],
        ];

        $listIdentifiers =  $this->generateOutputForDocumentList($resultSet);
        $this->view->assign('listIdentifiers', $listIdentifiers);
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

        $resultSet = [];
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
    }

    /**
     * Process verb "ListRecords"
     *
     * @access protected
     *
     * @return void
     */
    protected function verbListRecords()
    {
        // Check for invalid arguments.
        if (!empty($this->parameters['resumptionToken'])) {
            // "resumptionToken" is an exclusive argument.
            if (count($this->parameters) > 2) {
                $this->error = 'badArgument';
                return;
            } else {
                // return next chunk of documents
                $resultSet = $this->resume();
                $listRecords =  $this->generateOutputForDocumentList($resultSet);
                $this->parameters['metadataPrefix'] = $resultSet->metadata['metadataPrefix'];
                $this->view->assign('listRecords', $listRecords);
                return;
            }
        }
        if (empty($this->parameters['metadataPrefix']) || !empty($this->parameters['identifier'])) {
            // "metadataPrefix" is required and "identifier" is not allowed.
            $this->error = 'badArgument';
            return;
        }
        // Check "metadataPrefix" for valid value.
        if (!in_array($this->parameters['metadataPrefix'], array_keys($this->formats))) {
            $this->error = 'cannotDisseminateFormat';
            return;
        }
        try {
            $documentSet = $this->fetchDocumentUIDs();
        } catch (\Exception $exception) {
            $this->error = 'idDoesNotExist';
            return;
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
                $this->error = 'badResumptionToken';
                return;
            } else {
                $this->error = 'badArgument';
                return;
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
                $this->error = 'noSetHierarchy';
                return;
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
                $this->error = 'badArgument';
                return;
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
                    $this->error = 'badArgument';
                }
            } else {
                $this->error = 'badArgument';
            }
        }
        // Check "from" and "until" for same granularity.
        if (
            !empty($this->parameters['from'])
            && !empty($this->parameters['until'])
        ) {
            if (strlen($this->parameters['from']) != strlen($this->parameters['until'])) {
                $this->error = 'badArgument';
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
            $this->error = 'noRecordsMatch';
            return;
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
     * @return array of enriched records
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

        $records = [];
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
        }

        $this->generateResumptionTokenForDocumentListSet($documentListSet);

        return $records;
    }

    /**
     * Generate resumption token
     *
     * @access protected
     *
     * @param \Kitodo\Dlf\Common\DocumentList $documentListSet
     *
     * @return void
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
                $resumptionToken = $token;
            } else {
                $this->logger->error('Could not create resumption token');
                $this->error = 'badResumptionToken';
                return;
            }
        } else {
            // Result set complete. We don't need a token.
            $resumptionToken = '';
        }

        $resumptionTokenInfo = [];
        $resumptionTokenInfo['token'] = $resumptionToken;
        $resumptionTokenInfo['cursor'] = $documentListSet->metadata['completeListSize'] - count($documentListSet);
        $resumptionTokenInfo['completeListSize'] = $documentListSet->metadata['completeListSize'];
        $expireDateTime = new \DateTime();
        $expireDateTime->add(new \DateInterval('PT' .$this->settings['expired'] . 'S'));
        $resumptionTokenInfo['expired'] = $expireDateTime;

        $this->view->assign('resumptionToken', $resumptionTokenInfo);
    }
}
