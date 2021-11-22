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
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Domain\Model\Token;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\LibraryRepository;
use Kitodo\Dlf\Domain\Repository\TokenRepository;

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
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * @param TokenRepository $tokenRepository
     */
    public function injectTokenRepository(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @param CollectionRepository $collectionRepository
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @var LibraryRepository
     */
    protected $libraryRepository;

    /**
     * @param LibraryRepository $libraryRepository
     */
    public function injectLibraryRepository(LibraryRepository $libraryRepository)
    {
        $this->libraryRepository = $libraryRepository;
    }

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
        $this->tokenRepository->deleteExpiredTokens($this->settings['expired']);
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

            $document = $this->documentRepository->findOneByPartof($metadata['partof']);

            if ($document) {
                $metadata[] = ['dc:relation' => $document->getRecordId()];
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
     * @return \Kitodo\Dlf\Common\DocumentList|null list of uids
     */
    protected function resume(): ?DocumentList
    {
        $token = $this->tokenRepository->findOneByToken($this->parameters['resumptionToken']);

        if ($token) {
            $options = $token->getOptions();
        }
        if ($options instanceof DocumentList) {
            return $options;
        } else {
            // No resumption token found or resumption token expired.
            $this->error = 'badResumptionToken';
            return null;
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
            $this->error = 'badArgument';
            return;
        }

        if (!array_key_exists($this->parameters['metadataPrefix'], $this->formats)) {
            $this->error = 'cannotDisseminateFormat';
            return;
        }

        $document = $this->documentRepository->getOaiRecord($this->settings, $this->parameters);

        if (!$document['uid']) {
            $this->error = 'idDoesNotExist';
            return;
        }

        // Check for required fields.
        foreach ($this->formats[$this->parameters['metadataPrefix']]['requiredFields'] as $required) {
            if (empty($document[$required])) {
                $this->error = 'cannotDisseminateFormat';
                return;
            }
        }

        // we need the collections as array later
        $document['collections'] = explode(' ', $document['collections']);

        // Add metadata
        switch ($this->parameters['metadataPrefix']) {
            case 'oai_dc':
                $document['metadata'] = $this->getDcData($document);
                break;
            case 'epicur':
                $document['metadata'] = $document;
                break;
            case 'mets':
                $document['metadata'] = $this->getMetsData($document);
                break;
        }

        $this->view->assign('record', $document);
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
        $library = $this->libraryRepository->findByUid($this->settings['library']);

        $oaiIdentifyInfo = [];

        if (!$oaiIdentifyInfo) {
            $this->logger->notice('Incomplete plugin configuration');
        }

        $oaiIdentifyInfo['oai_label'] = $library->getOaiLabel();
        // Use default values for an installation with incomplete plugin configuration.
        if (empty($oaiIdentifyInfo['oai_label'])) {
            $oaiIdentifyInfo['oai_label'] = 'Kitodo.Presentation OAI-PMH Interface (default configuration)';
            $this->logger->notice('Incomplete plugin configuration (oai_label is missing)');
        }

        $oaiIdentifyInfo['contact'] = $library->getContact();
        if (empty($oaiIdentifyInfo['contact'])) {
            $oaiIdentifyInfo['contact'] = 'unknown@example.org';
            $this->logger->notice('Incomplete plugin configuration (contact is missing)');
        }

        $document = $this->documentRepository->findOldestDocument();

        if ($document) {
            $oaiIdentifyInfo['earliestDatestamp'] = gmdate('Y-m-d\TH:i:s\Z', $document->getTstamp()->getTimestamp());
        } else {
            // access storagePid from TypoScript
            $pageSettings = $this->configurationManager->getConfiguration($this->configurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            $storagePid = $pageSettings["plugin."]["tx_dlf."]["persistence."]["storagePid"];
            if ($storagePid > 0) {
                $this->logger->notice('No records found with PID ' . $storagePid);
            } else {
                $this->logger->notice('No records found');
            }
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
                    $listIdentifiers = $this->generateOutputForDocumentList($resultSet);
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
        if (is_array($documentSet)) {
            $resultSet->add($documentSet);
            $resultSet->metadata = [
                'completeListSize' => count($documentSet),
                'metadataPrefix' => $this->parameters['metadataPrefix'],
            ];
        }

        $listIdentifiers = $this->generateOutputForDocumentList($resultSet);
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
            $resArray = $this->documentRepository->findOneByRecordId($this->parameters['identifier']);
        }

        $resultSet = [];
        foreach ($this->formats as $prefix => $details) {
            if (!empty($resArray)) {
                // check, if all required fields are available for a given identifier
                foreach ($details['requiredFields'] as $required) {
                    $methodName = 'get' . GeneralUtility::underscoredToUpperCamelCase($required);
                    if (empty($resArray->$methodName())) {
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
                if ($resultSet instanceof DocumentList) {
                    $listRecords = $this->generateOutputForDocumentList($resultSet);
                    $this->parameters['metadataPrefix'] = $resultSet->metadata['metadataPrefix'];
                    $this->view->assign('listRecords', $listRecords);
                }
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
        if (is_array($documentSet)) {
            $resultSet->add($documentSet);
            $resultSet->metadata = [
                'completeListSize' => count($documentSet),
                'metadataPrefix' => $this->parameters['metadataPrefix'],
            ];
        }

        $resultSet = $this->generateOutputForDocumentList($resultSet);
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
        // It is required to set a oai_name inside the collection record to be shown in oai-pmh plugin.
        $this->settings['hideEmptyOaiNames'] = true;

        $oaiSets = $this->collectionRepository->findCollectionsBySettings($this->settings);

        $this->view->assign('oaiSets', $oaiSets);
    }

    /**
     * Fetch records
     *
     * @access protected
     *
     * @return array|null Array of matching records
     */
    protected function fetchDocumentUIDs()
    {
        $solr_query = '';
        // Check "set" for valid value.
        if (!empty($this->parameters['set'])) {
            // For SOLR we need the index_name of the collection,
            // For DB Query we need the UID of the collection

            $result = $this->collectionRepository->getIndexNameForSolr($this->settings, $this->parameters['set']);

            if ($resArray = $result->fetch()) {
                if ($resArray['index_query'] != "") {
                    $solr_query .= '(' . $resArray['index_query'] . ')';
                } else {
                    $solr_query .= 'collection:' . '"' . $resArray['index_name'] . '"';
                }
            } else {
                $this->error = 'noSetHierarchy';
                return null;
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
        $documentsToProcess = $documentListSet->removeRange(0, (int) $this->settings['limit']);
        if ($documentsToProcess === null) {
            $this->error = 'noRecordsMatch';
            return [];
        }
        $verb = $this->parameters['verb'];

        $documents = $this->documentRepository->getOaiDocumentList($this->settings, $documentsToProcess);

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
            $resumptionToken = uniqid('', false);

            // create new token
            $newToken = $this->objectManager->get(Token::class);
            $newToken->setToken($resumptionToken);
            $newToken->setOptions($documentListSet);

            // add to tokenRepository
            $this->tokenRepository->add($newToken);
        } else {
            // Result set complete. We don't need a token.
            $resumptionToken = '';
        }

        $resumptionTokenInfo = [];
        $resumptionTokenInfo['token'] = $resumptionToken;
        $resumptionTokenInfo['cursor'] = $documentListSet->metadata['completeListSize'] - count($documentListSet);
        $resumptionTokenInfo['completeListSize'] = $documentListSet->metadata['completeListSize'];
        $expireDateTime = new \DateTime();
        $expireDateTime->add(new \DateInterval('PT' . $this->settings['expired'] . 'S'));
        $resumptionTokenInfo['expired'] = $expireDateTime;

        $this->view->assign('resumptionToken', $resumptionTokenInfo);
    }
}
