<?php

namespace Kitodo\Dlf\Tests\Unit\Format;

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
        //TODO: TeiHeader class has no useful implementation.
        $this->markTestSkipped('Implement test when TeiHeader class is implemented.');
    }
}
