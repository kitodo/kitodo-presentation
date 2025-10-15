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

use Kitodo\Dlf\Format\Tei;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TeiTest extends UnitTestCase
{
    /**
     * @test
     * @group extract data
     */
    public function getRawData(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/tei.xml');
        $tei = new Tei();
        $tei->setPageId('f0001');
        $rawText = $tei->getRawText($xml);

        self::assertEquals('Lorem ipsum dolor sit amet, consectetuer adipiscing elit.', $rawText);
    }

    /**
     * @test
     * @group extract data
     */
    public function getTextAsMiniOcr(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/tei.xml');
        $tei = new Tei();
        $tei->setPageId('f0002');
        $rawText = $tei->getTextAsMiniOcr($xml);

        $miniOCR = <<<XML
        <ocr>
            <b>Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim.</b>
        </ocr>
        XML;

        self::assertXmlStringEqualsXmlString($miniOCR, $rawText);
    }
}
