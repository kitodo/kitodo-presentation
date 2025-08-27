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

use Kitodo\Dlf\Format\Alto;
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

        $rawText = $tei->getRawText($xml);

        self::assertEquals('Bürgertum und Bürgerlichkeit in Dresden DRESDNER HEFTE', $rawText);
    }

    /**
     * @test
     * @group extract data
     */
    public function getTextAsMiniOcr(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/tei.xml');
        $alto = new Tei();

        $rawText = $alto->getTextAsMiniOcr($xml);

        $miniOCR = <<<XML
        <ocr>
            <b>
                <l>
                    <w x="477 2083 437 95">B&#xFC;rgertum </w>
                    <w x="950 2076 155 76">und </w>
                </l>
                <l>
                    <w x="477 2201 574 102">B&#xFC;rgerlichkeit </w>
                    <w x="1084 2205 74 68">in </w>
                    <w x="1194 2199 333 75">Dresden </w>
                </l>
            </b>
            <b>
                <l>
                    <w x="473 315 752 98">DRESDNER </w>
                </l>
                <l>
                    <w x="473 492 448 97">HEFTE </w>
                </l>
            </b>
        </ocr>
        XML;

        self::assertXmlStringEqualsXmlString($miniOCR, $rawText);
    }

}
