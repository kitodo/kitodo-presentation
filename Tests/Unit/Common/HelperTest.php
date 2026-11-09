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

use Kitodo\Dlf\Common\Helper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class HelperTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @var LogManager|MockObject
     */
    protected LogManager|MockObject $logManagerMock;

    /**
     * @var Logger|MockObject
     */
    protected Logger|MockObject $loggerMock;

    /**
     * @var RequestFactory|MockObject
     */
    protected RequestFactory|MockObject $requestFactoryMock;

    /**
     * Sets up the test environment.
     *
     * @access protected
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // create Logger Mock
        $this->loggerMock = $this->createMock(Logger::class);

        // create LogManager Mock
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->logManagerMock->method('getLogger')->willReturn($this->loggerMock);

        GeneralUtility::setSingletonInstance(LogManager::class, $this->logManagerMock);
    }

    /**
     * Cleans up the test environment.
     *
     * @access protected
     *
     * @return void
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * Asserts that the given XML is invalid.
     *
     * @access private
     *
     * @param mixed $xml The XML to check
     *
     * @return void
     */
    private static function assertInvalidXml(mixed $xml): void
    {
        $result = Helper::getXmlFileAsString($xml);
        self::assertFalse($result);
    }

    #[Test]
    #[Group('getXmlFileAsString')]
    public function invalidXmlYieldsFalse(): void
    {
        self::assertInvalidXml(false);
        self::assertInvalidXml(null);
        self::assertInvalidXml(1);
        self::assertInvalidXml([]);
        self::assertInvalidXml(new \stdClass());
        self::assertInvalidXml('');
        self::assertInvalidXml('not xml');
        self::assertInvalidXml('<tag-not-closed>');
    }

    #[Test]
    #[Group('getXmlFileAsString')]
    public function validXmlIsAccepted(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <single />
</root>
XML;
        $node = Helper::getXmlFileAsString($xml);
        self::assertIsObject($node);
        self::assertEquals('root', $node->getName());
    }

    #[Test]
    #[Group('timeCodeToSeconds')]
    public function canConvertTimeCode()
    {
        $this->assertEquals(20, Helper::timeCodeToSeconds('20'));
        $this->assertEquals(20.5, Helper::timeCodeToSeconds('20.5'));
        $this->assertEquals(80.5, Helper::timeCodeToSeconds('1:20.5'));
    }

    #[Test]
    #[Group('filterFilesByMimeType')]
    public function filterFilesByMimeTypeHandlesInvalidInput(): void
    {
        // Empty categories and types
        self::assertFalse(Helper::filterFilesByMimeType(
            ['mimetype' => 'image/jpeg'],
            []
        ));

        // Invalid file input
        self::assertFalse(Helper::filterFilesByMimeType(
            null,
            ['image']
        ));

        // Missing mime type key
        self::assertFalse(Helper::filterFilesByMimeType(
            ['wrong_key' => 'image/jpeg'],
            ['image']
        ));
    }

    #[Test]
    #[Group('filterFilesByMimeType')]
    public function filterFilesByMimeTypeAcceptsStandardMimeTypes(): void
    {
        $file = ['mimetype' => 'image/jpeg'];

        self::assertTrue(Helper::filterFilesByMimeType(
            $file,
            ['image']
        ));

        self::assertFalse(Helper::filterFilesByMimeType(
            $file,
            ['video']
        ));

        // Test multiple categories
        self::assertTrue(Helper::filterFilesByMimeType(
            $file,
            ['video', 'image']
        ));
    }

    #[Test]
    #[Group('filterFilesByMimeType')]
    public function filterFilesByMimeTypeHandlesCustomDlfTypes(): void
    {
        $testCases = [
            ['mimetype' => 'application/vnd.kitodo.iiif'],
            ['mimetype' => 'application/vnd.netfpx'],
            ['mimetype' => 'application/vnd.kitodo.zoomify'],
            ['mimetype' => 'image/jpg']
        ];

        foreach ($testCases as $file) {
            self::assertTrue(Helper::filterFilesByMimeType(
                $file,
                [],
                true
            ));
        }

        foreach ($testCases as $file) {
            self::assertTrue(Helper::filterFilesByMimeType(
                $file,
                [],
                ['IIIF', 'IIP', 'ZOOMIFY', 'JPG']
            ));
        }

        // Test specific DLF type filtering
        $file = ['mimetype' => 'application/vnd.kitodo.iiif'];
        self::assertTrue(Helper::filterFilesByMimeType(
            $file,
            [],
            true
        ));
        self::assertFalse(Helper::filterFilesByMimeType(
            $file,
            [],
            ['IIP']
        ));
    }

    #[Test]
    #[Group('filterFilesByMimeType')]
    public function filterFilesByMimeTypeHandlesCustomMimeTypeKey(): void
    {
        $file = ['customKey' => 'image/jpeg'];

        self::assertTrue(Helper::filterFilesByMimeType(
            $file,
            ['image'],
            null,
            'customKey'
        ));

        self::assertFalse(Helper::filterFilesByMimeType(
            $file,
            ['image']
        ));
    }

    #[Test]
    #[Group('filterFilesByMimeType')]
    public function filterFilesByMimeTypeHandlesMixedScenarios(): void
    {
        // Standard mime type with DLF types enabled
        self::assertTrue(Helper::filterFilesByMimeType(
            ['mimetype' => 'image/jpeg'],
            ['image'],
            ['IIIF', 'IIP']
        ));

        // DLF mime type with matching category
        self::assertTrue(Helper::filterFilesByMimeType(
            ['mimetype' => 'application/vnd.kitodo.iiif'],
            ['application'],
            ['IIIF']
        ));

        // DLF mime type with non-matching category but allowed DLF type
        self::assertTrue(Helper::filterFilesByMimeType(
            ['mimetype' => 'application/vnd.kitodo.iiif'],
            ['image'],
            ['IIIF']
        ));
    }

    #[Test]
    #[Group('filterFilesByMimeType')]
    public function filterFilesByMimeTypeHandlesWrongJpg(): void
    {
        $wrongJpg = ['mimetype' => 'image/jpg'];

        // file with wrong JPG mime type and no custom dlf mime type key
        self::assertFalse(Helper::filterFilesByMimeType(
            $wrongJpg,
            ['image'],
            []
        ));

        // file with wrong JPG mime type and custom dlf mime type key
        self::assertTrue(Helper::filterFilesByMimeType(
            $wrongJpg,
            [],
            ['JPG']
        ));

        // file with wrong JPG mime type in allowed key and custom dlf mime type key
        self::assertTrue(Helper::filterFilesByMimeType(
            $wrongJpg,
            ['image'],
            ['JPG']
        ));
    }

    #[Test]
    #[Group('filterFilesByMimeType')]
    public function filterFilesByMimeTypeHandlesDifferentDlfModeTypes(): void
    {
        // Test-Setup
        $imageFile = ['mimetype' => 'image/jpeg'];
        $iiifFile = ['mimetype' => 'application/vnd.kitodo.iiif'];
        $iipFile = ['mimetype' => 'application/vnd.netfpx'];

        // Test: No DLF MIME Types (only Standard-Types)
        self::assertTrue(
            Helper::filterFilesByMimeType(
                $imageFile,
                ['image'],
                null
            ),
            'Standard image type should be accepted when DLF types are null'
        );

        self::assertFalse(
            Helper::filterFilesByMimeType(
                $iiifFile,
                ['image'],
                null
            ),
            'DLF type should be rejected when DLF types are null'
        );

        // Test: All DLF MIME Types
        self::assertTrue(
            Helper::filterFilesByMimeType(
                $iiifFile,
                ['image'],
                true
            ),
            'IIIF should be accepted when all DLF types are enabled'
        );

        self::assertTrue(
            Helper::filterFilesByMimeType(
                $iipFile,
                ['image'],
                true
            ),
            'IIP should be accepted when all DLF types are enabled'
        );

        // Test: Specific DLF MIME Types
        self::assertTrue(
            Helper::filterFilesByMimeType(
                $iiifFile,
                ['image'],
                ['IIIF']
            ),
            'IIIF should be accepted when specifically allowed'
        );

        self::assertFalse(
            Helper::filterFilesByMimeType(
                $iipFile,
                ['image'],
                ['IIIF']
            ),
            'IIP should be rejected when not in allowed DLF types'
        );
    }

    /**
     * Get the content of the given URL with the RequestFactory replaced by a
     * mock that returns a response with the given body and content length.
     *
     * @access private
     *
     * @param string $url The URL to fetch
     * @param string $body The body of the response
     * @param int|string $contentLength The value of the Content-Length header, or '' if absent
     * @param mixed $maxRemoteFileSize The value of the "maxRemoteFileSize" extension setting
     *
     * @return mixed The result of Helper::getUrl()
     */
    private function getUrlWithMockedResponse(
        string $url,
        string $body,
        int|string $contentLength = '',
        mixed $maxRemoteFileSize = 0
    ): mixed {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['dlf'] = [
            'general' => ['maxRemoteFileSize' => $maxRemoteFileSize],
        ];

        $response = new Response();
        if ($contentLength !== '') {
            $response = $response->withHeader('Content-Length', (string) $contentLength);
        }
        $response->getBody()->write($body);
        $response->getBody()->rewind();

        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->requestFactoryMock
            ->method('request')
            ->with($url, 'GET', $this->callback(function (array $options): bool {
                return ($options['stream'] ?? false) === true;
            }))
            ->willReturn($response);
        GeneralUtility::addInstance(RequestFactory::class, $this->requestFactoryMock);

        // The location is trusted so that no DNS lookup is needed for the host check.
        return Helper::getUrl($url, true);
    }

    #[Test]
    #[Group('getUrl')]
    public function getUrlReturnsContentWithinSizeLimit(): void
    {
        $body = 'some content';
        self::assertSame($body, $this->getUrlWithMockedResponse(
            'https://example.org/file.xml',
            $body,
            strlen($body),
            strlen($body)
        ));
    }

    #[Test]
    #[Group('getUrl')]
    public function getUrlReturnsContentWhenSizeLimitDisabled(): void
    {
        $body = 'some very long content';
        self::assertSame($body, $this->getUrlWithMockedResponse(
            'https://example.org/file.xml',
            $body,
            strlen($body),
            0
        ));
    }

    #[Test]
    #[Group('getUrl')]
    public function getUrlRefusesContentLengthAboveLimit(): void
    {
        self::assertFalse($this->getUrlWithMockedResponse(
            'https://example.org/file.xml',
            'too large',
            1024,
            10
        ));
    }

    #[Test]
    #[Group('getUrl')]
    public function getUrlRefusesContentWithoutContentLengthAboveLimit(): void
    {
        self::assertFalse($this->getUrlWithMockedResponse(
            'https://example.org/file.xml',
            'some content that is too large for the configured limit',
            '',
            10
        ));
    }

    #[Test]
    #[Group('getUrl')]
    public function getUrlRefusesLyingContentLength(): void
    {
        // The declared content length is below the limit, but the actual
        // body is larger.
        self::assertFalse($this->getUrlWithMockedResponse(
            'https://example.org/file.xml',
            'some content that is larger than the declared content length',
            5,
            10
        ));
    }

    #[Test]
    #[Group('getUrl')]
    public function getUrlAcceptsContentShorterThanContentLength(): void
    {
        $body = 'short';
        self::assertSame($body, $this->getUrlWithMockedResponse(
            'https://example.org/file.xml',
            $body,
            1024,
            2048
        ));
    }

    #[Test]
    #[Group('getUrl')]
    public function getUrlRejectsInvalidUrlsWithoutRequest(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['dlf'] = ['general' => []];
        foreach (['not a url', 'file:///etc/hosts', 'mailto:example@example.org'] as $url) {
            $this->requestFactoryMock = $this->createMock(RequestFactory::class);
            $this->requestFactoryMock->expects(self::never())->method('request');
            GeneralUtility::addInstance(RequestFactory::class, $this->requestFactoryMock);
            self::assertFalse(Helper::getUrl($url));
        }
    }
}
