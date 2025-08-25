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
        $this->expectExceptionMessage("Validator must be an instance of AbstractDlfValidator.");
        new DOMDocumentValidationStack([["className" => UrlValidator::class]]);
    }
}
