<?php

namespace Kitodo\Dlf\Tests\Unit\Format;

use Kitodo\Dlf\Format\TeiHeader ;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TeiHeaderTest extends UnitTestCase
{
    protected $metadata = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->metadata = [];
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function extract(): void
    {
        $teiHeader = new TeiHeader();
        //$teiHeader->extractMetadata();
        //TODO: TeiHeader class not fully implemented.
        $this->assertTrue(false);
    }
}
