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
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Helper class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Helper
{
    /**
     * The extension key
     *
     * @var string
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * This holds the cipher algorithm
     * @see openssl_get_cipher_methods() for options
     *
     * @var string
     * @access protected
     */
    protected static $cipherAlgorithm = 'aes-256-ctr';

    /**
     * This holds the hash algorithm
     * @see openssl_get_md_methods() for options
     *
     * @var string
     * @access protected
     */
    protected static $hashAlgorithm = 'sha256';

    /**
     * The locallang array for flash messages
     *
     * @var array
     * @access protected
     */
    protected static $messages = [];

    /**
     * Generates a flash message and adds it to a message queue.
     *
     * @access public
     *
     * @param string $message: The body of the message
     * @param string $title: The title of the message
     * @param int $severity: The message's severity
     * @param bool $session: Should the message be saved in the user's session?
     * @param string $queue: The queue's unique identifier
     *
     * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue The queue the message was added to
     */
    public static function addMessage($message, $title, $severity, $session = false, $queue = 'kitodo.default.flashMessages')
    {
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier($queue);
        $flashMessage = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\FlashMessage::class,
            $message,
            $title,
            $severity,
            $session
        );
        $flashMessageQueue->enqueue($flashMessage);
        return $flashMessageQueue;
    }

    /**
     * Check if given identifier is a valid identifier of the German National Library
     *
     * @access public
     *
     * @param string $id: The identifier to check
     * @param string $type: What type is the identifier supposed to be?
     *                      Possible values: PPN, IDN, PND, ZDB, SWD, GKD
     *
     * @return bool Is $id a valid GNL identifier of the given $type?
     */
    public static function checkIdentifier($id, $type)
    {
        $digits = substr($id, 0, 8);
        $checksum = 0;
        for ($i = 0, $j = strlen($digits); $i < $j; $i++) {
            $checksum += (9 - $i) * intval(substr($digits, $i, 1));
        }
        $checksum = (11 - ($checksum % 11)) % 11;
        switch (strtoupper($type)) {
            case 'PPN':
            case 'IDN':
            case 'PND':
                if ($checksum == 10) {
                    $checksum = 'X';
                }
                if (!preg_match('/\d{8}[\dX]{1}/i', $id)) {
                    return false;
                } elseif (strtoupper(substr($id, -1, 1)) != $checksum) {
                    return false;
                }
                break;
            case 'ZDB':
                if ($checksum == 10) {
                    $checksum = 'X';
                }
                if (!preg_match('/\d{8}-[\dX]{1}/i', $id)) {
                    return false;
                } elseif (strtoupper(substr($id, -1, 1)) != $checksum) {
                    return false;
                }
                break;
            case 'SWD':
                $checksum = 11 - $checksum;
                if (!preg_match('/\d{8}-\d{1}/i', $id)) {
                    return false;
                } elseif ($checksum == 10) {
                    return self::checkIdentifier(($digits + 1) . substr($id, -2, 2), 'SWD');
                } elseif (substr($id, -1, 1) != $checksum) {
                    return false;
                }
                break;
            case 'GKD':
                $checksum = 11 - $checksum;
                if ($checksum == 10) {
                    $checksum = 'X';
                }
                if (!preg_match('/\d{8}-[\dX]{1}/i', $id)) {
                    return false;
                } elseif (strtoupper(substr($id, -1, 1)) != $checksum) {
                    return false;
                }
                break;
        }
        return true;
    }

    /**
     * Decrypt encrypted value with given control hash
     *
     * @access public
     *
     * @param string $encrypted: The encrypted value to decrypt
     *
     * @return mixed The decrypted value or false on error
     */
    public static function decrypt($encrypted)
    {
        if (
            !in_array(self::$cipherAlgorithm, openssl_get_cipher_methods(true))
            || !in_array(self::$hashAlgorithm, openssl_get_md_methods(true))
        ) {
            self::log('OpenSSL library doesn\'t support cipher and/or hash algorithm', LOG_SEVERITY_ERROR);
            return false;
        }
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            self::log('No encryption key set in TYPO3 configuration', LOG_SEVERITY_ERROR);
            return false;
        }
        if (
            empty($encrypted)
            || strlen($encrypted) < openssl_cipher_iv_length(self::$cipherAlgorithm)
        ) {
            self::log('Invalid parameters given for decryption', LOG_SEVERITY_ERROR);
            return false;
        }
        // Split initialisation vector and encrypted data.
        $binary = base64_decode($encrypted);
        $iv = substr($binary, 0, openssl_cipher_iv_length(self::$cipherAlgorithm));
        $data = substr($binary, openssl_cipher_iv_length(self::$cipherAlgorithm));
        $key = openssl_digest($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], self::$hashAlgorithm, true);
        // Decrypt data.
        $decrypted = openssl_decrypt($data, self::$cipherAlgorithm, $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

    /**
     * Try to parse $content into a `SimpleXmlElement`. If $content is not a
     * string or does not contain valid XML, `false` is returned.
     *
     * @access public
     *
     * @param string $content: content of file to read
     *
     * @return \SimpleXMLElement|false
     */
    public static function getXmlFileAsString($content)
    {
        // Don't make simplexml_load_string throw (when $content is an array
        // or object)
        if (!is_string($content)) {
            return false;
        }

        // Turn off libxml's error logging.
        $libxmlErrors = libxml_use_internal_errors(true);
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        // Try to load XML from file.
        $xml = simplexml_load_string($content);
        // reset entity loader setting
        libxml_disable_entity_loader($previousValueOfEntityLoader);
        // Reset libxml's error logging.
        libxml_use_internal_errors($libxmlErrors);
        return $xml;
    }

    /**
     * Add a message to the TYPO3 log
     *
     * @access public
     *
     * @param string $message: The message to log
     * @param int $severity: The severity of the message
     *                       0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     *
     * @return void
     */
    public static function log($message, $severity = 0)
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(get_called_class());

        switch ($severity) {
            case 0:
                $logger->info($message);
                break;
            case 1:
                $logger->notice($message);
                break;
            case 2:
                $logger->warning($message);
                break;
            case 3:
                $logger->error($message);
                break;
            default:
                break;
        }
    }

    /**
     * Digest the given string
     *
     * @access public
     *
     * @param string $string: The string to encrypt
     *
     * @return mixed Hashed string or false on error
     */
    public static function digest($string)
    {
        if (!in_array(self::$hashAlgorithm, openssl_get_md_methods(true))) {
            self::log('OpenSSL library doesn\'t support hash algorithm', LOG_SEVERITY_ERROR);
            return false;
        }
        // Hash string.
        $hashed = openssl_digest($string, self::$hashAlgorithm);
        return $hashed;
    }

    /**
     * Encrypt the given string
     *
     * @access public
     *
     * @param string $string: The string to encrypt
     *
     * @return mixed Encrypted string or false on error
     */
    public static function encrypt($string)
    {
        if (
            !in_array(self::$cipherAlgorithm, openssl_get_cipher_methods(true))
            || !in_array(self::$hashAlgorithm, openssl_get_md_methods(true))
        ) {
            self::log('OpenSSL library doesn\'t support cipher and/or hash algorithm', LOG_SEVERITY_ERROR);
            return false;
        }
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            self::log('No encryption key set in TYPO3 configuration', LOG_SEVERITY_ERROR);
            return false;
        }
        // Generate random initialisation vector.
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipherAlgorithm));
        $key = openssl_digest($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], self::$hashAlgorithm, true);
        // Encrypt data.
        $encrypted = openssl_encrypt($string, self::$cipherAlgorithm, $key, OPENSSL_RAW_DATA, $iv);
        // Merge initialisation vector and encrypted data.
        if ($encrypted !== false) {
            $encrypted = base64_encode($iv . $encrypted);
        }
        return $encrypted;
    }

    /**
     * Get the unqualified name of a class
     *
     * @access public
     *
     * @param string $qualifiedClassname: The qualified class name from get_class()
     *
     * @return string The unqualified class name
     */
    public static function getUnqualifiedClassName($qualifiedClassname)
    {
        $nameParts = explode('\\', $qualifiedClassname);
        return end($nameParts);
    }

    /**
     * Clean up a string to use in an URL.
     *
     * @access public
     *
     * @param string $string: The string to clean up
     *
     * @return string The cleaned up string
     */
    public static function getCleanString($string)
    {
        // Convert to lowercase.
        $string = strtolower($string);
        // Remove non-alphanumeric characters.
        $string = preg_replace('/[^a-z\d_\s-]/', '', $string);
        // Remove multiple dashes or whitespaces.
        $string = preg_replace('/[\s-]+/', ' ', $string);
        // Convert whitespaces and underscore to dash.
        $string = preg_replace('/[\s_]/', '-', $string);
        return $string;
    }

    /**
     * Get the registered hook objects for a class
     *
     * @access public
     *
     * @param string $scriptRelPath: The path to the class file
     *
     * @return array Array of hook objects for the class
     */
    public static function getHookObjects($scriptRelPath)
    {
        $hookObjects = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::$extKey . '/' . $scriptRelPath]['hookClass'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::$extKey . '/' . $scriptRelPath]['hookClass'] as $classRef) {
                $hookObjects[] = GeneralUtility::makeInstance($classRef);
            }
        }
        return $hookObjects;
    }

    /**
     * Get the "index_name" for an UID
     *
     * @access public
     *
     * @param int $uid: The UID of the record
     * @param string $table: Get the "index_name" from this table
     * @param int $pid: Get the "index_name" from this page
     *
     * @return string "index_name" for the given UID
     */
    public static function getIndexNameFromUid($uid, $table, $pid = -1)
    {
        // Sanitize input.
        $uid = max(intval($uid), 0);
        if (
            !$uid
            // NOTE: Only use tables that don't have too many entries!
            || !in_array($table, ['tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures', 'tx_dlf_solrcores'])
        ) {
            self::log('Invalid UID "' . $uid . '" or table "' . $table . '"', LOG_SEVERITY_ERROR);
            return '';
        }

        $makeCacheKey = function ($pid, $uid) {
            return $pid . '.' . $uid;
        };

        static $cache = [];
        if (!isset($cache[$table])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);

            $result = $queryBuilder
                ->select(
                    $table . '.index_name AS index_name',
                    $table . '.uid AS uid',
                    $table . '.pid AS pid',
                )
                ->from($table)
                ->execute();

            $cache[$table] = [];

            while ($row = $result->fetch()) {
                $cache[$table][$makeCacheKey($row['pid'], $row['uid'])]
                    = $cache[$table][$makeCacheKey(-1, $row['uid'])]
                    = $row['index_name'];
            }
        }

        $cacheKey = $makeCacheKey($pid, $uid);
        $result = $cache[$table][$cacheKey] ?? '';

        if ($result === '') {
            self::log('No "index_name" with UID ' . $uid . ' and PID ' . $pid . ' found in table "' . $table . '"', LOG_SEVERITY_WARNING);
        }

        return $result;
    }

    /**
     * Get language name from ISO code
     *
     * @access public
     *
     * @param string $code: ISO 639-1 or ISO 639-2/B language code
     *
     * @return string Localized full name of language or unchanged input
     */
    public static function getLanguageName($code)
    {
        // Analyze code and set appropriate ISO table.
        $isoCode = strtolower(trim($code));
        if (preg_match('/^[a-z]{3}$/', $isoCode)) {
            $file = 'EXT:dlf/Resources/Private/Data/iso-639-2b.xml';
        } elseif (preg_match('/^[a-z]{2}$/', $isoCode)) {
            $file = 'EXT:dlf/Resources/Private/Data/iso-639-1.xml';
        } else {
            // No ISO code, return unchanged.
            return $code;
        }
        $lang = LocalizationUtility::translate('LLL:' . $file . ':' . $code);
        if (!empty($lang)) {
            return $lang;
        } else {
            self::log('Language code "' . $code . '" not found in ISO-639 table', LOG_SEVERITY_NOTICE);
            return $code;
        }
    }

    /**
     * Get all document structures as array
     *
     * @param int $pid: Get the "index_name" from this page only
     *
     * @return array
     */
    public static function getDocumentStructures($pid = -1)
    {
        // TODO: Against redundancy with getIndexNameFromUid

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_dlf_structures');

        $where = '';
        // Should we check for a specific PID, too?
        if ($pid !== -1) {
            $pid = max(intval($pid), 0);
            $where = $queryBuilder->expr()->eq('tx_dlf_structures.pid', $pid);
        }

        // Fetch document info for UIDs in $documentSet from DB
        $kitodoStructures = $queryBuilder
            ->select(
                'tx_dlf_structures.uid AS uid',
                'tx_dlf_structures.index_name AS indexName'
            )
            ->from('tx_dlf_structures')
            ->where($where)
            ->execute();

        $allStructures = $kitodoStructures->fetchAll();

        // make lookup-table indexName -> uid
        $allStructures = array_column($allStructures, 'indexName', 'uid');

        return $allStructures;
    }

    /**
     * Get the URN of an object
     * @see http://www.persistent-identifier.de/?link=316
     *
     * @access public
     *
     * @param string $base: The namespace and base URN
     * @param string $id: The object's identifier
     *
     * @return string Uniform Resource Name as string
     */
    public static function getURN($base, $id)
    {
        $concordance = [
            '0' => 1,
            '1' => 2,
            '2' => 3,
            '3' => 4,
            '4' => 5,
            '5' => 6,
            '6' => 7,
            '7' => 8,
            '8' => 9,
            '9' => 41,
            'a' => 18,
            'b' => 14,
            'c' => 19,
            'd' => 15,
            'e' => 16,
            'f' => 21,
            'g' => 22,
            'h' => 23,
            'i' => 24,
            'j' => 25,
            'k' => 42,
            'l' => 26,
            'm' => 27,
            'n' => 13,
            'o' => 28,
            'p' => 29,
            'q' => 31,
            'r' => 12,
            's' => 32,
            't' => 33,
            'u' => 11,
            'v' => 34,
            'w' => 35,
            'x' => 36,
            'y' => 37,
            'z' => 38,
            '-' => 39,
            ':' => 17,
        ];
        $urn = strtolower($base . $id);
        if (preg_match('/[^a-z\d:-]/', $urn)) {
            self::log('Invalid chars in given parameters', LOG_SEVERITY_WARNING);
            return '';
        }
        $digits = '';
        for ($i = 0, $j = strlen($urn); $i < $j; $i++) {
            $digits .= $concordance[substr($urn, $i, 1)];
        }
        $checksum = 0;
        for ($i = 0, $j = strlen($digits); $i < $j; $i++) {
            $checksum += ($i + 1) * intval(substr($digits, $i, 1));
        }
        $checksum = substr(intval($checksum / intval(substr($digits, -1, 1))), -1, 1);
        return $base . $id . $checksum;
    }

    /**
     * Check if given ID is a valid Pica Production Number (PPN)
     *
     * @access public
     *
     * @param string $id: The identifier to check
     *
     * @return bool Is $id a valid PPN?
     */
    public static function isPPN($id)
    {
        return self::checkIdentifier($id, 'PPN');
    }

    /**
     * Determine whether or not $url is a valid URL using HTTP or HTTPS scheme.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isValidHttpUrl($url)
    {
        if (!GeneralUtility::isValidUrl($url)) {
            return false;
        }

        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? '';
        $schemeNormalized = strtolower($scheme);

        return $schemeNormalized === 'http' || $schemeNormalized === 'https';
    }

    /**
     * Merges two arrays recursively and actually returns the modified array.
     * @see \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule()
     *
     * @access public
     *
     * @param array $original: Original array
     * @param array $overrule: Overrule array, overruling the original array
     * @param bool $addKeys: If set to false, keys that are not found in $original will not be set
     * @param bool $includeEmptyValues: If set, values from $overrule will overrule if they are empty
     * @param bool $enableUnsetFeature: If set, special value "__UNSET" can be used in the overrule array to unset keys in the original array
     *
     * @return array Merged array
     */
    public static function mergeRecursiveWithOverrule(array $original, array $overrule, $addKeys = true, $includeEmptyValues = true, $enableUnsetFeature = true)
    {
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($original, $overrule, $addKeys, $includeEmptyValues, $enableUnsetFeature);
        return $original;
    }

    /**
     * Fetches and renders all available flash messages from the queue.
     *
     * @access public
     *
     * @param string $queue: The queue's unique identifier
     *
     * @return string All flash messages in the queue rendered as HTML.
     */
    public static function renderFlashMessages($queue = 'kitodo.default.flashMessages')
    {
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier($queue);
        $flashMessages = $flashMessageQueue->getAllMessagesAndFlush();
        $content = GeneralUtility::makeInstance(\Kitodo\Dlf\Common\KitodoFlashMessageRenderer::class)
            ->render($flashMessages);
        return $content;
    }

    /**
     * This translates an internal "index_name"
     *
     * @access public
     *
     * @param string $index_name: The internal "index_name" to translate
     * @param string $table: Get the translation from this table
     * @param string $pid: Get the translation from this page
     *
     * @return string Localized label for $index_name
     */
    public static function translate($index_name, $table, $pid)
    {
        // Load labels into static variable for future use.
        static $labels = [];
        // Sanitize input.
        $pid = max(intval($pid), 0);
        if (!$pid) {
            self::log('Invalid PID ' . $pid . ' for translation', LOG_SEVERITY_WARNING);
            return $index_name;
        }
        /** @var \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);

        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');

        // Check if "index_name" is an UID.
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($index_name)) {
            $index_name = self::getIndexNameFromUid($index_name, $table, $pid);
        }
        /* $labels already contains the translated content element, but with the index_name of the translated content element itself
         * and not with the $index_name of the original that we receive here. So we have to determine the index_name of the
         * associated translated content element. E.g. $labels['title0'] != $index_name = title. */

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        // First fetch the uid of the received index_name
        $result = $queryBuilder
            ->select(
                $table . '.uid AS uid',
                $table . '.l18n_parent AS l18n_parent'
            )
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($table . '.pid', $pid),
                $queryBuilder->expr()->eq($table . '.index_name', $queryBuilder->expr()->literal($index_name)),
                self::whereExpression($table, true)
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $result->fetchAll();

        if (count($allResults) == 1) {
            // Now we use the uid of the l18_parent to fetch the index_name of the translated content element.
            $resArray = $allResults[0];

            $result = $queryBuilder
                ->select($table . '.index_name AS index_name')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq($table . '.pid', $pid),
                    $queryBuilder->expr()->eq($table . '.uid', $resArray['l18n_parent']),
                    $queryBuilder->expr()->eq($table . '.sys_language_uid', intval($languageAspect->getContentId())),
                    self::whereExpression($table, true)
                )
                ->setMaxResults(1)
                ->execute();

            $allResults = $result->fetchAll();

            if (count($allResults) == 1) {
                // If there is an translated content element, overwrite the received $index_name.
                $index_name = $allResults[0]['index_name'];
            }
        }

        // Check if we already got a translation.
        if (empty($labels[$table][$pid][$languageAspect->getContentId()][$index_name])) {
            // Check if this table is allowed for translation.
            if (in_array($table, ['tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures'])) {
                $additionalWhere = $queryBuilder->expr()->in($table . '.sys_language_uid', [-1, 0]);
                if ($languageAspect->getContentId() > 0) {
                    $additionalWhere = $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->in($table . '.sys_language_uid', [-1, 0]),
                            $queryBuilder->expr()->eq($table . '.sys_language_uid', intval($languageAspect->getContentId()))
                        ),
                        $queryBuilder->expr()->eq($table . '.l18n_parent', 0)
                    );
                }

                // Get labels from database.
                $result = $queryBuilder
                    ->select('*')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->eq($table . '.pid', $pid),
                        $additionalWhere,
                        self::whereExpression($table, true)
                    )
                    ->setMaxResults(10000)
                    ->execute();

                if ($result->rowCount() > 0) {
                    while ($resArray = $result->fetch()) {
                        // Overlay localized labels if available.
                        if ($languageAspect->getContentId() > 0) {
                            $resArray = $pageRepository->getRecordOverlay($table, $resArray, $languageAspect->getContentId(), $languageAspect->getLegacyOverlayType());
                        }
                        if ($resArray) {
                            $labels[$table][$pid][$languageAspect->getContentId()][$resArray['index_name']] = $resArray['label'];
                        }
                    }
                } else {
                    self::log('No translation with PID ' . $pid . ' available in table "' . $table . '" or translation not accessible', LOG_SEVERITY_NOTICE);
                }
            } else {
                self::log('No translations available for table "' . $table . '"', LOG_SEVERITY_WARNING);
            }
        }

        if (!empty($labels[$table][$pid][$languageAspect->getContentId()][$index_name])) {
            return $labels[$table][$pid][$languageAspect->getContentId()][$index_name];
        } else {
            return $index_name;
        }
    }

    /**
     * This returns the additional WHERE expression of a table based on its TCA configuration
     *
     * @access public
     *
     * @param string $table: Table name as defined in TCA
     * @param bool $showHidden: Ignore the hidden flag?
     *
     * @return string Additional WHERE expression
     */
    public static function whereExpression($table, $showHidden = false)
    {
        if (\TYPO3_MODE === 'FE') {
            // Should we ignore the record's hidden flag?
            $ignoreHide = 0;
            if ($showHidden) {
                $ignoreHide = 1;
            }
            /** @var \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository */
            $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);

            $expression = $pageRepository->enableFields($table, $ignoreHide);
            if (!empty($expression)) {
                return substr($expression, 5);
            } else {
                return '';
            }
        } elseif (\TYPO3_MODE === 'BE') {
            return GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table)
                ->expr()
                ->eq($table . '.' . $GLOBALS['TCA'][$table]['ctrl']['delete'], 0);
        } else {
            self::log('Unexpected TYPO3_MODE "' . \TYPO3_MODE . '"', LOG_SEVERITY_ERROR);
            return '1=-1';
        }
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

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    public static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Make classname configuration from `Classes.php` available in contexts
     * where it normally isn't, and where the classical way via TypoScript won't
     * work either.
     *
     * This transforms the structure used in `Classes.php` to that used in
     * `ext_typoscript_setup.txt`. See commit 5e6110fb for a similar approach.
     *
     * @deprecated Remove once we drop support for TYPO3v9
     *
     * @access public
     */
    public static function polyfillExtbaseClassesForTYPO3v9()
    {
        $classes = require __DIR__ . '/../../Configuration/Extbase/Persistence/Classes.php';

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $frameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $extbaseClassmap = &$frameworkConfiguration['persistence']['classes'];
        if ($extbaseClassmap === null) {
            $extbaseClassmap = [];
        }

        foreach ($classes as $className => $classConfig) {
            $extbaseClass = &$extbaseClassmap[$className];
            if ($extbaseClass === null) {
                $extbaseClass = [];
            }
            if (!isset($extbaseClass['mapping'])) {
                $extbaseClass['mapping'] = [];
            }
            $extbaseClass['mapping']['tableName'] = $classConfig['tableName'];
        }

        $configurationManager->setConfiguration($frameworkConfiguration);
    }

    /**
     * Replacement for the TYPO3 GeneralUtility::getUrl().
     *
     * This method respects the User Agent settings from extConf
     *
     * @access public
     *
     * @param string $url
     *
     * @return string|bool
     */
    public static function getUrl(string $url)
    {
        if (!Helper::isValidHttpUrl($url)) {
            return false;
        }

        // Get extension configuration.
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        /** @var RequestFactory $requestFactory */
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $configuration = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => $extConf['useragent'] ?? 'Kitodo.Presentation Proxy',
            ],
        ];
        try {
            $response = $requestFactory->request($url, 'GET', $configuration);
        } catch (\Exception $e) {
            self::log('Could not fetch data from URL "' . $url . '". Error: ' . $e->getMessage() . '.', LOG_SEVERITY_WARNING);
            return false;
        }
        $content  = $response->getBody()->getContents();

        return $content;
    }

    /**
     * Check if given value is a valid XML ID.
     * @see https://www.w3.org/TR/xmlschema-2/#ID
     *
     * @access public
     *
     * @param mixed $id: The ID value to check
     *
     * @return bool: TRUE if $id is valid XML ID, FALSE otherwise
     */
    public static function isValidXmlId($id): bool
    {
        return preg_match('/^[_a-z][_a-z0-9-.]*$/i', $id) === 1;
    }
}
