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

namespace Kitodo\Dlf\Tests\Unit\Common;

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Common\DocumentCacheManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionProperty;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DocumentCacheManagerTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected string $location = 'https://example.org/sample1.xml';

    protected function tearDown(): void
    {
        $this->flushPool();
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * Get the DocumentCacheManager with a mocked cache frontend.
     *
     * The manager's constructor builds its frontend through the
     * (statically cached) CacheManager singleton, so the cache manager
     * is replaced by a mock before the manager is instantiated.
     *
     * @access private
     *
     * @return DocumentCacheManager
     */
    private function getCacheManager(): DocumentCacheManager
    {
        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->method('getCache')->willReturn(
            $this->createMock(FrontendInterface::class)
        );
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);

        // ExtensionConfiguration is read from TYPO3_CONF_VARS.
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['dlf'] = ['general' => []];
        return GeneralUtility::makeInstance(DocumentCacheManager::class);
    }

    /**
     * Reset the static in-memory pool.
     *
     * @access private
     *
     * @return void
     */
    private function flushPool(): void
    {
        $reflection = new ReflectionClass(DocumentCacheManager::class);
        $reflection->setStaticPropertyValue('pool', []);
    }

    /**
     * A pool hit returns the very same document instance without reading
     * the file cache again, which is what skips the XML re-parsing.
     */
    #[Test]
    public function getFromPoolHitsReturnsSameInstanceWithoutReadingFileCache(): void
    {
        $cacheManager = $this->getCacheManager();
        $cache = $this->getCacheProperty($cacheManager);
        $document = $this->getDocument();
        $cache->expects(self::once())->method('set');
        $cacheManager->set($this->location, $document);

        $cache->expects(self::never())->method('get');
        self::assertSame($document, $cacheManager->get($this->location));
    }

    /**
     * Pool entries expire after the pool TTL; a stale entry is dropped
     * and the file cache is read again instead of being returned.
     */
    #[Test]
    public function expiredPoolEntriesAreReadFromTheFileCacheAgain(): void
    {
        $cacheManager = $this->getCacheManager();
        $cache = $this->getCacheProperty($cacheManager);
        $document = $this->getDocument();
        $cache->method('set')->willReturnSelf();
        $cacheManager->set($this->location, $document);

        $reflection = new ReflectionClass(DocumentCacheManager::class);
        $pool = $reflection->getStaticPropertyValue('pool');
        $pool[array_key_first($pool)][1] -= 301;
        $reflection->setStaticPropertyValue('pool', $pool);

        $cache->expects(self::once())->method('get')->willReturn(false);
        self::assertFalse($cacheManager->get($this->location));
    }

    /**
     * More than five documents in the pool evict the least recently used
     * one first.
     */
    #[Test]
    public function poolEvictsLeastRecentlyUsedDocument(): void
    {
        $cacheManager = $this->getCacheManager();
        $cache = $this->getCacheProperty($cacheManager);
        $cache->method('set')->willReturnSelf();
        $documents = [];
        foreach (range(1, 6) as $i) {
            $documents[$i] = $this->getDocument();
            $cacheManager->set("https://example.org/sample$i.xml", $documents[$i]);
        }

        // sample1 has been evicted, sample6 is still pooled.
        $cache->expects(self::once())->method('get')->willReturn(false);
        self::assertFalse($cacheManager->get('https://example.org/sample1.xml'));
        $cache->expects(self::never())->method('get');
        self::assertSame($documents[6], $cacheManager->get('https://example.org/sample6.xml'));
    }

    /**
     * Loading a new document from the file cache adds it to the pool, so
     * a subsequent read for the same location does not touch the file
     * cache again.
     */
    #[Test]
    public function documentReadFromFileCacheIsAddedToPool(): void
    {
        $cacheManager = $this->getCacheManager();
        $cache = $this->getCacheProperty($cacheManager);
        $document = $this->getDocument();
        $cache->expects(self::once())->method('get')->willReturn($document);
        self::assertSame($document, $cacheManager->get($this->location));

        $cache->expects(self::never())->method('get');
        self::assertSame($document, $cacheManager->get($this->location));
    }

    /**
     * remove() also removes the document from the in-memory pool.
     */
    #[Test]
    public function removeAlsoRemovesFromPool(): void
    {
        $cacheManager = $this->getCacheManager();
        $cache = $this->getCacheProperty($cacheManager);
        $document = $this->getDocument();
        $cache->method('set')->willReturnSelf();
        $cacheManager->set($this->location, $document);

        $cache->expects(self::once())->method('remove');
        $cacheManager->remove($this->location);

        $cache->expects(self::once())->method('get')->willReturn(false);
        self::assertFalse($cacheManager->get($this->location));
    }

    /**
     * flush() also clears the in-memory pool.
     */
    #[Test]
    public function flushAlsoClearsPool(): void
    {
        $cacheManager = $this->getCacheManager();
        $cache = $this->getCacheProperty($cacheManager);
        $document = $this->getDocument();
        $cache->method('set')->willReturnSelf();
        $cacheManager->set($this->location, $document);

        $cache->expects(self::once())->method('flush');
        $cacheManager->flush();

        $cache->expects(self::once())->method('get')->willReturn(false);
        self::assertFalse($cacheManager->get($this->location));
    }

    /**
     * setFail() drops the document from the pool, so a subsequent read
     * goes to the file cache and can return the LOAD_FAILED sentinel.
     */
    #[Test]
    public function setFailRemovesFromPool(): void
    {
        $cacheManager = $this->getCacheManager();
        $cache = $this->getCacheProperty($cacheManager);
        $document = $this->getDocument();
        $cache->method('set')->willReturnSelf();
        $cacheManager->set($this->location, $document);

        $cacheManager->setFail($this->location);

        $cache->expects(self::once())
            ->method('get')
            ->willReturn(DocumentCacheManager::LOAD_FAILED);
        self::assertSame(DocumentCacheManager::LOAD_FAILED, $cacheManager->get($this->location));
    }

    /**
     * The mocked cache frontend of the manager under test.
     *
     * @access private
     *
     * @param DocumentCacheManager $cacheManager
     *
     * @return FrontendInterface&MockObject
     */
    private function getCacheProperty(DocumentCacheManager $cacheManager): FrontendInterface&MockObject
    {
        $reflection = new ReflectionProperty($cacheManager, 'cache');
        return $reflection->getValue($cacheManager);
    }

    /**
     * Create a document instance without calling the (protected)
     * AbstractDocument constructor, which would require XML.
     *
     * @access private
     *
     * @return AbstractDocument&MockObject
     */
    private function getDocument(): AbstractDocument&MockObject
    {
        return $this->createMock(AbstractDocument::class);
    }
}
