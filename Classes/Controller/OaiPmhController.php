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

use DateTime;
use DOMDocument;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Domain\Model\Token;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\LibraryRepository;
use Kitodo\Dlf\Domain\Repository\TokenRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Controller class for the plugin 'OAI-PMH Interface'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class OaiPmhController extends AbstractController
{
    /**
     * @access protected
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * @access public
     *
     * @param TokenRepository $tokenRepository
     *
     * @return void
     */
    public function injectTokenRepository(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * @access protected
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @access public
     *
     * @param CollectionRepository $collectionRepository
     *
     * @return void
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @access protected
     * @var LibraryRepository
     */
    protected $libraryRepository;

    /**
     * @access public
     *
     * @param LibraryRepository $libraryRepository
     *
     * @return void
     */
    public function injectLibraryRepository(LibraryRepository $libraryRepository)
    {
        $this->libraryRepository = $libraryRepository;
    }

    /**
     * Initializes the current action
     *
     * @access public
     *
     * @return void
     */
    public function initializeAction(): void
    {
        $this->request = $this->request->withFormat("xml");
    }

    /**
     * @access protected
     * @var string Did an error occur?
     */
    protected $error;

    /**
     * @access protected
     * @var array This holds the configuration for all supported metadata prefixes
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
     * @access protected
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
     * @param RequestInterface $request the HTTP request
     *
     * @return void
     */
    protected function getUrlParams(RequestInterface $request)
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
            if ($request->getQueryParams()[$param] ?? null) {
                $this->parameters[$param] = $request->getQueryParams()[$param];
            }
        }
    }

    /**
     * Get unqualified Dublin Core data.
     * @see https://www.openarchives.org/OAI/openarchivesprotocol.html#dublincore
     *
     * @access private
     *
     * @param array $record The full record array
     *
     * @return array The mapped metadata array
     */
    private function getDublinCoreData(array $record)
    {
        $metadata = [];

        $metadata[] = ['dc:identifier' => $record['record_id']];

        $this->addDublinCoreData($metadata, 'dc:identifier', $record['purl']);
        $this->addDublinCoreData($metadata, 'dc:identifier', $record['prod_id']);
        $this->addDublinCoreData($metadata, 'dc:identifier', $record['urn']);
        $this->addDublinCoreData($metadata, 'dc:title', $record['title']);
        $this->addDublinCoreData($metadata, 'dc:creator', $record['author']);
        $this->addDublinCoreData($metadata, 'dc:date', $record['year']);
        $this->addDublinCoreData($metadata, 'dc:coverage', $record['place']);

        $record[] = ['dc:format' => $record['application/mets+xml'] ?? ''];
        $record[] = ['dc:type' => $record['Text'] ?? ''];
        if (!empty($record['partof'])) {
            $document = $this->documentRepository->findOneBy(['partof' => $metadata['partof'] ?? '']);

            if ($document) {
                $metadata[] = ['dc:relation' => $document->getRecordId()];
            }
        }
        $this->addDublinCoreData($metadata, 'dc:rights', $record['license']);
        $this->addDublinCoreData($metadata, 'dc:rights', $record['terms']);
        $this->addDublinCoreData($metadata, 'dc:rights', $record['restrictions']);
        $this->addDublinCoreData($metadata, 'dc:rights', $record['out_of_print']);
        $this->addDublinCoreData($metadata, 'dc:rights', $record['rights_info']);

        return $metadata;
    }

    /**
     * Add Dublin Core data.
     *
     * @access private
     *
     * @param array $metadata The mapped metadata array passed as reference
     * @param string $key The key to which record value should be assigned
     * @param string $value The key from record array
     *
     * @return void
     */
    private function addDublinCoreData(&$metadata, $key, $value)
    {
        if (!empty($value)) {
            $metadata[] = [$key => $value];
        }
    }

    /**
     * Get METS data.
     * @see http://www.loc.gov/standards/mets/docs/mets.v1-7.html
     *
     * @access protected
     *
     * @param array $record The full record array
     *
     * @return string The fetched METS XML
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
                $mets = $xml->saveXML($root->item(0));
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
     * @access public
     *
     * @return ResponseInterface
     */
    public function mainAction(): ResponseInterface
    {
        // Get allowed GET and POST variables.
        $this->getUrlParams($this->request);

        // Delete expired resumption tokens.
        $this->deleteExpiredTokens();

        $verb = $this->parameters['verb'] ?? null;

        match ($verb) {
            'GetRecord' => $this->verbGetRecord(),
            'Identify' => $this->verbIdentify(),
            'ListIdentifiers' => $this->verbListIdentifiers(),
            'ListMetadataFormats'=> $this->verbListMetadataFormats(),
            'ListRecords' => $this->verbListRecords(),
            'ListSets' => $this->verbListSets(),
            default => $this->error = 'badVerb'
        };

        $this->view->assign('parameters', $this->parameters);
        $this->view->assign('error', $this->error);

        // Generate the XML output.
        $xmlOutput = $this->view->render();

        // Format the XML.
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        // Here we could also choose `false` for a minimized XML.
        $dom->formatOutput = true;
        $dom->loadXML($xmlOutput);
        $formattedXmlOutput = trim($dom->saveXML());

        // Return the formatted XML.
        return $this->htmlResponse($formattedXmlOutput);
    }

    /**
     * Continue with resumption token
     *
     * @access protected
     *
     * @return array|null list of uids
     */
    protected function resume(): ?array
    {
        $token = $this->tokenRepository->findOneBy(['token' => $this->parameters['resumptionToken']]);

        if ($token) {
            $options = $token->getOptions();
            if (is_array($options)) {
                return $options;
            }
        }

        // No resumption token found or resumption token expired.
        $this->error = 'badResumptionToken';
        return null;
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
        $document['metadata'] = match ($this->parameters['metadataPrefix']) {
            'oai_dc' => $this->getDublinCoreData($document),
            'epicur' => $document,
            'mets' => $this->getMetsData($document),
            default => null,
        };

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
        $library = $this->libraryRepository->findByUid($this->settings['library'] ?? 0);

        $oaiIdentifyInfo = [];

        $oaiIdentifyInfo['oai_label'] = $library ? $library->getOaiLabel() : '';
        // Use default values for an installation with incomplete plugin configuration.
        if (empty($oaiIdentifyInfo['oai_label'])) {
            $oaiIdentifyInfo['oai_label'] = 'Kitodo.Presentation OAI-PMH Interface (default configuration)';
            $this->logger->notice('Incomplete plugin configuration (oai_label is missing)');
        }

        $oaiIdentifyInfo['contact'] = $library ? $library->getContact() : '';
        if (empty($oaiIdentifyInfo['contact'])) {
            $oaiIdentifyInfo['contact'] = 'unknown@example.org';
            $this->logger->notice('Incomplete plugin configuration (contact is missing)');
        }

        $document = $this->documentRepository->findOldestDocument();

        if ($document) {
            $oaiIdentifyInfo['earliestDatestamp'] = gmdate('Y-m-d\TH:i:s\Z', $document->getTstamp()->getTimestamp());
        } else {
            // Provide a fallback timestamp if no document is found
            $oaiIdentifyInfo['earliestDatestamp'] = '0000-00-00T00:00:00Z';

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
                if (is_array($resultSet)) {
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
            $documentSet = $this->fetchDocumentSet();
        } catch (\Exception $exception) {
            $this->error = 'idDoesNotExist';
            return;
        }
        // create new and empty document list
        $resultSet = [];
        if (is_array($documentSet)) {
            $resultSet['elements'] = $documentSet;
            $resultSet['metadata'] = [
                'cursor' => 0,
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
            $resArray = $this->documentRepository->findOneBy(['recordId' => $this->parameters['identifier']]);
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
                if (is_array($resultSet)) {
                    $listRecords = $this->generateOutputForDocumentList($resultSet);
                    $this->parameters['metadataPrefix'] = $resultSet['metadata']['metadataPrefix'];
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
            $documentSet = $this->fetchDocumentSet();
        } catch (\Exception $exception) {
            $this->error = 'idDoesNotExist';
            return;
        }
        $resultSet = [];
        if (count($documentSet) > 0) {
            $resultSet['elements'] = $documentSet;
            $resultSet['metadata'] = [
                'cursor' => 0,
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
        // It is required to set oai_name inside the collection record to be shown in oai-pmh plugin.
        $this->settings['hideEmptyOaiNames'] = true;

        $oaiSets = $this->collectionRepository->findCollectionsBySettings($this->settings);

        $this->view->assign('oaiSets', $oaiSets);
    }

    /**
     * Fetch records
     *
     * @access protected
     *
     * @return array matching records or empty array if there were some errors
     */
    protected function fetchDocumentSet(): array
    {
        $documentSet = [];
        $solrQuery = '';
        // Check "set" for valid value.
        if (!empty($this->parameters['set'])) {
            // For SOLR we need the index_name of the collection,
            // For DB Query we need the UID of the collection

            $result = $this->collectionRepository->getIndexNameForSolr($this->settings, $this->parameters['set']);
            $resArray = $result->fetchAssociative();
            if ($resArray) {
                if ($resArray['index_query'] != "") {
                    $solrQuery .= '(' . $resArray['index_query'] . ')';
                } else {
                    $solrQuery .= 'collection:' . '"' . $resArray['index_name'] . '"';
                }
            } else {
                $this->error = 'noSetHierarchy';
                return $documentSet;
            }
        } else {
            // If no set is specified we have to query for all collections
            $solrQuery .= 'collection:* NOT collection:""';
        }
        // Check for required fields.
        foreach ($this->formats[$this->parameters['metadataPrefix']]['requiredFields'] as $required) {
            $solrQuery .= ' NOT ' . $required . ':""';
        }
        // toplevel="true" is always required
        $solrQuery .= ' AND toplevel:true';

        $from = $this->getFrom();
        $until = $this->getUntil($from);

        $this->checkGranularity();

        if ($this->error === 'badArgument') {
            return $documentSet;
        }

        $solrQuery .= ' AND timestamp:[' . $from . ' TO ' . $until . ']';

        $solrcore = $this->settings['solrcore'] ?? false;
        if (!$solrcore) {
            $this->logger->error('Solr core not configured');
            return $documentSet;
        }
        $solr = Solr::getInstance($solrcore);
        if (!$solr->ready) {
            $this->logger->error('Apache Solr not available');
            return $documentSet;
        }
        if ($this->settings['solr_limit'] > 0) {
            $solr->limit = $this->settings['solr_limit'];
        }
        // We only care about the UID in the results and want them sorted
        $parameters = [
            "fields" => "uid",
            "sort" => [
                "uid" => "asc"
            ]
        ];
        $parameters['query'] = $solrQuery;
        $result = $solr->searchRaw($parameters);
        if (empty($result)) {
            $this->error = 'noRecordsMatch';
            return $documentSet;
        }
        foreach ($result as $doc) {
            $documentSet[] = $doc->uid;
        }
        return $documentSet;
    }

    /**
     * Get 'from' query parameter.
     *
     * @access private
     *
     * @return string
     */
    private function getFrom(): string
    {
        $from = "*";
        // Check "from" for valid value.
        if (!empty($this->parameters['from'])) {
            // Is valid format?
            $date = $this->getDateTimeFromParameter('from');
            if ($date) {
                $from = $this->dateTimeToString($date, '.000Z');
            } else {
                $this->error = 'badArgument';
            }
        }
        return $from;
    }

    /**
     * Get 'until' query parameter.
     *
     * @access private
     *
     * @param string $from start date
     *
     * @return string
     */
    private function getUntil(string $from): string
    {
        $until = "*";
        // Check "until" for valid value.
        if (!empty($this->parameters['until'])) {
            // Is valid format?
            $date = $this->getDateTimeFromParameter('until');
            if ($date) {
                $until = $this->dateTimeToString($date, '.999Z');
                if ($from != "*" && $from > $until) {
                    $this->error = 'badArgument';
                }
            } else {
                $this->error = 'badArgument';
            }
        }
        return $until;
    }

    /**
     * Get date from parameter string.
     *
     * @access private
     *
     * @param string $dateType
     *
     * @return DateTime|false
     */
    private function getDateTimeFromParameter(string $dateType)
    {
        return DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $this->parameters[$dateType])
            ?: DateTime::createFromFormat('Y-m-d', $this->parameters[$dateType]);
    }

    /**
     * Get date from timestamp.
     *
     * @access private
     *
     * @param array $date
     * @param string $end
     *
     * @return string
     */
    private function dateTimeToString(DateTime $date, string $end): string
    {
        return $date->format('Y-m-d') . 'T' . $date->format("H:i:s") . $end;
    }

    /**
     * Check "from" and "until" for same granularity.
     *
     * @access private
     *
     * @return void
     */
    private function checkGranularity(): void
    {
        if (
            !empty($this->parameters['from'])
            && !empty($this->parameters['until'])
        ) {
            if (strlen($this->parameters['from']) != strlen($this->parameters['until'])) {
                $this->error = 'badArgument';
            }
        }
    }

    /**
     * Fetch more information for document list
     *
     * @access protected
     *
     * @param array $documentListSet
     *
     * @return array of enriched records
     */
    protected function generateOutputForDocumentList(array $documentListSet)
    {
        // check whether any result elements are available
        if (empty($documentListSet) || empty($documentListSet['elements'])) {
            $this->error = 'noRecordsMatch';
            return [];
        }
        // consume result elements from list to implement pagination logic of resumptionToken
        $documentsToProcess = array_splice($documentListSet['elements'], 0, $this->settings['limit']);
        $verb = $this->parameters['verb'];

        $documents = $this->documentRepository->getOaiDocumentList($documentsToProcess);

        $records = [];
        while ($resArray = $documents->fetchAssociative()) {
            // we need the collections as array later
            $resArray['collections'] = explode(' ', $resArray['collections']);

            if ($verb === 'ListRecords') {
                // Add metadata node.
                $metadataPrefix = $this->parameters['metadataPrefix'] ?? false;
                if (!$metadataPrefix) {
                    // If we resume an action the metadataPrefix is stored with the documentSet
                    $metadataPrefix = $documentListSet['metadata']['metadataPrefix'];
                }
                $resArray['metadata'] = match ($metadataPrefix) {
                    'oai_dc' => $this->getDublinCoreData($resArray),
                    'epicur' => $resArray,
                    'mets' => $this->getMetsData($resArray),
                    default => null,
                };
            }

            $records[] = $resArray;
        }

        $this->generateResumptionTokenForDocumentListSet($documentListSet, count($documentsToProcess));

        return $records;
    }

    /**
     * Generate resumption token
     *
     * @access protected
     *
     * @param array $documentListSet
     * @param int $numShownDocuments
     *
     * @return void
     */
    protected function generateResumptionTokenForDocumentListSet(array $documentListSet, int $numShownDocuments)
    {
        // The cursor specifies how many elements have already been returned in previous requests
        // See https://www.openarchives.org/OAI/openarchivesprotocol.html#FlowControl
        $currentCursor = $documentListSet['metadata']['cursor'];

        if (count($documentListSet['elements']) !== 0) {
            $resumptionToken = uniqid('', false);

            $documentListSet['metadata']['cursor'] += $numShownDocuments;

            // create new token
            $newToken = GeneralUtility::makeInstance(Token::class);
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
        $resumptionTokenInfo['cursor'] = $currentCursor;
        $resumptionTokenInfo['completeListSize'] = $documentListSet['metadata']['completeListSize'];
        $expireDateTime = new \DateTime();
        $expireDateTime->add(new \DateInterval('PT' . $this->settings['expired'] . 'S'));
        $resumptionTokenInfo['expired'] = $expireDateTime;

        $omitResumptionToken = $currentCursor === 0 && $numShownDocuments >= $documentListSet['metadata']['completeListSize'];
        if (!$omitResumptionToken) {
            $this->view->assign('resumptionToken', $resumptionTokenInfo);
        }
    }
}
