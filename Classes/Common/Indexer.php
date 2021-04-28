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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
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
     * @param \Kitodo\Dlf\Common\Document &$doc: The document to add
     * @param int $core: UID of the Solr core to use
     *
     * @return int 0 on success or 1 on failure
     */
    public static function add(Document &$doc, $core = 0)
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        if (in_array($doc->uid, self::$processedDocs)) {
            return 0;
        } elseif (self::solrConnect($core, $doc->pid)) {
            $errors = 0;
            // Handle multi-volume documents.
            if ($doc->parentId) {
                $parent = Document::getInstance($doc->parentId, 0, true);
                if ($parent->ready) {
                    $errors = self::add($parent, $core);
                } else {
                    $logger->error('Could not load parent document with UID ' . $doc->parentId);
                    return 1;
                }
            }
            try {
                // Add document to list of processed documents.
                self::$processedDocs[] = $doc->uid;
                // Delete old Solr documents.
                $updateQuery = self::$solr->service->createUpdate();
                $updateQuery->addDeleteQuery('uid:' . $doc->uid);
                self::$solr->service->update($updateQuery);
                //TODO: handle problem with indexing documents without OCR
                // Index every logical unit as separate Solr document.
                /*foreach ($doc->tableOfContents as $logicalUnit) {
                    if (!$errors) {
                        $errors = self::processLogical($doc, $logicalUnit);
                    } else {
                        break;
                    }
                }*/
                // Index full text files if available.
                if ($doc->hasFulltext) {
                    foreach ($doc->physicalStructure as $pageNumber => $xmlId) {
                        if (!$errors) {
                            $errors = self::processPhysical($doc, $pageNumber, $doc->physicalStructureInfo[$xmlId]);
                        } else {
                            break;
                        }
                    }
                }
                // Commit all changes.
                $updateQuery = self::$solr->service->createUpdate();
                $updateQuery->addCommit();
                self::$solr->service->update($updateQuery);

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('tx_dlf_documents');

                // Get document title from database.
                $result = $queryBuilder
                    ->select('tx_dlf_documents.title AS title')
                    ->from('tx_dlf_documents')
                    ->where(
                        $queryBuilder->expr()->eq('tx_dlf_documents.uid', intval($doc->uid)),
                        Helper::whereExpression('tx_dlf_documents')
                    )
                    ->setMaxResults(1)
                    ->execute();

                $allResults = $result->fetchAll();
                $resArray = $allResults[0];
                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    if (!$errors) {
                        Helper::addMessage(
                            htmlspecialchars(sprintf(Helper::getMessage('flash.documentIndexed'), $resArray['title'], $doc->uid)),
                            Helper::getMessage('flash.done', true),
                            FlashMessage::OK,
                            true,
                            'core.template.flashMessages'
                        );
                    } else {
                        Helper::addMessage(
                            htmlspecialchars(sprintf(Helper::getMessage('flash.documentNotIndexed'), $resArray['title'], $doc->uid)),
                            Helper::getMessage('flash.error', true),
                            FlashMessage::ERROR,
                            true,
                            'core.template.flashMessages'
                        );
                    }
                }
                return $errors;
            } catch (\Exception $e) {
                if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                    Helper::addMessage(
                        Helper::getMessage('flash.solrException', true) . '<br />' . htmlspecialchars($e->getMessage()),
                        Helper::getMessage('flash.error', true),
                        FlashMessage::ERROR,
                        true,
                        'core.template.flashMessages'
                    );
                }
                $logger->error('Apache Solr threw exception: "' . $e->getMessage() . '"');
                return 1;
            }
        } else {
            if (!(\TYPO3_REQUESTTYPE & \TYPO3_REQUESTTYPE_CLI)) {
                Helper::addMessage(
                    Helper::getMessage('flash.solrNoConnection', true),
                    Helper::getMessage('flash.warning', true),
                    FlashMessage::WARNING,
                    true,
                    'core.template.flashMessages'
                );
            }
            $logger->error('Could not connect to Apache Solr server');
            return 1;
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
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        // Sanitize input.
        $pid = max(intval($pid), 0);
        if (!$pid) {
            $logger->error('Invalid PID ' . $pid . ' for metadata configuration');
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
     * @param \Kitodo\Dlf\Common\Document &$doc: The METS document
     * @param array $logicalUnit: Array of the logical unit to process
     *
     * @return int 0 on success or 1 on failure
     */
    protected static function processLogical(Document &$doc, array $logicalUnit)
    {
        $logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);

        $errors = 0;
        // Get metadata for logical unit.
        $metadata = $doc->metadataArray[$logicalUnit['id']];
        if (!empty($metadata)) {
            // Remove appended "valueURI" from authors' names for indexing.
            if (is_array($metadata['author'])) {
                foreach ($metadata['author'] as $i => $author) {
                    $splitName = explode(chr(31), $author);
                    $metadata['author'][$i] = $splitName[0];
                }
            }
            // Create new Solr document.
            $updateQuery = self::$solr->service->createUpdate();
            $solrDoc = $updateQuery->createDocument();
            // Create unique identifier from document's UID and unit's XML ID.
            $solrDoc->setField('id', $doc->uid . $logicalUnit['id']);
            $solrDoc->setField('uid', $doc->uid);
            $solrDoc->setField('pid', $doc->pid);
            if (MathUtility::canBeInterpretedAsInteger($logicalUnit['points'])) {
                $solrDoc->setField('page', $logicalUnit['points']);
            }
            if ($logicalUnit['id'] == $doc->toplevelId) {
                $solrDoc->setField('thumbnail', $doc->thumbnail);
            } elseif (!empty($logicalUnit['thumbnailId'])) {
                $solrDoc->setField('thumbnail', $doc->getFileLocation($logicalUnit['thumbnailId']));
            }
            $solrDoc->setField('partof', $doc->parentId);
            $solrDoc->setField('root', $doc->rootId);
            $solrDoc->setField('sid', $logicalUnit['id']);
            // There can be only one toplevel unit per UID, independently of backend configuration
            $solrDoc->setField('toplevel', $logicalUnit['id'] == $doc->toplevelId ? true : false);
            $solrDoc->setField('type', $logicalUnit['type'], self::$fields['fieldboost']['type']);
            $solrDoc->setField('title', $metadata['title'][0], self::$fields['fieldboost']['title']);
            $solrDoc->setField('volume', $metadata['volume'][0], self::$fields['fieldboost']['volume']);
            $solrDoc->setField('record_id', $metadata['record_id'][0]);
            $solrDoc->setField('purl', $metadata['purl'][0]);
            $solrDoc->setField('location', $doc->location);
            $solrDoc->setField('urn', $metadata['urn']);
            $solrDoc->setField('license', $metadata['license']);
            $solrDoc->setField('terms', $metadata['terms']);
            $solrDoc->setField('restrictions', $metadata['restrictions']);
            $solrDoc->setField('collection', $doc->metadataArray[$doc->toplevelId]['collection']);
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
                    $solrDoc->setField(self::getIndexFieldName($index_name, $doc->pid), $data, self::$fields['fieldboost'][$index_name]);
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
                        Helper::getMessage('flash.solrException', true) . '<br />' . htmlspecialchars($e->getMessage()),
                        Helper::getMessage('flash.error', true),
                        FlashMessage::ERROR,
                        true,
                        'core.template.flashMessages'
                    );
                }
                $logger->error('Apache Solr threw exception: "' . $e->getMessage() . '"');
                return 1;
            }
        }
        // Check for child elements...
        if (!empty($logicalUnit['children'])) {
            foreach ($logicalUnit['children'] as $child) {
                if (!$errors) {
                    // ...and process them, too.
                    $errors = self::processLogical($doc, $child);
                } else {
                    break;
                }
            }
        }
        return $errors;
    }

    /**
     * Processes a physical unit for the Solr index
     *
     * @access protected
     *
     * @param \Kitodo\Dlf\Common\Document &$doc: The METS document
     * @param int $page: The page number
     * @param array $physicalUnit: Array of the physical unit to process
     *
     * @return int 0 on success or 1 on failure
     */
    protected static function processPhysical(Document &$doc, $page, array $physicalUnit)
    {
        $logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);

        $logger->error($doc->hasFulltext && $fullText = $doc->getFullText($physicalUnit['id']));
        if ($doc->hasFulltext && $fullText = $doc->getFullText($physicalUnit['id'])) {
            // Read extension configuration.
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey);
            // Create new Solr document.
            $updateQuery = self::$solr->service->createUpdate();
            $solrDoc = $updateQuery->createDocument();
            // Create unique identifier from document's UID and unit's XML ID.
            $solrDoc->setField('id', $doc->uid . $physicalUnit['id']);
            $solrDoc->setField('uid', $doc->uid);
            $solrDoc->setField('pid', $doc->pid);
            $solrDoc->setField('page', $page);
            $fileGrpsThumb = GeneralUtility::trimExplode(',', $extConf['fileGrpThumbs']);
            while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                if (!empty($physicalUnit['files'][$fileGrpThumb])) {
                    $solrDoc->setField('thumbnail', $doc->getFileLocation($physicalUnit['files'][$fileGrpThumb]));
                    break;
                }
            }
            $solrDoc->setField('partof', $doc->parentId);
            $solrDoc->setField('root', $doc->rootId);
            $solrDoc->setField('sid', $physicalUnit['id']);
            $solrDoc->setField('toplevel', false);
            $solrDoc->setField('type', $physicalUnit['type'], self::$fields['fieldboost']['type']);
            $solrDoc->setField('collection', $doc->metadataArray[$doc->toplevelId]['collection']);
            $solrDoc->setField('fulltext', $fullText);
            // Add faceting information to physical sub-elements if applicable.
            foreach ($doc->metadataArray[$doc->toplevelId] as $index_name => $data) {
                if (
                    !empty($data)
                    && substr($index_name, -8) !== '_sorting'
                ) {

                    if (in_array($index_name, self::$fields['facets'])) {
                        // Remove appended "valueURI" from authors' names for indexing.
                        if ($index_name == 'author') {
                            foreach ($data as $i => $author) {
                                $splitName = explode(chr(31), $author);
                                $data[$i] = $splitName[0];
                            }
                        }
                        // Add facets to index.
                        $solrDoc->setField($index_name . '_faceting', $data);
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
                        Helper::getMessage('flash.solrException', true) . '<br />' . htmlspecialchars($e->getMessage()),
                        Helper::getMessage('flash.error', true),
                        FlashMessage::ERROR,
                        true,
                        'core.template.flashMessages'
                    );
                    $logger->error('Apache Solr threw exception: "' . $e->getMessage() . '"');
                }
                return 1;
            }
        }
        return 0;
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
     * Prevent instantiation by hiding the constructor
     *
     * @access private
     */
    private function __construct()
    {
        // This is a static class, thus no instances should be created.
    }
}
