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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier($queue);
        $flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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
                if (!preg_match('/[0-9]{8}[0-9X]{1}/i', $id)) {
                    return false;
                } elseif (strtoupper(substr($id, -1, 1)) != $checksum) {
                    return false;
                }
                break;
            case 'ZDB':
                if ($checksum == 10) {
                    $checksum = 'X';
                }
                if (!preg_match('/[0-9]{8}-[0-9X]{1}/i', $id)) {
                    return false;
                } elseif (strtoupper(substr($id, -1, 1)) != $checksum) {
                    return false;
                }
                break;
            case 'SWD':
                $checksum = 11 - $checksum;
                if (!preg_match('/[0-9]{8}-[0-9]{1}/i', $id)) {
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
                if (!preg_match('/[0-9]{8}-[0-9X]{1}/i', $id)) {
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
            self::devLog('OpenSSL library doesn\'t support cipher and/or hash algorithm', DEVLOG_SEVERITY_ERROR);
            return false;
        }
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            self::devLog('No encryption key set in TYPO3 configuration', DEVLOG_SEVERITY_ERROR);
            return false;
        }
        if (
            empty($encrypted)
            || strlen($encrypted) < openssl_cipher_iv_length(self::$cipherAlgorithm)
        ) {
            self::devLog('Invalid parameters given for decryption', DEVLOG_SEVERITY_ERROR);
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
     * Add a message to the TYPO3 developer log
     *
     * @access public
     *
     * @param string $message: The message to log
     * @param int $severity: The severity of the message
     *                       0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     *
     * @return void
     */
    public static function devLog($message, $severity = 0)
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'])) {
            $stacktrace = debug_backtrace(0, 2);
            // Set some defaults.
            $caller = 'Kitodo\Dlf\Default\UnknownClass::unknownMethod';
            $args = [];
            $data = [];
            if (!empty($stacktrace[1])) {
                $caller = $stacktrace[1]['class'] . $stacktrace[1]['type'] . $stacktrace[1]['function'];
                foreach ($stacktrace[1]['args'] as $arg) {
                    if (is_bool($arg)) {
                        $args[] = ($arg ? 'true' : 'false');
                    } elseif (is_scalar($arg)) {
                        $args[] = (string) $arg;
                    } elseif (is_null($arg)) {
                        $args[] = 'null';
                    } elseif (is_array($arg)) {
                        $args[] = '[data]';
                        $data[] = $arg;
                    } elseif (is_object($arg)) {
                        $args[] = '[' . get_class($arg) . ']';
                        $data[] = $arg;
                    }
                }
            }
            $arguments = '(' . implode(', ', $args) . ')';
            $additionalData = (empty($data) ? false : $data);
            /** @scrutinizer ignore-deprecated */ \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[' . $caller . $arguments . '] ' . $message, self::$extKey, $severity, $additionalData);
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
            self::devLog('OpenSSL library doesn\'t support hash algorithm', DEVLOG_SEVERITY_ERROR);
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
            self::devLog('OpenSSL library doesn\'t support cipher and/or hash algorithm', DEVLOG_SEVERITY_ERROR);
            return false;
        }
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            self::devLog('No encryption key set in TYPO3 configuration', DEVLOG_SEVERITY_ERROR);
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
        $string = preg_replace('/[^a-z0-9_\s-]/', '', $string);
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
                $hookObjects[] = &\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classRef);
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
            || !in_array($table, ['tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures', 'tx_dlf_solrcores'])
        ) {
            self::devLog('Invalid UID "' . $uid . '" or table "' . $table . '"', DEVLOG_SEVERITY_ERROR);
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $where = '';
        // Should we check for a specific PID, too?
        if ($pid !== -1) {
            $pid = max(intval($pid), 0);
            $where = $queryBuilder->expr()->eq($table . '.pid', $pid);
        }

        // Get index_name from database.
        $result = $queryBuilder
            ->select($table . '.index_name AS index_name')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($table . '.uid', $uid),
                $where,
                self::whereExpression($table)
            )
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
            return $resArray['index_name'];
        } else {
            self::devLog('No "index_name" with UID ' . $uid . ' and PID ' . $pid . ' found in table "' . $table . '"', DEVLOG_SEVERITY_WARNING);
            return '';
        }
    }

    /**
     * Get the "label" for an UID
     *
     * @access public
     *
     * @param int $uid: The UID of the record
     * @param string $table: Get the "label" from this table
     * @param int $pid: Get the "label" from this page
     *
     * @return string "label" for the given UID
     */
    public static function getLabelFromUid($uid, $table, $pid = -1)
    {
        // Sanitize input.
        $uid = max(intval($uid), 0);
        if (
            !$uid
            || !in_array($table, ['tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures', 'tx_dlf_solrcores'])
        ) {
            self::devLog('Invalid UID "' . $uid . '" or table "' . $table . '"', DEVLOG_SEVERITY_ERROR);
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $where = '';
        // Should we check for a specific PID, too?
        if ($pid !== -1) {
            $pid = max(intval($pid), 0);
            $where = $queryBuilder->expr()->eq($table . '.pid', $pid);
        }

        // Get label from database.
        $result = $queryBuilder
            ->select($table . '.label AS label')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($table . '.uid', $uid),
                $where,
                self::whereExpression($table)
            )
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
            return $resArray['label'];
        } else {
            self::devLog('No "label" with UID ' . $uid . ' and PID ' . $pid . ' found in table "' . $table . '"', DEVLOG_SEVERITY_WARNING);
            return '';
        }
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
            $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(self::$extKey) . 'Resources/Private/Data/iso-639-2b.xml';
        } elseif (preg_match('/^[a-z]{2}$/', $isoCode)) {
            $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(self::$extKey) . 'Resources/Private/Data/iso-639-1.xml';
        } else {
            // No ISO code, return unchanged.
            return $code;
        }
        // Load ISO table and get localized full name of language.
        if (\TYPO3_MODE === 'FE') {
            $iso639 = $GLOBALS['TSFE']->readLLfile($file);
            if (!empty($iso639['default'][$isoCode])) {
                $lang = $GLOBALS['TSFE']->getLLL($isoCode, $iso639);
            }
        } elseif (\TYPO3_MODE === 'BE') {
            $iso639 = $GLOBALS['LANG']->includeLLFile($file, false, true);
            if (!empty($iso639['default'][$isoCode])) {
                $lang = $GLOBALS['LANG']->getLLL($isoCode, $iso639);
            }
        } else {
            self::devLog('Unexpected TYPO3_MODE "' . \TYPO3_MODE . '"', DEVLOG_SEVERITY_ERROR);
            return $code;
        }
        if (!empty($lang)) {
            return $lang;
        } else {
            self::devLog('Language code "' . $code . '" not found in ISO-639 table', DEVLOG_SEVERITY_NOTICE);
            return $code;
        }
    }

    /**
     * Wrapper function for getting localized messages in frontend and backend
     *
     * @access public
     *
     * @param string $key: The locallang key to translate
     * @param bool $hsc: Should the result be htmlspecialchar()'ed?
     * @param string $default: Default return value if no translation is available
     *
     * @return string The translated string or the given key on failure
     */
    public static function getMessage($key, $hsc = false, $default = '')
    {
        // Set initial output to default value.
        $translated = (string) $default;
        // Load common messages file.
        if (empty(self::$messages)) {
            $file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(self::$extKey, 'Resources/Private/Language/FlashMessages.xml');
            if (\TYPO3_MODE === 'FE') {
                self::$messages = $GLOBALS['TSFE']->readLLfile($file);
            } elseif (\TYPO3_MODE === 'BE') {
                self::$messages = $GLOBALS['LANG']->includeLLFile($file, false, true);
            } else {
                self::devLog('Unexpected TYPO3_MODE "' . \TYPO3_MODE . '"', DEVLOG_SEVERITY_ERROR);
            }
        }
        // Get translation.
        if (!empty(self::$messages['default'][$key])) {
            if (\TYPO3_MODE === 'FE') {
                $translated = $GLOBALS['TSFE']->getLLL($key, self::$messages);
            } elseif (\TYPO3_MODE === 'BE') {
                $translated = $GLOBALS['LANG']->getLLL($key, self::$messages);
            } else {
                self::devLog('Unexpected TYPO3_MODE "' . \TYPO3_MODE . '"', DEVLOG_SEVERITY_ERROR);
            }
        }
        // Escape HTML characters if applicable.
        if ($hsc) {
            $translated = htmlspecialchars($translated);
        }
        return $translated;
    }

    /**
     * Get the UID for a given "index_name"
     *
     * @access public
     *
     * @param int $index_name: The index_name of the record
     * @param string $table: Get the "index_name" from this table
     * @param int $pid: Get the "index_name" from this page
     *
     * @return string "uid" for the given index_name
     */
    public static function getUidFromIndexName($index_name, $table, $pid = -1)
    {
        if (
            !$index_name
            || !in_array($table, ['tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures', 'tx_dlf_solrcores'])
        ) {
            self::devLog('Invalid UID ' . $index_name . ' or table "' . $table . '"', DEVLOG_SEVERITY_ERROR);
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $where = '';
        // Should we check for a specific PID, too?
        if ($pid !== -1) {
            $pid = max(intval($pid), 0);
            $where = $queryBuilder->expr()->eq($table . '.pid', $pid);
        }
        // Get index_name from database.
        $result = $queryBuilder
            ->select($table . '.uid AS uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($table . '.index_name', $queryBuilder->expr()->literal($index_name)),
                $where,
                self::whereExpression($table)
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $result->fetchAll();

        if (count($allResults) == 1) {
            return $allResults[0]['uid'];
        } else {
            self::devLog('No UID for given index_name "' . $index_name . '" and PID ' . $pid . ' found in table "' . $table . '"', DEVLOG_SEVERITY_WARNING);
            return '';
        }
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
        if (preg_match('/[^a-z0-9:-]/', $urn)) {
            self::devLog('Invalid chars in given parameters', DEVLOG_SEVERITY_WARNING);
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
     * Load value from user's session.
     *
     * @access public
     *
     * @param string $key: Session data key for retrieval
     *
     * @return mixed Session value for given key or null on failure
     */
    public static function loadFromSession($key)
    {
        // Cast to string for security reasons.
        $key = (string) $key;
        if (!$key) {
            self::devLog('Invalid key "' . $key . '" for session data retrieval', DEVLOG_SEVERITY_WARNING);
            return;
        }
        // Get the session data.
        if (\TYPO3_MODE === 'FE') {
            return $GLOBALS['TSFE']->fe_user->getKey('ses', $key);
        } elseif (\TYPO3_MODE === 'BE') {
            return $GLOBALS['BE_USER']->getSessionData($key);
        } else {
            self::devLog('Unexpected TYPO3_MODE "' . \TYPO3_MODE . '"', DEVLOG_SEVERITY_ERROR);
            return;
        }
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
     * Process a data and/or command map with TYPO3 core engine as admin.
     *
     * @access public
     *
     * @param array $data: Data map
     * @param array $cmd: Command map
     * @param bool $reverseOrder: Should the data map be reversed?
     * @param bool $cmdFirst: Should the command map be processed first?
     *
     * @return array Array of substituted "NEW..." identifiers and their actual UIDs.
     */
    public static function processDBasAdmin(array $data = [], array $cmd = [], $reverseOrder = false, $cmdFirst = false)
    {
        if (
            \TYPO3_MODE === 'BE'
            && $GLOBALS['BE_USER']->isAdmin()
        ) {
            // Instantiate TYPO3 core engine.
            $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            // We do not use workspaces and have to bypass restrictions in DataHandler.
            $dataHandler->bypassWorkspaceRestrictions = true;
            // Load data and command arrays.
            $dataHandler->start($data, $cmd);
            // Process command map first if default order is reversed.
            if (
                !empty($cmd)
                && $cmdFirst
            ) {
                $dataHandler->process_cmdmap();
            }
            // Process data map.
            if (!empty($data)) {
                $dataHandler->reverseOrder = $reverseOrder;
                $dataHandler->process_datamap();
            }
            // Process command map if processing order is not reversed.
            if (
                !empty($cmd)
                && !$cmdFirst
            ) {
                $dataHandler->process_cmdmap();
            }
            return $dataHandler->substNEWwithIDs;
        } else {
            self::devLog('Current backend user has no admin privileges', DEVLOG_SEVERITY_ERROR);
            return [];
        }
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
     * Save given value to user's session.
     *
     * @access public
     *
     * @param mixed $value: Value to save
     * @param string $key: Session data key for saving
     *
     * @return bool true on success, false on failure
     */
    public static function saveToSession($value, $key)
    {
        // Cast to string for security reasons.
        $key = (string) $key;
        if (!$key) {
            self::devLog('Invalid key "' . $key . '" for session data saving', DEVLOG_SEVERITY_WARNING);
            return false;
        }
        // Save value in session data.
        if (\TYPO3_MODE === 'FE') {
            $GLOBALS['TSFE']->fe_user->setKey('ses', $key, $value);
            $GLOBALS['TSFE']->fe_user->storeSessionData();
            return true;
        } elseif (\TYPO3_MODE === 'BE') {
            $GLOBALS['BE_USER']->setAndSaveSessionData($key, $value);
            return true;
        } else {
            self::devLog('Unexpected TYPO3_MODE "' . \TYPO3_MODE . '"', DEVLOG_SEVERITY_ERROR);
            return false;
        }
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
            self::devLog('Invalid PID ' . $pid . ' for translation', DEVLOG_SEVERITY_WARNING);
            return $index_name;
        }
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
                    $queryBuilder->expr()->eq($table . '.sys_language_uid', intval($GLOBALS['TSFE']->sys_language_content)),
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
        if (empty($labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name])) {
            // Check if this table is allowed for translation.
            if (in_array($table, ['tx_dlf_collections', 'tx_dlf_libraries', 'tx_dlf_metadata', 'tx_dlf_structures'])) {
                $additionalWhere = $queryBuilder->expr()->in($table . '.sys_language_uid', [-1, 0]);
                if ($GLOBALS['TSFE']->sys_language_content > 0) {
                    $additionalWhere = $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->in($table . '.sys_language_uid', [-1, 0]),
                            $queryBuilder->expr()->eq($table . '.sys_language_uid', intval($GLOBALS['TSFE']->sys_language_content))
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
                        if ($GLOBALS['TSFE']->sys_language_content > 0) {
                            $resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay($table, $resArray, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
                        }
                        if ($resArray) {
                            $labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$resArray['index_name']] = $resArray['label'];
                        }
                    }
                } else {
                    self::devLog('No translation with PID ' . $pid . ' available in table "' . $table . '" or translation not accessible', DEVLOG_SEVERITY_NOTICE);
                }
            } else {
                self::devLog('No translations available for table "' . $table . '"', DEVLOG_SEVERITY_WARNING);
            }
        }

        if (!empty($labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name])) {
            return $labels[$table][$pid][$GLOBALS['TSFE']->sys_language_content][$index_name];
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
            $expression = $GLOBALS['TSFE']->sys_page->enableFields($table, $ignoreHide);
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
            self::devLog('Unexpected TYPO3_MODE "' . \TYPO3_MODE . '"', DEVLOG_SEVERITY_ERROR);
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
}
