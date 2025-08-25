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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DocumentCacheManager class for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DocumentCacheManager
{
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
     * Get document instance from cache or false if not found.
     *
     * @access public
     *
     * @param string $location
     *
     * @return AbstractDocument|false
     */
    public function get(string $location)
    {
        return $this->cache->get($this->getIdentifier($location));
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
     * Set cache for document instance.
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
