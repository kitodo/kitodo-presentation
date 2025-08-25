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

namespace Kitodo\Dlf\Tests\Unit\Common;

use Kitodo\Dlf\Validation\DocumentValidator;
use SimpleXMLElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DocumentValidatorTest extends UnitTestCase
{
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

    /**
     * @test
     */
    public function passesHasAllMandatoryMetadataFields()
    {
        $metadata = [
            'type' => [
                'newspaper'
            ],
            'record_id' => [
                'xyz'
            ],
            'is_administrative' => [
                true
            ]
        ];
        $documentValidator = new DocumentValidator($metadata, $this->getRequiredMetadataFields());
        self::assertTrue($documentValidator->hasAllMandatoryMetadataFields());
    }

    /**
     * @test
     */
    public function passesHasNotMandatoryMetadataFieldsButType()
    {
        $metadata = [
            'type' => [
                'chapter'
            ],
            'is_administrative' => [
                false
            ]
        ];
        $documentValidator = new DocumentValidator($metadata, $this->getRequiredMetadataFields());
        self::assertTrue($documentValidator->hasAllMandatoryMetadataFields());
    }

    /**
     * @test
     */
    public function notPassesHasAllMandatoryMetadataFields()
    {
        $metadata = [
            'document_format' => [
                'METS'
            ],
            'type' => [
                'newspaper'
            ],
            'is_administrative' => [
                true
            ]
        ];
        $documentValidator = new DocumentValidator($metadata, $this->getRequiredMetadataFields());
        self::assertFalse($documentValidator->hasAllMandatoryMetadataFields());
    }

    /**
     * @test
     */
    public function passesHasCorrectLogicalStructure()
    {
        $xml = $this->getXml('av_beispiel.xml');

        $documentValidator = new DocumentValidator([], [], $xml);
        self::assertTrue($documentValidator->hasCorrectLogicalStructure('advertisement'));
    }

    /**
     * @test
     */
    public function notPassesHasCorrectLogicalStructure()
    {
        $xml = $this->getXml('av_beispiel.xml');

        $documentValidator = new DocumentValidator([], [], $xml);
        self::assertFalse($documentValidator->hasCorrectLogicalStructure('newspaper'));
    }

    /**
     * @test
     */
    public function passesHasCorrectPhysicalStructure()
    {
        $xml = $this->getXml('av_beispiel.xml');

        $documentValidator = new DocumentValidator([], [], $xml);
        self::assertTrue($documentValidator->hasCorrectPhysicalStructure());
    }

    /**
     * @test
     */
    public function notPassesHasCorrectPhysicalStructure()
    {
        $xml = $this->getXml('two_dmdsec.xml');

        $documentValidator = new DocumentValidator([], [], $xml);
        self::assertFalse($documentValidator->hasCorrectPhysicalStructure());
    }

    /**
     * Returns an array of required metadata fields for validation.
     *
     * @access private
     *
     * @return array The required metadata fields.
     */
    private function getRequiredMetadataFields(): array
    {
        return [
            'record_id'
        ];
    }

    /**
     * Loads an XML file from the fixtures directory and returns it as a SimpleXMLElement.
     *
     * @access private
     *
     * @param string $file The name of the XML file to load.
     *
     * @return SimpleXMLElement The loaded XML as a SimpleXMLElement object.
     */
    private function getXml(string $file): SimpleXMLElement
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/MetsDocument/' . $file);
        self::assertNotFalse($xml);
        return $xml;
    }
}
