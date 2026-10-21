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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DocumentCacheManager class for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DocumentCacheManager implements SingletonInterface
{
    /**
     * Sentinel stored in the document cache to remember that loading a
     * document failed, so that the same unloadable location is not fetched
     * over the network again on every request.
     */
    public const LOAD_FAILED = '__DLF_DOC_LOAD_FAILED__';

    /**
     * Lifetime of entries in the in-memory pool in seconds. An entry is
     * refreshed (sliding TTL) as long as it is requested regularly.
     */
    private const POOL_TTL = 300;

    /**
     * Maximum number of documents kept in the in-memory pool per
     * PHP-FPM worker. The oldest (least recently used) entry is
     * evicted first.
     */
    private const POOL_MAX_ENTRIES = 5;

    /**
     * In-memory, per-PHP-FPM-worker pool of recently used documents.
     *
     * Reading a document from the file-based TYPO3 cache requires
     * deserializing the entry and re-parsing the complete METS XML in
     * AbstractDocument::__wakeup(). Documents that are requested
     * repeatedly (e.g. by crawlers) are therefore additionally kept
     * in this short-lived pool: a pool hit skips the file cache, the
     * deserialization and the XML re-parsing entirely, and the already
     * computed lazy data (physical structure, metadata array, file
     * groups) is reused as well.
     *
     * Note: PHP static state lives per FPM worker process and is only
     * accessed by one request at a time, so sharing the instances
     * between sequential requests of the same worker is safe.
     *
     * @var array<string, array{0: AbstractDocument, 1: int}>
     */
    protected static array $pool = [];

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_dlf_doc');
    }

    /**
     * Get document instance from cache.
     *
     * The per-worker in-memory pool is checked first; on a hit the
     * fully loaded document is returned without touching the file
     * cache. On a miss the file cache is read and the result is added
     * to the pool (subject to its TTL and entry cap).
     *
     * @access public
     *
     * @param string $location
     *
     * @return AbstractDocument|false|self::LOAD_FAILED The cached document, the
     * LOAD_FAILED sentinel if a previous load of this location failed, or false
     * if nothing is cached
     */
    public function get(string $location)
    {
        $identifier = $this->getIdentifier($location);
        $now = time();
        if (isset(self::$pool[$identifier])) {
            [$document, $cachedAt] = self::$pool[$identifier];
            // Re-insert the entry to keep least-recently-used ordering.
            unset(self::$pool[$identifier]);
            if ($now - $cachedAt < self::POOL_TTL) {
                self::$pool[$identifier] = [$document, $now];
                return $document;
            }
        }
        $instance = $this->cache->get($identifier);
        if ($instance instanceof AbstractDocument) {
            self::$pool[$identifier] = [$instance, $now];
            self::evictOldestPoolEntries();
        }
        return $instance;
    }

    /**
     * Set cache for document instance.
     *
     * The entry uses the cache's default lifetime (one day) in the
     * file cache and POOL_TTL in the per-worker in-memory pool.
     *
     * @access public
     *
     * @param string $location
     * @param AbstractDocument $currentDocument
     *
     * @return void
     */
    public function set(string $location, AbstractDocument $currentDocument): void
    {
        $identifier = $this->getIdentifier($location);
        $this->cache->set($identifier, $currentDocument);
        unset(self::$pool[$identifier]);
        self::$pool[$identifier] = [$currentDocument, time()];
        self::evictOldestPoolEntries();
    }

    /**
     * Remember that loading a document failed.
     *
     * The entry is stored in the same document cache but only for the
     * configured fail-cache lifetime (a short default is used so that a
     * document that appears later is picked up again) and can be removed via
     * remove() like a regular entry.
     *
     * @access public
     *
     * @param string $location
     *
     * @return void
     */
    public function setFail(string $location): void
    {
        unset(self::$pool[$this->getIdentifier($location)]);
        $this->cache->set($this->getIdentifier($location), self::LOAD_FAILED, [], $this->getFailLifetime());
    }

    /**
     * Get the lifetime in seconds for cached "load failed" flags.
     *
     * @access private
     *
     * @return int
     */
    private function getFailLifetime(): int
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf', 'general');
        $lifetime = (int) ($extConf['failCacheLifetime'] ?? 0);
        return $lifetime > 0 ? $lifetime : 300;
    }

    /**
     * Remove all documents from cache and from the in-memory pool.
     *
     * @access public
     *
     * @return void
     */
    public function flush(): void
    {
        self::$pool = [];
        $this->cache->flush();
    }

    /**
     * Reset the in-memory pool without touching the file cache.
     *
     * Unlike flush(), which is part of the public cache API, this is
     * intended for testing only: the pool is per-worker state that
     * survives individual functional tests, and a pooled document
     * loaded by a previous test (possibly with different settings)
     * would leak into the next test.
     *
     * @access public
     *
     * @static
     *
     * @return void
     */
    public static function resetPool(): void
    {
        self::$pool = [];
    }

    /**
     * Remove single document from cache and from the in-memory pool.
     *
     * @access public
     *
     * @param string $location
     *
     * @return void
     */
    public function remove(string $location): void
    {
        unset(self::$pool[$this->getIdentifier($location)]);
        $this->cache->remove($this->getIdentifier($location));
    }

    /**
     * Get cache identifier for document location.
     *
     * @access private
     *
     * @param string $location
     *
     * @return string
     */
    private function getIdentifier(string $location): string
    {
        return hash('md5', $location);
    }

    /**
     * Evict least-recently-used entries from the in-memory pool until
     * it contains at most POOL_MAX_ENTRIES documents.
     *
     * @access private
     *
     * @return void
     */
    private static function evictOldestPoolEntries(): void
    {
        while (count(self::$pool) > self::POOL_MAX_ENTRIES) {
            unset(self::$pool[array_key_first(self::$pool)]);
        }
    }
}
