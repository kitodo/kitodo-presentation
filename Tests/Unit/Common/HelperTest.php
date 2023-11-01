<?php

namespace Kitodo\Dlf\Tests\Unit\Common;

use Kitodo\Dlf\Common\Helper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HelperTest extends UnitTestCase
{
    public function assertInvalidXml($xml)
    {
        $result = Helper::getXmlFileAsString($xml);
        self::assertEquals(false, $result);
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
}
