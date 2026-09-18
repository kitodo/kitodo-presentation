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

namespace Kitodo\Dlf\Tests\Unit\Controller;

use Kitodo\Dlf\Controller\AbstractController;
use Kitodo\Dlf\Controller\PageViewController;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

interface ConfigureProxyUrlCaller
{
    public function callConfigureProxyUrl(string &$url): void;
}

class AbstractControllerTest extends UnitTestCase
{
    private const ORIGINAL_URL = 'https://example.com/mets_audio/jpegs/00000002.tif.large.jpg';

    private const PROXY_URL = 'https://test.local/index.php';

    #[Test]
    public function configureProxyUrlRewritesUrlByReference(): void
    {
        $controller = $this->createControllerWithUriBuilder($this->createUriBuilderMock());

        $url = self::ORIGINAL_URL;
        $controller->callConfigureProxyUrl($url);

        // The URL must be rewritten in place to the built proxy URL.
        self::assertSame(self::PROXY_URL, $url);
    }

    #[Test]
    public function configureProxyUrlPassesOriginalUrlAndHmacToUriBuilder(): void
    {
        $uriBuilderMock = $this->createUriBuilderMock();
        $uriBuilderMock->expects(self::once())
            ->method('setArguments')
            ->with(self::callback(
                static function (array $arguments): bool {
                    return ($arguments['middleware'] ?? '') === 'dlf/page-view-proxy'
                        && ($arguments['url'] ?? '') === self::ORIGINAL_URL
                        && ($arguments['uHash'] ?? '') === GeneralUtility::hmac(self::ORIGINAL_URL, 'PageViewProxy');
                }
            ))
            ->willReturn($uriBuilderMock);

        $controller = $this->createControllerWithUriBuilder($uriBuilderMock);

        $url = self::ORIGINAL_URL;
        $controller->callConfigureProxyUrl($url);
    }

    /**
     * Creates a PageViewController with a mocked UriBuilder that exposes the
     * protected configureProxyUrl() method for testing.
     */
    private function createControllerWithUriBuilder(UriBuilder $uriBuilder): ConfigureProxyUrlCaller
    {
        $controller = new class () extends PageViewController implements ConfigureProxyUrlCaller {
            public function callConfigureProxyUrl(string &$url): void
            {
                $this->configureProxyUrl($url);
            }
        };
        $this->injectProperty($controller, ActionController::class, 'uriBuilder', $uriBuilder);
        $this->injectProperty($controller, AbstractController::class, 'extConf', ['general' => []]);
        $this->injectProperty($controller, AbstractController::class, 'pageUid', 123);
        return $controller;
    }

    /**
     * Creates a mock UriBuilder supporting the fluent interface used by
     * configureProxyUrl() and returning PROXY_URL from build().
     */
    private function createUriBuilderMock(): UriBuilder&MockObject
    {
        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('reset')->willReturn($uriBuilderMock);
        $uriBuilderMock->method('setTargetPageUid')->willReturn($uriBuilderMock);
        $uriBuilderMock->method('setCreateAbsoluteUri')->willReturn($uriBuilderMock);
        $uriBuilderMock->method('setArguments')->willReturn($uriBuilderMock);
        $uriBuilderMock->method('build')->willReturn(self::PROXY_URL);
        return $uriBuilderMock;
    }

    private function injectProperty(object $object, string $declaringClass, string $propertyName, mixed $value): void
    {
        $property = new ReflectionProperty($declaringClass, $propertyName);
        $property->setValue($object, $value);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Required by GeneralUtility::hmac() for the uHash argument.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'TestEncryptionKey';
    }
}
