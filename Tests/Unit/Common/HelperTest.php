<?php

namespace Kitodo\Dlf\Tests\Unit\Common;

use Kitodo\Dlf\Common\Helper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HelperTest extends UnitTestCase
{
    public function assertInvalidXml($xml)
    {
        $result = Helper::getXmlFileAsString($xml);
        $this->assertEquals(false, $result);
    }

    /**
     * @test
     * @group getXmlFileAsString
     */
    public function invalidXmlYieldsFalse(): void
    {
        $this->assertInvalidXml(false);
        $this->assertInvalidXml(null);
        $this->assertInvalidXml(1);
        $this->assertInvalidXml([]);
        $this->assertInvalidXml(new \stdClass());
        $this->assertInvalidXml('');
        $this->assertInvalidXml('not xml');
        $this->assertInvalidXml('<tag-not-closed>');
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
        $this->assertIsObject($node);
        $this->assertEquals('root', $node->getName());
    }
}
