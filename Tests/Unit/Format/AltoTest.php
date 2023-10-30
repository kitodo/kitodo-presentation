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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AltoTest extends UnitTestCase
{
    /**
     * @test
     * @group extract data
     */
    public function getRawData(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/alto.xml');
        $alto = new Alto();

        $rawText = $alto->getRawText($xml);

        self::assertEquals('Bürgertum und Bürgerlichkeit in Dresden DRESDNER HEFTE', $rawText);
    }

    /**
     * @test
     * @group extract data
     */
    public function getTextAsMiniOcr(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/alto.xml');
        $alto = new Alto();

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

    /**
     * @test
     * @group extract data
     */
    public function getTextAsMiniOcrNoTextBlock(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/altoNoTextBlock.xml');
        $alto = new Alto();

        $rawText = $alto->getTextAsMiniOcr($xml);

        self::assertEquals('', $rawText);
    }

    /**
     * @test
     * @group extract data
     */
    public function getTextAsMiniOcrNoTextline(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/altoNoTextLine.xml');
        $alto = new Alto();

        $rawText = $alto->getTextAsMiniOcr($xml);

        self::assertXmlStringEqualsXmlString('<?xml version="1.0"?><ocr><b/><b/></ocr>', $rawText);
    }

    /**
     * @test
     * @group extract data
     */
    public function getTextAsMiniOcrNoString(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/altoNoString.xml');
        $alto = new Alto();

        $rawText = $alto->getTextAsMiniOcr($xml);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0"?><ocr><b><l/><l/></b><b><l/><l/></b></ocr>',
            $rawText
        );
    }
}
