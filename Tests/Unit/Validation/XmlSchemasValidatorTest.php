<?php

declare(strict_types=1);

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Tests\Unit\Validation;

use DOMDocument;
use Kitodo\Dlf\Validation\XmlSchemasValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing the XmlSchemesValidator class.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class XmlSchemasValidatorTest extends UnitTestCase
{
    const METS = <<<METS
        <mets:mets
            xmlns:mets="http://www.loc.gov/METS/"
            xmlns:mods="http://www.loc.gov/mods/v3" >
            <mets:metsHdr></mets:metsHdr>
            <mets:amdSec></mets:amdSec>
            <mets:fileSec>
                <mets:fileGrp></mets:fileGrp>
            </mets:fileSec>
            <mets:structMap>
                <mets:div></mets:div>
            </mets:structMap>
        </mets:mets>
    METS;

    const METS_MODS = <<<METS_MODS
        <mets:mets
            xmlns:mets="http://www.loc.gov/METS/"
            xmlns:mods="http://www.loc.gov/mods/v3" >
            <mets:metsHdr></mets:metsHdr>
            <mets:dmdSec ID="DMD1">
                <mets:mdWrap MDTYPE="MODS">
                    <mets:xmlData>
                        <mods:mods>
                            <mods:titleInfo></mods:titleInfo>
                        </mods:mods>
                    </mets:xmlData>
                </mets:mdWrap>
            </mets:dmdSec>
            <mets:amdSec></mets:amdSec>
            <mets:fileSec>
                <mets:fileGrp></mets:fileGrp>
            </mets:fileSec>
            <mets:structMap>
                <mets:div></mets:div>
            </mets:structMap>
        </mets:mets>
    METS_MODS;

    /**
     * Sets up the test case environment.
     *
     * @access public
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
    }

    public function testValidation(): void
    {
        $xmlSchemesValidator = new XmlSchemasValidator(
            [["namespace" => "http://www.loc.gov/METS/", "schemaLocation" => "http://www.loc.gov/standards/mets/mets.xsd"], ["namespace" => "http://www.loc.gov/mods/v3", "schemaLocation" => "http://www.loc.gov/standards/mods/mods.xsd"]]
        );

        $domDocument = new DOMDocument();
        // Test with empty document
        $result = $xmlSchemesValidator->validate($domDocument);
        self::assertCount(1, $result->getErrors());

        $domDocument->loadXML(self::METS);
        $result = $xmlSchemesValidator->validate($domDocument);
        self::assertFalse($result->hasErrors());

        $domDocument->loadXML(self::METS_MODS);
        $result = $xmlSchemesValidator->validate($domDocument);
        self::assertFalse($result->hasErrors());

        // Test with wrong mets element
        $domDocument->loadXML(str_replace("mets:metsHdr", "mets:Hdr", self::METS));
        $result = $xmlSchemesValidator->validate($domDocument);
        self::assertTrue($result->hasErrors());

        // Test with wrong mods element
        $domDocument->loadXML(str_replace("mods:titleInfo", "mods:title", self::METS_MODS));

        $result = $xmlSchemesValidator->validate($domDocument);
        self::assertTrue($result->hasErrors());
    }
}
