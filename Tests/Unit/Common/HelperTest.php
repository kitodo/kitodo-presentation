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

    /**
     * @test
     * @group getXmlFileAsString
     */
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

    /**
     * @test
     * @group getXmlFileAsString
     */
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

    /**
     * @test
     * @group timeCodeToSeconds
     */
    public function canConvertTimeCode()
    {
        $this->assertEquals(20, Helper::timeCodeToSeconds('20'));
        $this->assertEquals(20.5, Helper::timeCodeToSeconds('20.5'));
        $this->assertEquals(80.5, Helper::timeCodeToSeconds('1:20.5'));
    }

    /**
     * @test
     * @group filterFilesByMimeType
     */
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

    /**
     * @test
     * @group filterFilesByMimeType
     */
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

    /**
     * @test
     * @group filterFilesByMimeType
     */
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

    /**
     * @test
     * @group filterFilesByMimeType
     */
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

    /**
     * @test
     * @group filterFilesByMimeType
     */
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

    /**
     * @test
     * @group filterFilesByMimeType
     */
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

    /**
     * @test
     * @group filterFilesByMimeType
     */
    public function filterFilesByMimeTypeHandlesDifferentDlfModeTypes(): void
    {
        // Test-Setup
        $imageFile = ['mimetype' => 'image/jpeg'];
        $iiifFile = ['mimetype' => 'application/vnd.kitodo.iiif'];
        $iipFile = ['mimetype' => 'application/vnd.netfpx'];

        // Test: No DLF MIME Types (only Standard-Types)
        self::assertTrue(Helper::filterFilesByMimeType(
            $imageFile,
            ['image'],
            null
        ), 'Standard image type should be accepted when DLF types are null'
        );

        self::assertFalse(Helper::filterFilesByMimeType(
            $iiifFile,
            ['image'],
            null
        ), 'DLF type should be rejected when DLF types are null'
        );

        // Test: All DLF MIME Types
        self::assertTrue(Helper::filterFilesByMimeType(
            $iiifFile,
            ['image'],
            true
        ), 'IIIF should be accepted when all DLF types are enabled'
        );

        self::assertTrue(Helper::filterFilesByMimeType(
            $iipFile,
            ['image'],
            true
        ), 'IIP should be accepted when all DLF types are enabled'
        );

        // Test: Spezific DLF MIME Types
        self::assertTrue(Helper::filterFilesByMimeType(
            $iiifFile,
            ['image'],
            ['IIIF']
        ), 'IIIF should be accepted when specifically allowed'
        );

        self::assertFalse(Helper::filterFilesByMimeType(
            $iipFile,
            ['image'],
            ['IIIF']
        ), 'IIP should be rejected when not in allowed DLF types'
        );
    }
}
