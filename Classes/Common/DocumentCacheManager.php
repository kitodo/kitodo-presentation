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
        return $this->cache->get($this->getIdentifier($location));
    }

    /**
     * Set cache for document instance.
     *
     * The entry uses the cache's default lifetime (one day).
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
        $this->cache->set($this->getIdentifier($location), $currentDocument);
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
     * Remove all documents from cache.
     *
     * @access public
     *
     * @return void
     */
    public function flush(): void
    {
        $this->cache->flush();
    }

    /**
     * Remove single document from cache.
     *
     * @access public
     *
     * @param string $location
     *
     * @return void
     */
    public function remove(string $location): void
    {
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
}
