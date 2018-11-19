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
 * Indexing class 'tx_dlf_indexing' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_indexing {

    /**
     * The extension key
     *
     * @var	string
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * Array of metadata fields' configuration
     * @see loadIndexConf()
     *
     * @var	array
     * @access protected
     */
    protected static $fields = array (
        'autocomplete' => array (),
        'facets' => array (),
        'sortables' => array (),
        'indexed' => array (),
        'stored' => array (),
        'tokenized' => array (),
        'fieldboost' => array ()
    );

    /**
     * Is the index configuration loaded?
     * @see $fields
     *
     * @var	boolean
     * @access protected
     */
    protected static $fieldsLoaded = FALSE;

    /**
     * List of already processed documents
     *
     * @var	array
     * @access protected
     */
    protected static $processedDocs = array ();

    /**
     * Instance of tx_dlf_solr class
     *
     * @var	tx_dlf_solr
     * @access protected
     */
    protected static $solr;

    /**
     * Array of toplevel structure elements
     * @see loadIndexConf()
     *
     * @var	array
     * @access protected
     */
    protected static $toplevel = array ();

    /**
     * Insert given document into Solr index
     *
     * @access	public
     *
     * @param	tx_dlf_document		&$doc: The document to add
     * @param	integer		$core: UID of the Solr core to use
     *
     * @return	integer		0 on success or 1 on failure
     */
    public static function add(tx_dlf_document &$doc, $core = 0) {

        if (in_array($doc->uid, self::$processedDocs)) {

            return 0;

        } elseif (self::solrConnect($core, $doc->pid)) {

            $errors = 0;

            // Handle multi-volume documents.
            if ($doc->parentId) {

                $parent = & tx_dlf_document::getInstance($doc->parentId, 0, TRUE);

                if ($parent->ready) {

                    $errors = self::add($parent, $core);

                } else {

                    if (TYPO3_DLOG) {

                        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_indexing->add(['.$doc->uid.'], '.$core.')] Could not load parent document with UID "'.$doc->parentId.'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

                    }

                    return 1;

                }

            }

            try {

                // Add document to list of processed documents.
                self::$processedDocs[] = $doc->uid;

                // Delete old Solr documents.
                $updateQuery = self::$solr->service->createUpdate();
                $updateQuery->addDeleteQuery('uid:'.$doc->uid);
                self::$solr->service->update($updateQuery);

                // Index every logical unit as separate Solr document.
                foreach ($doc->tableOfContents as $logicalUnit) {

                    if (!$errors) {

                        $errors = self::processLogical($doc, $logicalUnit);

                    } else {

                        break;

                    }

                }

                // Index fulltext files if available.
                if ($doc->hasFulltext) {

                    foreach ($doc->physicalStructure as $pageNumber => $xmlId) {

                        if (!$errors) {

                            $errors = self::processPhysical($doc, $pageNumber, $doc->physicalStructureInfo[$xmlId]);

                        } else {

                            break;

                        }

                    }

                }

                $updateQuery = self::$solr->service->createUpdate();
                $updateQuery->addCommit();
                self::$solr->service->update($updateQuery);

                // Get document title from database.
                $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    'tx_dlf_documents.title AS title',
                    'tx_dlf_documents',
                    'tx_dlf_documents.uid='.intval($doc->uid).tx_dlf_helper::whereClause('tx_dlf_documents'),
                    '',
                    '',
                    '1'
                );

                $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

                if (!defined('TYPO3_cliMode')) {

                    if (!$errors) {

                        $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                            'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                            htmlspecialchars(sprintf(tx_dlf_helper::getLL('flash.documentIndexed'), $resArray['title'], $doc->uid)),
                            tx_dlf_helper::getLL('flash.done', TRUE),
                            \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                            TRUE
                        );

                    } else {

                        $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                            'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                            htmlspecialchars(sprintf(tx_dlf_helper::getLL('flash.documentNotIndexed'), $resArray['title'], $doc->uid)),
                            tx_dlf_helper::getLL('flash.error', TRUE),
                            \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                            TRUE
                        );

                    }

                    tx_dlf_helper::addMessage($message);

                }

                return $errors;

            } catch (Exception $e) {

                if (!defined('TYPO3_cliMode')) {

                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        tx_dlf_helper::getLL('flash.solrException', TRUE).'<br />'.htmlspecialchars($e->getMessage()),
                        tx_dlf_helper::getLL('flash.error', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                        TRUE
                    );

                    tx_dlf_helper::addMessage($message);

                }

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_indexing->add(['.$doc->uid.'], '.$core.')] Apache Solr threw exception: "'.$e->getMessage().'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

                }

                return 1;

            }

        } else {

            if (!defined('TYPO3_cliMode')) {

                $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    tx_dlf_helper::getLL('flash.solrNoConnection', TRUE),
                    tx_dlf_helper::getLL('flash.warning', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
                    TRUE
                );

                tx_dlf_helper::addMessage($message);

            }

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_indexing->add(['.$doc->uid.'], '.$core.')] Could not connect to Apache Solr server', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return 1;

        }

    }

    /**
     * Delete document from Solr index
     *
     * @access	public
     *
     * @param	integer		$uid: UID of the document to delete
     *
     * @return	integer		0 on success or 1 on failure
     */
    public static function delete($uid) {

        // Save parameter for logging purposes.
        $_uid = $uid;

        // Sanitize input.
        $uid = max(intval($uid), 0);

        // Get Solr core for document.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_solrcores.uid AS uid,tx_dlf_documents.title AS title',
            'tx_dlf_solrcores,tx_dlf_documents',
            'tx_dlf_solrcores.uid=tx_dlf_documents.solrcore AND tx_dlf_documents.uid='.$uid.tx_dlf_helper::whereClause('tx_dlf_solrcores'),
            '',
            '',
            '1'
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

            list ($core, $title) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

            // Establish Solr connection.
            if (self::solrConnect($core)) {

                try {

                    // Delete Solr document.
                    $updateQuery = self::$solr->service->createUpdate();
                    $updateQuery->addDeleteQuery('uid:'.$uid);
                    $updateQuery->addCommit();
                    self::$solr->service->update($updateQuery);

                } catch (Exception $e) {

                    if (!defined('TYPO3_cliMode')) {

                        $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                            'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                            tx_dlf_helper::getLL('flash.solrException', TRUE).'<br />'.htmlspecialchars($e->getMessage()),
                            tx_dlf_helper::getLL('flash.error', TRUE),
                            \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                            TRUE
                        );

                        tx_dlf_helper::addMessage($message);

                    }

                    if (TYPO3_DLOG) {

                        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_indexing->delete('.$_uid.')] Apache Solr threw exception: "'.$e->getMessage().'"', self::$extKey, SYSLOG_SEVERITY_ERROR);

                    }

                    return 1;

                }

            } else {

                if (!defined('TYPO3_cliMode')) {

                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        tx_dlf_helper::getLL('flash.solrNoConnection', TRUE),
                        tx_dlf_helper::getLL('flash.error', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                        TRUE
                    );

                    tx_dlf_helper::addMessage($message);

                }

                if (TYPO3_DLOG) {

                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_indexing->delete('.$_uid.')] Could not connect to Apache Solr server', self::$extKey, SYSLOG_SEVERITY_ERROR);

                }

                return 1;

            }

            if (!defined('TYPO3_cliMode')) {

                $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    htmlspecialchars(sprintf(tx_dlf_helper::getLL('flash.documentDeleted'), $title, $uid)),
                    tx_dlf_helper::getLL('flash.done', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                    TRUE
                );

                tx_dlf_helper::addMessage($message);

            }

            return 0;

        } else {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_indexing->delete('.$_uid.')] Invalid UID "'.$uid.'" for document deletion', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return 1;

        }

    }

    /**
     * Returns the dynamic index field name for the given metadata field.
     *
     * @access	public
     *
     * @param	string		$index_name: The metadata field's name in database
     * @param	integer		$pid: UID of the configuration page
     *
     * @return	string		The field's dynamic index name
     */
    public static function getIndexFieldName($index_name, $pid = 0) {

        // Save parameter for logging purposes.
        $_pid = $pid;

        // Sanitize input.
        $pid = max(intval($pid), 0);

        if (!$pid) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_indexing->getIndexFieldName('.$index_name.', '.$_pid.')] Invalid PID "'.$pid.'" for metadata configuration', self::$extKey, SYSLOG_SEVERITY_ERROR);

            }

            return '';

        }

        // Load metadata configuration.
        self::loadIndexConf($pid);

        // Build field's suffix.
        $suffix = (in_array($index_name, self::$fields['tokenized']) ? 't' : 'u');

        $suffix .= (in_array($index_name, self::$fields['stored']) ? 's' : 'u');

        $suffix .= (in_array($index_name, self::$fields['indexed']) ? 'i' : 'u');

        $index_name .= '_'.$suffix;

        return $index_name;

    }

    /**
     * Load indexing configuration
     *
     * @access	protected
     *
     * @param	integer		$pid: The configuration page's UID
     *
     * @return	void
     */
    protected static function loadIndexConf($pid) {

        if (!self::$fieldsLoaded) {

            // Get the list of toplevel structures.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_structures.index_name AS index_name',
                'tx_dlf_structures',
                'tx_dlf_structures.toplevel=1 AND tx_dlf_structures.pid='.intval($pid).tx_dlf_helper::whereClause('tx_dlf_structures'),
                '',
                '',
                ''
            );

            while ($toplevel = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

                self::$toplevel[] = $toplevel['index_name'];

            }

            // Get the metadata indexing options.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_metadata.index_name AS index_name,tx_dlf_metadata.index_tokenized AS index_tokenized,tx_dlf_metadata.index_stored AS index_stored,tx_dlf_metadata.index_indexed AS index_indexed,tx_dlf_metadata.is_sortable AS is_sortable,tx_dlf_metadata.is_facet AS is_facet,tx_dlf_metadata.is_listed AS is_listed,tx_dlf_metadata.index_autocomplete AS index_autocomplete,tx_dlf_metadata.index_boost AS index_boost',
                'tx_dlf_metadata',
                'tx_dlf_metadata.pid='.intval($pid).tx_dlf_helper::whereClause('tx_dlf_metadata'),
                '',
                '',
                ''
            );

            while ($indexing = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

                if ($indexing['index_tokenized']) {

                    self::$fields['tokenized'][] = $indexing['index_name'];

                }

                if ($indexing['index_stored'] || $indexing['is_listed']) {

                    self::$fields['stored'][] = $indexing['index_name'];

                }

                if ($indexing['index_indexed'] || $indexing['index_autocomplete']) {

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

                    self::$fields['fieldboost'][$indexing['index_name']] = FALSE;

                }

            }

            self::$fieldsLoaded = TRUE;

        }

    }

    /**
     * Processes a logical unit (and its children) for the Solr index
     *
     * @access	protected
     *
     * @param	tx_dlf_document		&$doc: The METS document
     * @param	array		$logicalUnit: Array of the logical unit to process
     *
     * @return	integer		0 on success or 1 on failure
     */
    protected static function processLogical(tx_dlf_document &$doc, array $logicalUnit) {

        $errors = 0;

        // Get metadata for logical unit.
        $metadata = $doc->metadataArray[$logicalUnit['id']];

        if (!empty($metadata)) {

            // Create new Solr document.
            $updateQuery = self::$solr->service->createUpdate();
            $solrDoc = $updateQuery->createDocument();

            // Create unique identifier from document's UID and unit's XML ID.
            $solrDoc->setField('id', $doc->uid.$logicalUnit['id']);

            $solrDoc->setField('uid', $doc->uid);

            $solrDoc->setField('pid', $doc->pid);

            if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($logicalUnit['points'])) {

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

            // There can be only one toplevel unit per uid, independently of backend configuration
            $solrDoc->setField('toplevel', $logicalUnit['id'] == $doc->toplevelId ? TRUE : FALSE);

            $solrDoc->setField('type', $logicalUnit['type'], self::$fields['fieldboost']['type']);

            $solrDoc->setField('title', $metadata['title'][0], self::$fields['fieldboost']['title']);

            $solrDoc->setField('volume', $metadata['volume'][0], self::$fields['fieldboost']['volume']);

            $solrDoc->setField('record_id', $metadata['record_id'][0]);

            $solrDoc->setField('purl', $metadata['purl'][0]);

            $solrDoc->setField('location', $doc->location);

            $solrDoc->setField('urn', $metadata['urn']);

            $solrDoc->setField('collection', $doc->metadataArray[$doc->toplevelId]['collection']);

            $autocomplete = array ();

            foreach ($metadata as $index_name => $data) {

                if (!empty($data) && substr($index_name, -8) !== '_sorting') {

                    $solrDoc->setField(self::getIndexFieldName($index_name, $doc->pid), $data, self::$fields['fieldboost'][$index_name]);

                    if (in_array($index_name, self::$fields['sortables'])) {

                        // Add sortable fields to index.
                        $solrDoc->setField($index_name.'_sorting', $metadata[$index_name.'_sorting'][0]);

                    }

                    if (in_array($index_name, self::$fields['facets'])) {

                        // Add facets to index.
                        $solrDoc->setField($index_name.'_faceting', $data);

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
            if (in_array('collection', self::$fields['facets'])
                && empty($metadata['collection'])
                && !empty($doc->metadataArray[$doc->toplevelId]['collection'])) {

                $solrDoc->setField('collection_faceting', $doc->metadataArray[$doc->toplevelId]['collection']);

            }

            try {

                $updateQuery->addDocument($solrDoc);
                $updateQuery->addCommit();
                self::$solr->service->update($updateQuery);

            } catch (Exception $e) {

                if (!defined('TYPO3_cliMode')) {

                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        tx_dlf_helper::getLL('flash.solrException', TRUE).'<br />'.htmlspecialchars($e->getMessage()),
                        tx_dlf_helper::getLL('flash.error', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                        TRUE
                    );

                    tx_dlf_helper::addMessage($message);

                }

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
     * @access	protected
     *
     * @param	tx_dlf_document		&$doc: The METS document
     * @param	integer		$page: The page number
     * @param	array		$physicalUnit: Array of the physical unit to process
     *
     * @return	integer		0 on success or 1 on failure
     */
    protected static function processPhysical(tx_dlf_document &$doc, $page, array $physicalUnit) {

        $errors = 0;

        // Read extension configuration.
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::$extKey]);

        if (!empty($physicalUnit['files'][$extConf['fileGrpFulltext']])) {

            $file = $doc->getFileLocation($physicalUnit['files'][$extConf['fileGrpFulltext']]);

            // Load XML file.
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($file)) {

                // Set user-agent to identify self when fetching XML data.
                if (!empty($extConf['useragent'])) {

                    @ini_set('user_agent', $extConf['useragent']);

                }

                // Turn off libxml's error logging.
                $libxmlErrors = libxml_use_internal_errors(TRUE);

                // disable entity loading
                $previousValueOfEntityLoader = libxml_disable_entity_loader(TRUE);

                // Load XML from file.
                $xml = simplexml_load_string(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file));

                // reset entity loader setting
                libxml_disable_entity_loader($previousValueOfEntityLoader);

                // Reset libxml's error logging.
                libxml_use_internal_errors($libxmlErrors);

                if ($xml === FALSE) {

                    return 1;

                }

            } else {

                return 1;

            }

            // Create new Solr document.
            $updateQuery = self::$solr->service->createUpdate();
            $solrDoc = $updateQuery->createDocument();

            // Create unique identifier from document's UID and unit's XML ID.
            $solrDoc->setField('id', $doc->uid.$physicalUnit['id']);

            $solrDoc->setField('uid', $doc->uid);

            $solrDoc->setField('pid', $doc->pid);

            $solrDoc->setField('page', $page);

            if (!empty($physicalUnit['files'][$extConf['fileGrpThumbs']])) {

                $solrDoc->setField('thumbnail', $doc->getFileLocation($physicalUnit['files'][$extConf['fileGrpThumbs']]));

            }

            $solrDoc->setField('partof', $doc->parentId);

            $solrDoc->setField('root', $doc->rootId);

            $solrDoc->setField('sid', $physicalUnit['id']);

            $solrDoc->setField('toplevel', FALSE);

            $solrDoc->setField('type', $physicalUnit['type'], self::$fields['fieldboost']['type']);

            $solrDoc->setField('collection', $doc->metadataArray[$doc->toplevelId]['collection']);

            $solrDoc->setField('fulltext', $doc->getRawText($physicalUnit['id']));

            // Add faceting information to physical sub-elements if applicable.
            foreach ($doc->metadataArray[$doc->toplevelId] as $index_name => $data) {

                if (!empty($data) && substr($index_name, -8) !== '_sorting') {

                    if (in_array($index_name, self::$fields['facets'])) {

                        // Add facets to index.
                        $solrDoc->setField($index_name.'_faceting', $data);

                    }

                }

            }

            // Add collection information to physical sub-elements if applicable.
            if (in_array('collection', self::$fields['facets'])
                && !empty($doc->metadataArray[$doc->toplevelId]['collection'])) {

                $solrDoc->setField('collection_faceting', $doc->metadataArray[$doc->toplevelId]['collection']);

            }

            try {

                $updateQuery->addDocument($solrDoc);
                $updateQuery->addCommit();
                self::$solr->service->update($updateQuery);

            } catch (Exception $e) {

                if (!defined('TYPO3_cliMode')) {

                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        tx_dlf_helper::getLL('flash.solrException', TRUE).'<br />'.htmlspecialchars($e->getMessage()),
                        tx_dlf_helper::getLL('flash.error', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                        TRUE
                    );

                    tx_dlf_helper::addMessage($message);

                }

                return 1;

            }

        }

        return $errors;

    }

    /**
     * Connects to Solr server.
     *
     * @access	protected
     *
     * @param	integer		$core: UID of the Solr core
     * @param	integer		$pid: UID of the configuration page
     *
     * @return	boolean		TRUE on success or FALSE on failure
     */
    protected static function solrConnect($core, $pid = 0) {

        // Get Solr instance.
        if (!self::$solr) {

            // Connect to Solr server.
            if (self::$solr = tx_dlf_solr::getInstance($core)) {

                // Load indexing configuration if needed.
                if ($pid) {

                    self::loadIndexConf($pid);

                }

            } else {

                return FALSE;

            }

        }

        return TRUE;

    }

    /**
     * This is a static class, thus no instances should be created
     *
     * @access private
     */
    private function __construct() {}

}
