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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractDocumentTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * When a document location has been remembered as unloadable, getInstance
     * must not fetch it over the network again on every call. This is the guard
     * against a single page view fanning out into dozens of repeated HTTP
     * requests for a document that cannot be loaded (e.g. an OAI-PMH GetRecord
     * URL for an unknown identifier).
     */
    #[Test]
    public function getInstanceReturnsNullWithoutFetchingWhenFailIsCached(): void
    {
        $cacheManager = $this->createMock(DocumentCacheManager::class);
        // The document cache reports a previous failure for this location ...
        $cacheManager->expects(self::once())
            ->method('get')
            ->willReturn(DocumentCacheManager::LOAD_FAILED);
        GeneralUtility::setSingletonInstance(DocumentCacheManager::class, $cacheManager);

        $document = AbstractDocument::getInstance('http://example.com/does-not-exist.xml', ['storagePid' => 1]);

        self::assertNull($document);
    }
}
