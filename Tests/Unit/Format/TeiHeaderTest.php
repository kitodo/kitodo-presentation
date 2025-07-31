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

namespace Kitodo\Dlf\Tests\Unit\Format;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TeiHeaderTest extends UnitTestCase
{
    protected array $metadata = [];

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
