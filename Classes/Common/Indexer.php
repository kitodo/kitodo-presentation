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

namespace Kitodo\Dlf\Common;

use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Model\Document;
use Solarium\Core\Query\DocumentInterface;
use Solarium\QueryType\Update\Query\Query;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Indexer class for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Indexer
{
    /**
     * @access public
     * @static
     * @var string The extension key
     */
    public static string $extKey = 'dlf';

    /**
     * @access protected
     * @static
     * @var array Array of metadata fields' configuration
     *
     * @see loadIndexConf()
     */
    protected static array $fields = [
        'autocomplete' => [],
        'facets' => [],
        'sortables' => [],
        'indexed' => [],
        'stored' => [],
        'tokenized' => [],
        'fieldboost' => []
    ];

    /**
     * @access protected
     * @static
     * @var bool Is the index configuration loaded?
     *
     * @see $fields
     */
    protected static bool $fieldsLoaded = false;

    /**
     * @access protected
     * @static
     * @var array List of already processed documents
     */
    protected static array $processedDocs = [];

    /**
     * @access protected
     * @static
     * @var Solr Instance of Solr class
     */
    protected static Solr $solr;

    /**
     * Insert given document into Solr index
     *
     * @access public
     *
     * @static
     *
     * @param Document $document The document to add
     * @param DocumentRepository $documentRepository The document repository for search of parent
     *
     * @return bool true on success or false on failure
     */
    public static function add(Document $document, DocumentRepository $documentRepository): bool
    {
        if (in_array($document->getUid(), self::$processedDocs)) {
            return true;
        } elseif (self::solrConnect($document->getSolrcore(), $document->getPid())) {
            $success = true;
            Helper::getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_be.xlf');
            // Handle multi-volume documents.
            $parentId = $document->getPartof();
            if ($parentId) {
                // get parent document
                $parent = $documentRepository->findByUid($parentId);
                if ($parent) {
                    // get XML document of parent
                    $doc = AbstractDocument::getInstance($parent->getLocation(), ['storagePid' => $parent->getPid()], true);
                    if ($doc !== null) {
                        $parent->setCurrentDocument($doc);
                        $success = self::add($parent, $documentRepository);
                    } else {
                        Helper::log('Could not load parent document with UID ' . $document->getCurrentDocument()->parentId, LOG_SEVERITY_ERROR);
                        return false;
                    }
                }
            }
            try {
                // Add document to list of processed documents.
                self::$processedDocs[] = $document->getUid();
                // Delete old Solr documents.
                $updateQuery = self::$solr->service->createUpdate();
                $updateQuery->addDeleteQuery('uid:' . $document->getUid());
                self::$solr->service->update($updateQuery);

                // Index every logical unit as separate Solr document.
                foreach ($document->getCurrentDocument()->tableOfContents as $logicalUnit) {
                    if ($success) {
                        $success = self::processLogical($document, $logicalUnit);
                    } else {
                        break;
                    }
                }
                // Index full text files if available.
                if ($document->getCurrentDocument()->hasFulltext) {
                    foreach ($document->getCurrentDocument()->physicalStructure as $pageNumber => $xmlId) {
                        if ($success) {
                            $success = self::processPhysical($document, $pageNumber, $document->getCurrentDocument()->physicalStructureInfo[$xmlId]);
                        } else {
                            break;
                        }
                    }
                }
                // Commit all changes.
                $updateQuery = self::$solr->service->createUpdate();
                $updateQuery->addCommit();
                self::$solr->service->update($updateQuery);

                if (!(Environment::isCli())) {
                    if ($success) {
                        self::addMessage(
                            sprintf(Helper::getLanguageService()->getLL('flash.documentIndexed'), $document->getTitle(), $document->getUid()),
                            'flash.done',
                            FlashMessage::OK
                        );
                    } else {
                        self::addErrorMessage(sprintf(Helper::getLanguageService()->getLL('flash.documentNotIndexed'), $document->getTitle(), $document->getUid()));
                    }
                }
                return $success;
            } catch (\Exception $e) {
                self::handleException($e->getMessage());
                return false;
            }
        } else {
            if (!(Environment::isCli())) {
                self::addMessage(
                    Helper::getLanguageService()->getLL('flash.solrNoConnection'),
                    'flash.warning',
                    FlashMessage::WARNING
                );
            }
            Helper::log('Could not connect to Apache Solr server', LOG_SEVERITY_ERROR);
            return false;
        }
    }

    /**
     * Returns the dynamic index field name for the given metadata field.
     *
     * @access public
     *
     * @static
     *
     * @param string $indexName The metadata field's name in database
     * @param int $pid UID of the configuration page
     *
     * @return string The field's dynamic index name
     */
    public static function getIndexFieldName(string $indexName, int $pid = 0): string
    {
        // Sanitize input.
        $pid = max((int) $pid, 0);
        if (!$pid) {
            Helper::log('Invalid PID ' . $pid . ' for metadata configuration', LOG_SEVERITY_ERROR);
            return '';
        }
        // Load metadata configuration.
        self::loadIndexConf($pid);
        // Build field's suffix.
        $suffix = (in_array($indexName, self::$fields['tokenized']) ? 't' : 'u');
        $suffix .= (in_array($indexName, self::$fields['stored']) ? 's' : 'u');
        $suffix .= (in_array($indexName, self::$fields['indexed']) ? 'i' : 'u');
        $indexName .= '_' . $suffix;
        return $indexName;
    }

    /**
     * Load indexing configuration
     *
     * @access protected
     *
     * @static
     *
     * @param int $pid The configuration page's UID
     *
     * @return void
     */
    protected static function loadIndexConf(int $pid): void
    {
        if (!self::$fieldsLoaded) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_metadata');

            // Get the metadata indexing options.
            $result = $queryBuilder
                ->select(
                    'tx_dlf_metadata.index_name AS index_name',
                    'tx_dlf_metadata.index_tokenized AS index_tokenized',
                    'tx_dlf_metadata.index_stored AS index_stored',
                    'tx_dlf_metadata.index_indexed AS index_indexed',
                    'tx_dlf_metadata.is_sortable AS is_sortable',
                    'tx_dlf_metadata.is_facet AS is_facet',
                    'tx_dlf_metadata.is_listed AS is_listed',
                    'tx_dlf_metadata.index_autocomplete AS index_autocomplete',
                    'tx_dlf_metadata.index_boost AS index_boost'
                )
                ->from('tx_dlf_metadata')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_metadata.pid', (int) $pid),
                    Helper::whereExpression('tx_dlf_metadata')
                )
                ->execute();

            while ($indexing = $result->fetchAssociative()) {
                if ($indexing['index_tokenized']) {
                    self::$fields['tokenized'][] = $indexing['index_name'];
                }
                if (
                    $indexing['index_stored']
                    || $indexing['is_listed']
                ) {
                    self::$fields['stored'][] = $indexing['index_name'];
                }
                if (
                    $indexing['index_indexed']
                    || $indexing['index_autocomplete']
                ) {
                    self::$fields['indexed'][] = $indexing['index_name'];
                }
                if ($indexing['is_sortable']) {
                    self::$fields['sortables'][] = $indexing['index_name'];
                }
                if ($indexing['is_facet']) {
                    self::$fields['facets'][] = $indexing['index_name'];
                }
                if ($indexing['index_autocomplete']) {
                    self::$fields['autocomplete'][] = $indexing['index_name'];
                }
                if ($indexing['index_boost'] > 0.0) {
                    self::$fields['fieldboost'][$indexing['index_name']] = floatval($indexing['index_boost']);
                } else {
                    self::$fields['fieldboost'][$indexing['index_name']] = false;
                }
            }
            self::$fieldsLoaded = true;
        }
    }

    /**
     * Processes a logical unit (and its children) for the Solr index
     *
     * @access protected
     *
     * @static
     *
     * @param Document $document The METS document
     * @param array $logicalUnit Array of the logical unit to process
     *
     * @return bool true on success or false on failure
     */
    protected static function processLogical(Document $document, array $logicalUnit): bool
    {
        $success = true;
        $doc = $document->getCurrentDocument();
        $doc->cPid = $document->getPid();
        // Get metadata for logical unit.
        $metadata = $doc->metadataArray[$logicalUnit['id']];
        if (!empty($metadata)) {
            $metadata['author'] = self::removeAppendsFromAuthor($metadata['author']);
            // set Owner if available
            if ($document->getOwner()) {
                $metadata['owner'][0] = $document->getOwner()->getIndexName();
            }
            // Create new Solr document.
            $updateQuery = self::$solr->service->createUpdate();
            $solrDoc = self::getSolrDocument($updateQuery, $document, $logicalUnit);
            if (MathUtility::canBeInterpretedAsInteger($logicalUnit['points'])) {
                $solrDoc->setField('page', $logicalUnit['points']);
            }
            if ($logicalUnit['id'] == $doc->getToplevelId()) {
                $solrDoc->setField('thumbnail', $doc->thumbnail);
            } elseif (!empty($logicalUnit['thumbnailId'])) {
                $solrDoc->setField('thumbnail', $doc->getFileLocation($logicalUnit['thumbnailId']));
            }
            // There can be only one toplevel unit per UID, independently of backend configuration
            $solrDoc->setField('toplevel', $logicalUnit['id'] == $doc->getToplevelId());
            $solrDoc->setField('title', $metadata['title'][0], self::$fields['fieldboost']['title']);
            $solrDoc->setField('volume', $metadata['volume'][0], self::$fields['fieldboost']['volume']);
            // verify date formatting
            if(strtotime($metadata['date'][0])) {
                $solrDoc->setField('date', self::getFormattedDate($metadata['date'][0]));
            }
            $solrDoc->setField('record_id', $metadata['record_id'][0]);
            $solrDoc->setField('purl', $metadata['purl'][0]);
            $solrDoc->setField('location', $document->getLocation());
            $solrDoc->setField('urn', $metadata['urn']);
            $solrDoc->setField('license', $metadata['license']);
            $solrDoc->setField('terms', $metadata['terms']);
            $solrDoc->setField('restrictions', $metadata['restrictions']);
            $coordinates = json_decode($metadata['coordinates'][0]);
            if (is_object($coordinates)) {
                $feature = (array) $coordinates->features[0];
                $geometry = (array) $feature['geometry'];
                krsort($geometry);
                $feature['geometry'] = $geometry;
                $solrDoc->setField('geom', json_encode($feature));
            }
            $autocomplete = self::processMetadata($document, $metadata, $solrDoc);
            // Add autocomplete values to index.
            if (!empty($autocomplete)) {
                $solrDoc->setField('autocomplete', $autocomplete);
            }
            // Add collection information to logical sub-elements if applicable.
            if (
                in_array('collection', self::$fields['facets'])
                && empty($metadata['collection'])
                && !empty($doc->metadataArray[$doc->getToplevelId()]['collection'])
            ) {
                $solrDoc->setField('collection_faceting', $doc->metadataArray[$doc->getToplevelId()]['collection']);
            }
            try {
                $updateQuery->addDocument($solrDoc);
                self::$solr->service->update($updateQuery);
            } catch (\Exception $e) {
                self::handleException($e->getMessage());
                return false;
            }
        }
        // Check for child elements...
        if (!empty($logicalUnit['children'])) {
            foreach ($logicalUnit['children'] as $child) {
                if ($success) {
                    // ...and process them, too.
                    $success = self::processLogical($document, $child);
                } else {
                    break;
                }
            }
        }
        return $success;
    }

    /**
     * Processes a physical unit for the Solr index
     *
     * @access protected
     *
     * @static
     *
     * @param Document $document The METS document
     * @param int $page The page number
     * @param array $physicalUnit Array of the physical unit to process
     *
     * @return bool true on success or false on failure
     */
    protected static function processPhysical(Document $document, int $page, array $physicalUnit): bool
    {
        $doc = $document->getCurrentDocument();
        $doc->cPid = $document->getPid();
        if ($doc->hasFulltext && $fullText = $doc->getFullText($physicalUnit['id'])) {
            // Read extension configuration.
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey, 'files');
            // Create new Solr document.
            $updateQuery = self::$solr->service->createUpdate();
            $solrDoc = self::getSolrDocument($updateQuery, $document, $physicalUnit, $fullText);
            $solrDoc->setField('page', $page);
            $fileGrpsThumb = GeneralUtility::trimExplode(',', $extConf['fileGrpThumbs']);
            while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                if (!empty($physicalUnit['files'][$fileGrpThumb])) {
                    $solrDoc->setField('thumbnail', $doc->getFileLocation($physicalUnit['files'][$fileGrpThumb]));
                    break;
                }
            }
            $solrDoc->setField('toplevel', false);
            $solrDoc->setField('type', $physicalUnit['type'], self::$fields['fieldboost']['type']);
            $solrDoc->setField('collection', $doc->metadataArray[$doc->getToplevelId()]['collection']);
            $solrDoc->setField('location', $document->getLocation());

            $solrDoc->setField('fulltext', $fullText);
            if (is_array($doc->metadataArray[$doc->getToplevelId()])) {
                self::addFaceting($doc, $solrDoc);
            }
            // Add collection information to physical sub-elements if applicable.
            if (
                in_array('collection', self::$fields['facets'])
                && !empty($doc->metadataArray[$doc->getToplevelId()]['collection'])
            ) {
                $solrDoc->setField('collection_faceting', $doc->metadataArray[$doc->getToplevelId()]['collection']);
            }
            try {
                $updateQuery->addDocument($solrDoc);
                self::$solr->service->update($updateQuery);
            } catch (\Exception $e) {
                self::handleException($e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * Connects to Solr server.
     *
     * @access protected
     *
     * @static
     *
     * @param int $core UID of the Solr core
     * @param int $pid UID of the configuration page
     *
     * @return bool true on success or false on failure
     */
    protected static function solrConnect(int $core, int $pid = 0): bool
    {
        // Get Solr instance.
        $solr = Solr::getInstance($core);
        // Connect to Solr server.
        if ($solr->ready) {
            self::$solr = $solr;
            // Load indexing configuration if needed.
            if ($pid) {
                self::loadIndexConf($pid);
            }
            return true;
        }
        return false;
    }

    /**
     * Process metadata: add facets, sortable fields and create autocomplete array.
     *
     * @static
     *
     * @access private
     *
     * @param Document $document
     * @param array $metadata
     * @param DocumentInterface &$solrDoc
     *
     * @return array empty array or autocomplete values
     */
    private static function processMetadata($document, $metadata, &$solrDoc): array
    {
        $autocomplete = [];
        foreach ($metadata as $indexName => $data) {
            if (
                !empty($data)
                && substr($indexName, -8) !== '_sorting'
            ) {
                $solrDoc->setField(self::getIndexFieldName($indexName, $document->getPid()), $data, self::$fields['fieldboost'][$indexName]);
                if (in_array($indexName, self::$fields['sortables'])) {
                    // Add sortable fields to index.
                    $solrDoc->setField($indexName . '_sorting', $metadata[$indexName . '_sorting'][0]);
                }
                if (in_array($indexName, self::$fields['facets'])) {
                    // Add facets to index.
                    $solrDoc->setField($indexName . '_faceting', $data);
                }
                if (in_array($indexName, self::$fields['autocomplete'])) {
                    $autocomplete = array_merge($autocomplete, $data);
                }
            }
        }
        return $autocomplete;
    }

    /**
     * Add faceting information to physical sub-elements if applicable.
     *
     * @static
     *
     * @access private
     *
     * @param AbstractDocument $doc
     * @param DocumentInterface &$solrDoc
     *
     * @return void
     */
    private static function addFaceting($doc, &$solrDoc): void
    {
        foreach ($doc->metadataArray[$doc->getToplevelId()] as $indexName => $data) {
            if (
                !empty($data)
                && substr($indexName, -8) !== '_sorting'
            ) {

                if (in_array($indexName, self::$fields['facets'])) {
                    // Remove appended "valueURI" from authors' names for indexing.
                    if ($indexName == 'author') {
                        $data = self::removeAppendsFromAuthor($data);
                    }
                    // Add facets to index.
                    $solrDoc->setField($indexName . '_faceting', $data);
                }
            }
            // Add sorting information to physical sub-elements if applicable.
            if (
                !empty($data)
                && substr($indexName, -8) == '_sorting'
            ) {
                $solrDoc->setField($indexName, $doc->metadataArray[$doc->getToplevelId()][$indexName]);
            }
        }
    }

    /**
     * Get SOLR document with set standard fields (identical for logical and physical unit)
     *
     * @access private
     *
     * @static
     *
     * @param Query $updateQuery solarium query
     * @param Document $document The METS document
     * @param array $unit Array of the logical or physical unit to process
     * @param string $fullText Text containing full text for indexing
     *
     * @return DocumentInterface
     */
    private static function getSolrDocument(Query $updateQuery, Document $document, array $unit, string $fullText = ''): DocumentInterface
    {
        $solrDoc = $updateQuery->createDocument();
        // Create unique identifier from document's UID and unit's XML ID.
        $solrDoc->setField('id', $document->getUid() . $unit['id']);
        $solrDoc->setField('uid', $document->getUid());
        $solrDoc->setField('pid', $document->getPid());
        $solrDoc->setField('partof', $document->getPartof());
        $solrDoc->setField('root', $document->getCurrentDocument()->rootId);
        $solrDoc->setField('sid', $unit['id']);
        $solrDoc->setField('type', $unit['type'], self::$fields['fieldboost']['type']);
        $solrDoc->setField('collection', $document->getCurrentDocument()->metadataArray[$document->getCurrentDocument()->getToplevelId()]['collection']);
        $solrDoc->setField('fulltext', $fullText);
        return $solrDoc;
    }

    /**
     * Get formatted date without alteration.
     * Possible formats: YYYY or YYYY-MM or YYYY-MM-DD.
     *
     * @static
     *
     * @access private
     *
     * @param string $date
     *
     * @return string formatted date YYYY or YYYY-MM or YYYY-MM-DD or empty string
     */
    private static function getFormattedDate($date): string
    {
        if (
            preg_match("/^[\d]{4}$/", $date)
            || preg_match("/^[\d]{4}-[\d]{2}$/", $date)
            || preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}$/", $date)
        ) {
            return $date;
        // change date YYYYMMDD to YYYY-MM-DD
        } elseif (preg_match("/^[\d]{8}$/", $date)) {
            return date("Y-m-d", strtotime($date));
        // convert any datetime to proper ISO extended datetime format and timezone for SOLR
        } elseif (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}T.*$/", $date)) {
            return date('Y-m-d\TH:i:s\Z', strtotime($date));
        }
        // date doesn't match any standard
        return '';
    }

    /**
     * Remove appended "valueURI" from authors' names for indexing.
     *
     * @access private
     *
     * @static
     *
     * @param array|string $authors Array or string containing author/authors
     *
     * @return array|string
     */
    private static function removeAppendsFromAuthor($authors)
    {
        if (is_array($authors)) {
            foreach ($authors as $i => $author) {
                if (is_array($author)) {
                    $authors[$i] = $author['name'];
                }
            }
        }
        return $authors;
    }

    /**
     * Handle exception.
     *
     * @static
     *
     * @access private
     *
     * @param string $errorMessage
     *
     * @return void
     */
    private static function handleException(string $errorMessage): void
    {
        if (!(Environment::isCli())) {
            self::addErrorMessage(Helper::getLanguageService()->getLL('flash.solrException') . '<br />' . htmlspecialchars($errorMessage));
        }
        Helper::log('Apache Solr threw exception: "' . $errorMessage . '"', LOG_SEVERITY_ERROR);
    }

    /**
     * Add error message only with message content.
     *
     * @static
     *
     * @access private
     *
     * @param string $message
     *
     * @return void
     */
    private static function addErrorMessage(string $message): void
    {
        self::addMessage(
            $message,
            'flash.error',
            FlashMessage::ERROR
        );
    }

    /**
     * Add message only with changeable parameters.
     *
     * @static
     *
     * @access private
     *
     * @param string $message
     * @param string $type
     * @param int $status
     *
     * @return void
     */
    private static function addMessage(string $message, string $type, int $status): void
    {
        Helper::addMessage(
            $message,
            Helper::getLanguageService()->getLL($type),
            $status,
            true,
            'core.template.flashMessages'
        );
    }

    /**
     * Prevent instantiation by hiding the constructor
     *
     * @access private
     *
     * @return void
     */
    private function __construct()
    {
    }
}
