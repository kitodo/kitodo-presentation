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

use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Model\Document;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Ubl\Iiif\Presentation\Common\Model\Resources\AnnotationContainerInterface;
use Ubl\Iiif\Tools\IiifHelper;

/**
 * Indexer class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Indexer
{
    /**
     * The extension key
     *
     * @var string
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * Array of metadata fields' configuration
     * @see loadIndexConf()
     *
     * @var array
     * @access protected
     */
    protected static $fields = [
        'autocomplete' => [],
        'facets' => [],
        'sortables' => [],
        'indexed' => [],
        'stored' => [],
        'tokenized' => [],
        'fieldboost' => []
    ];

    /**
     * Is the index configuration loaded?
     * @see $fields
     *
     * @var bool
     * @access protected
     */
    protected static $fieldsLoaded = false;

    /**
     * List of already processed documents
     *
     * @var array
     * @access protected
     */
    protected static $processedDocs = [];

    /**
     * Instance of \Kitodo\Dlf\Common\Solr class
     *
     * @var \Kitodo\Dlf\Common\Solr
     * @access protected
     */
    protected static $solr;

    /**
     * Insert given document into Solr index
     *
     * @access public
     *
     * @param \Kitodo\Dlf\Domain\Model\Document $document: The document to add
     *
     * @return bool true on success or false on failure
     */
    public static function add(Document $document)
    {
        if (in_array($document->getUid(), self::$processedDocs)) {
            return true;
        } elseif (self::solrConnect($document->getSolrcore(), $document->getPid())) {
            $success = true;
            Helper::getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_be.xlf');
            // Handle multi-volume documents.
            if ($parentId = $document->getPartof()) {
                // initialize documentRepository
                // TODO: When we drop support for TYPO3v9, we needn't/shouldn't use ObjectManager anymore
                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                $documentRepository = $objectManager->get(DocumentRepository::class);
                // get parent document
                $parent = $documentRepository->findByUid($parentId);
                if ($parent) {
                    // get XML document of parent
                    $doc = Doc::getInstance($parent->getLocation(), ['storagePid' => $parent->getPid()], true);
                    if ($doc !== null) {
                        $parent->setDoc($doc);
                        $success = self::add($parent);
                    } else {
                        Helper::log('Could not load parent document with UID ' . $document->getDoc()->parentId, LOG_SEVERITY_ERROR);
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
                foreach ($document->getDoc()->tableOfContents as $logicalUnit) {
                    if ($success) {
                        $success = self::processLogical($document, $logicalUnit);
                    } else {
                        break;
                    }
                }
                // Index full text files if available.
                if ($document->getDoc()->hasFulltext) {
                    foreach ($document->getDoc()->physicalStructure as $pageNumber => $xmlId) {
                        if ($success) {
                            $success = self::processPhysical($document, $pageNumber, $document->getDoc()->physicalStructureInfo[$xmlId]);
                        } else {
                            break;
                        }
                    }
                }
                // Commit all changes.
                $updateQuery = self::$solr->service->createUpdate();
                $updateQuery->addCommit();
                self::$solr->service->update($updateQuery);

                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    if ($success) {
                        Helper::addMessage(
                            sprintf(Helper::getLanguageService()->getLL('flash.documentIndexed'), $document->getTitle(), $document->getUid()),
                            Helper::getLanguageService()->getLL('flash.done'),
                            FlashMessage::OK,
                            true,
                            'core.template.flashMessages'
                        );
                    } else {
                        Helper::addMessage(
                            sprintf(Helper::getLanguageService()->getLL('flash.documentNotIndexed'), $document->getTitle(), $document->getUid()),
                            Helper::getLanguageService()->getLL('flash.error'),
                            FlashMessage::ERROR,
                            true,
                            'core.template.flashMessages'
                        );
                    }
                }
                return $success;
            } catch (\Exception $e) {
                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    Helper::addMessage(
                        Helper::getLanguageService()->getLL('flash.solrException') . ' ' . htmlspecialchars($e->getMessage()),
                        Helper::getLanguageService()->getLL('flash.error'),
                        FlashMessage::ERROR,
                        true,
                        'core.template.flashMessages'
                    );
                }
                Helper::log('Apache Solr threw exception: "' . $e->getMessage() . '"', LOG_SEVERITY_ERROR);
                return false;
            }
        } else {
            if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                Helper::addMessage(
                    Helper::getLanguageService()->getLL('flash.solrNoConnection'),
                    Helper::getLanguageService()->getLL('flash.warning'),
                    FlashMessage::WARNING,
                    true,
                    'core.template.flashMessages'
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
     * @param string $index_name: The metadata field's name in database
     * @param int $pid: UID of the configuration page
     *
     * @return string The field's dynamic index name
     */
    public static function getIndexFieldName($index_name, $pid = 0)
    {
        // Sanitize input.
        $pid = max(intval($pid), 0);
        if (!$pid) {
            Helper::log('Invalid PID ' . $pid . ' for metadata configuration', LOG_SEVERITY_ERROR);
            return '';
        }
        // Load metadata configuration.
        self::loadIndexConf($pid);
        // Build field's suffix.
        $suffix = (in_array($index_name, self::$fields['tokenized']) ? 't' : 'u');
        $suffix .= (in_array($index_name, self::$fields['stored']) ? 's' : 'u');
        $suffix .= (in_array($index_name, self::$fields['indexed']) ? 'i' : 'u');
        $index_name .= '_' . $suffix;
        return $index_name;
    }

    /**
     * Load indexing configuration
     *
     * @access protected
     *
     * @param int $pid: The configuration page's UID
     *
     * @return void
     */
    protected static function loadIndexConf($pid)
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
                    $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($pid)),
                    Helper::whereExpression('tx_dlf_metadata')
                )
                ->execute();

            while ($indexing = $result->fetch()) {
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
     * @param \Kitodo\Dlf\Domain\Model\Document $document: The METS document
     * @param array $logicalUnit: Array of the logical unit to process
     *
     * @return bool true on success or false on failure
     */
    protected static function processLogical(Document $document, array $logicalUnit)
    {
        $success = true;
        $doc = $document->getDoc();
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
            $solrDoc = $updateQuery->createDocument();
            $solrDoc = self::getSolrDocument($updateQuery, $document, $logicalUnit);
            if (MathUtility::canBeInterpretedAsInteger($logicalUnit['points'])) {
                $solrDoc->setField('page', $logicalUnit['points']);
            }
            if ($logicalUnit['id'] == $doc->toplevelId) {
                $solrDoc->setField('thumbnail', $doc->thumbnail);
            } elseif (!empty($logicalUnit['thumbnailId'])) {
                $solrDoc->setField('thumbnail', $doc->getFileLocation($logicalUnit['thumbnailId']));
            }
            // There can be only one toplevel unit per UID, independently of backend configuration
            $solrDoc->setField('toplevel', $logicalUnit['id'] == $doc->toplevelId ? true : false);
            $solrDoc->setField('title', $metadata['title'][0], self::$fields['fieldboost']['title']);
            $solrDoc->setField('volume', $metadata['volume'][0], self::$fields['fieldboost']['volume']);
            // verify date formatting
            if(strtotime($metadata['date'][0])) {
                // do not alter dates YYYY or YYYY-MM or YYYY-MM-DD
                if (
                    preg_match("/^[\d]{4}$/", $metadata['date'][0])
                    || preg_match("/^[\d]{4}-[\d]{2}$/", $metadata['date'][0])
                    || preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}$/", $metadata['date'][0])
                ) {
                    $solrDoc->setField('date', $metadata['date'][0]);
                // change date YYYYMMDD to YYYY-MM-DD
                } elseif (preg_match("/^[\d]{8}$/", $metadata['date'][0])){
                    $solrDoc->setField('date', date("Y-m-d", strtotime($metadata['date'][0])));
                // convert any datetime to proper ISO extended datetime format and timezone for SOLR
                } else {
                    $solrDoc->setField('date', date('Y-m-d\TH:i:s\Z', strtotime($metadata['date'][0])));
                }
                $solrDoc->setField('date', $metadata['date'][0]);
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
                $solrDoc->setField('geom', json_encode($coordinates->features[0]));
            }
            $autocomplete = [];
            foreach ($metadata as $index_name => $data) {
                if (
                    !empty($data)
                    && substr($index_name, -8) !== '_sorting'
                ) {
                    $solrDoc->setField(self::getIndexFieldName($index_name, $document->getPid()), $data, self::$fields['fieldboost'][$index_name]);
                    if (in_array($index_name, self::$fields['sortables'])) {
                        // Add sortable fields to index.
                        $solrDoc->setField($index_name . '_sorting', $metadata[$index_name . '_sorting'][0]);
                    }
                    if (in_array($index_name, self::$fields['facets'])) {
                        // Add facets to index.
                        $solrDoc->setField($index_name . '_faceting', $data);
                    }
                    if (in_array($index_name, self::$fields['autocomplete'])) {
                        $autocomplete = array_merge($autocomplete, $data);
                    }
                }
            }
            // Add autocomplete values to index.
            if (!empty($autocomplete)) {
                $solrDoc->setField('autocomplete', $autocomplete);
            }
            // Add collection information to logical sub-elements if applicable.
            if (
                in_array('collection', self::$fields['facets'])
                && empty($metadata['collection'])
                && !empty($doc->metadataArray[$doc->toplevelId]['collection'])
            ) {
                $solrDoc->setField('collection_faceting', $doc->metadataArray[$doc->toplevelId]['collection']);
            }
            try {
                $updateQuery->addDocument($solrDoc);
                self::$solr->service->update($updateQuery);
            } catch (\Exception $e) {
                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    Helper::addMessage(
                        Helper::getLanguageService()->getLL('flash.solrException') . '<br />' . htmlspecialchars($e->getMessage()),
                        Helper::getLanguageService()->getLL('flash.error'),
                        FlashMessage::ERROR,
                        true,
                        'core.template.flashMessages'
                    );
                }
                Helper::log('Apache Solr threw exception: "' . $e->getMessage() . '"', LOG_SEVERITY_ERROR);
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
     * @param \Kitodo\Dlf\Domain\Model\Document $document: The METS document
     * @param int $page: The page number
     * @param array $physicalUnit: Array of the physical unit to process
     *
     * @return bool true on success or false on failure
     */
    protected static function processPhysical(Document $document, $page, array $physicalUnit)
    {
        $doc = $document->getDoc();
        $doc->cPid = $document->getPid();
        if ($doc->hasFulltext && $fullText = $doc->getFullText($physicalUnit['id'])) {
            // Read extension configuration.
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey);
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
            $solrDoc->setField('collection', $doc->metadataArray[$doc->toplevelId]['collection']);

            $solrDoc->setField('fulltext', $fullText);
            if (is_array($doc->metadataArray[$doc->toplevelId])) {
                // Add faceting information to physical sub-elements if applicable.
                foreach ($doc->metadataArray[$doc->toplevelId] as $index_name => $data) {
                    if (
                        !empty($data)
                        && substr($index_name, -8) !== '_sorting'
                    ) {

                        if (in_array($index_name, self::$fields['facets'])) {
                            // Remove appended "valueURI" from authors' names for indexing.
                            if ($index_name == 'author') {
                                $data = self::removeAppendsFromAuthor($data);
                            }
                            // Add facets to index.
                            $solrDoc->setField($index_name . '_faceting', $data);
                        }
                    }
                    // Add sorting information to physical sub-elements if applicable.
                    if (
                        !empty($data)
                        && substr($index_name, -8) == '_sorting'
                    ) {
                        $solrDoc->setField($index_name , $doc->metadataArray[$doc->toplevelId][$index_name]);
                    }
                }
            }
            // Add collection information to physical sub-elements if applicable.
            if (
                in_array('collection', self::$fields['facets'])
                && !empty($doc->metadataArray[$doc->toplevelId]['collection'])
            ) {
                $solrDoc->setField('collection_faceting', $doc->metadataArray[$doc->toplevelId]['collection']);
            }
            try {
                $updateQuery->addDocument($solrDoc);
                self::$solr->service->update($updateQuery);
            } catch (\Exception $e) {
                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    Helper::addMessage(
                        Helper::getLanguageService()->getLL('flash.solrException') . '<br />' . htmlspecialchars($e->getMessage()),
                        Helper::getLanguageService()->getLL('flash.error'),
                        FlashMessage::ERROR,
                        true,
                        'core.template.flashMessages'
                    );
                }
                Helper::log('Apache Solr threw exception: "' . $e->getMessage() . '"', LOG_SEVERITY_ERROR);
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
     * @param int $core: UID of the Solr core
     * @param int $pid: UID of the configuration page
     *
     * @return bool true on success or false on failure
     */
    protected static function solrConnect($core, $pid = 0)
    {
        // Get Solr instance.
        if (!self::$solr) {
            // Connect to Solr server.
            $solr = Solr::getInstance($core);
            if ($solr->ready) {
                self::$solr = $solr;
                // Load indexing configuration if needed.
                if ($pid) {
                    self::loadIndexConf($pid);
                }
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Get SOLR document with set standard fields (identical for logical and physical unit)
     *
     * @access private
     *
     * @param \Solarium\QueryType\Update\Query\Query $updateQuery solarium query
     * @param \Kitodo\Dlf\Domain\Model\Document $document: The METS document
     * @param array $unit: Array of the logical or physical unit to process
     * @param string $fullText: Text containing full text for indexing
     *
     * @return \Solarium\Core\Query\DocumentInterface
     */
    private static function getSolrDocument($updateQuery, $document, $unit, $fullText = '') {
        $solrDoc = $updateQuery->createDocument();
        // Create unique identifier from document's UID and unit's XML ID.
        $solrDoc->setField('id', $document->getUid() . $unit['id']);
        $solrDoc->setField('uid', $document->getUid());
        $solrDoc->setField('pid', $document->getPid());
        $solrDoc->setField('partof', $document->getPartof());
        $solrDoc->setField('root', $document->getDoc()->rootId);
        $solrDoc->setField('sid', $unit['id']);
        $solrDoc->setField('type', $unit['type'], self::$fields['fieldboost']['type']);
        $solrDoc->setField('collection', $document->getDoc()->metadataArray[$document->getDoc()->toplevelId]['collection']);
        $solrDoc->setField('fulltext', $fullText);
        return $solrDoc;
    }

    /**
     * Remove appended "valueURI" from authors' names for indexing.
     *
     * @access private
     *
     * @param array|string $authors: Array or string containing author/authors
     *
     * @return array|string
     */
    private static function removeAppendsFromAuthor($authors) {
        if (is_array($authors)) {
            foreach ($authors as $i => $author) {
                $splitName = explode(chr(31), $author);
                $authors[$i] = $splitName[0];
            }
        }
        return $authors;
    }

    /**
     * Prevent instantiation by hiding the constructor
     *
     * @access private
     */
    private function __construct()
    {
        // This is a static class, thus no instances should be created.
    }
}
