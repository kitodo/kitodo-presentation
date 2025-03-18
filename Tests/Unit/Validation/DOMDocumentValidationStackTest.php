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
use InvalidArgumentException;
use Kitodo\Dlf\Validation\DOMDocumentValidationStack;
use Kitodo\Dlf\Validation\XmlSchemesValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing AbstractDlfValidatorStack with implementation of DOMDocumentValidationStack.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DOMDocumentValidationStackTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
    }

    public function testValueTypeException(): void
    {
        $domDocumentValidationStack = new DOMDocumentValidationStack([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Type of value is not valid.");
        $domDocumentValidationStack->validate("");
    }

    public function testEmptyValidationStack(): void
    {
        $domDocumentValidationStack = new DOMDocumentValidationStack([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The validation stack has no validator.");
        $domDocumentValidationStack->validate(new DOMDocument());
    }

    public function testValidatorClassNotExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to load validator class.");
        new DOMDocumentValidationStack([["className" => "Kitodo\Tests\TestValidator"]]);
    }

    public function testValidatorDerivation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class must be an instance of AbstractDlfValidator.");
        new DOMDocumentValidationStack([["className" => UrlValidator::class]]);
    }

    public function testBreakOnError(): void
    {
        $xsdFile = __DIR__ . '/../../Fixtures/OaiPmh/OAI-PMH.xsd';
        $xmlSchemesValidatorConfig = [
            'className' => XmlSchemesValidator::class,
            'configuration' => [
                [
                    'namespace' => 'http://www.openarchives.org/OAI/2.0/',
                    'schemaLocation' => $xsdFile
                ]
            ]
        ];
        $domDocumentValidationStack = new DOMDocumentValidationStack([$xmlSchemesValidatorConfig, $xmlSchemesValidatorConfig]);
        $result = $domDocumentValidationStack->validate(new DOMDocument());
        self::assertCount(1, $result->getErrors());

        // disable breaking on error
        $xmlSchemesValidatorConfig["breakOnError"] = "false";
        $domDocumentValidationStack = new DOMDocumentValidationStack([$xmlSchemesValidatorConfig, $xmlSchemesValidatorConfig]);
        $result = $domDocumentValidationStack->validate(new DOMDocument());
        self::assertCount(2, $result->getErrors());
    }
}
