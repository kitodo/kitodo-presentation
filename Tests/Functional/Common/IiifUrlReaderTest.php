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

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\IiifUrlReader;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class IiifUrlReaderTest extends FunctionalTestCase
{
    /**
     * @test
     * @group getContent
     */
    public function getContentCheck()
    {
        $iiifUrlReader = new IiifUrlReader();

        $correctUrl = 'http://web:8001/Tests/Fixtures/Common/correct.txt';
        $expected = "Correct result\n";
        self::assertSame($expected, $iiifUrlReader->getContent($correctUrl));

        $incorrectUrl = 'http://web:8001/incorrectPath';
        self::assertEmpty($iiifUrlReader->getContent($incorrectUrl));
    }
}
