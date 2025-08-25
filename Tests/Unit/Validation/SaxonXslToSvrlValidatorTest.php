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

use InvalidArgumentException;
use Kitodo\Dlf\Validation\SaxonXslToSvrlValidator;
use ReflectionClass;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing the SaxonXslToSvrlValidator class.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SaxonXslToSvrlValidatorTest extends UnitTestCase
{
    const SVRL = <<<SVRL
        <svrl:schematron-output
            xmlns:svrl="http://purl.oclc.org/dsdl/schematron">
            <svrl:failed-assert
                        test="year &gt; 1900"
                        location="book.xml">
                <svrl:text>The year must be greater than 1900.</svrl:text>
            </svrl:failed-assert>
        </svrl:schematron-output>
    SVRL;

    private string $dlfExtensionPath;

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
        $this->dlfExtensionPath = Environment::getExtensionsPath() . 'dlf';
    }

    public function testJarFileNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Saxon JAR file not found.");
        new SaxonXslToSvrlValidator([]);
    }

    public function testXslFileNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("XSL Schematron file not found.");
        // It only checks if a file exists at the specified path, so we can use one of the test files.
        new SaxonXslToSvrlValidator(["jar" => $this->dlfExtensionPath . '/Tests/Fixtures/Format/alto.xml']);
    }

    public function testValidation(): void
    {
        $saxonXslToSvrlValidator = new SaxonXslToSvrlValidator(["jar" => $this->dlfExtensionPath . '/Tests/Fixtures/Format/alto.xml', "xsl" => $this->dlfExtensionPath . '/Tests/Fixtures/Format/alto.xml']);
        $reflection = new ReflectionClass($saxonXslToSvrlValidator);

        $result = $reflection->getProperty("result");
        $result->setAccessible(true);
        $result->setValue($saxonXslToSvrlValidator, new Result());

        $method = $reflection->getMethod("addErrorsOfSvrl");
        $method->setAccessible(true);
        $method->invoke($saxonXslToSvrlValidator, self::SVRL);

        self::assertTrue($result->getValue($saxonXslToSvrlValidator)->hasErrors());
        self::assertEquals("The year must be greater than 1900.", $result->getValue($saxonXslToSvrlValidator)->getErrors()[0]->getMessage());
    }
}
