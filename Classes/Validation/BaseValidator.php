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

namespace Kitodo\Dlf\Validation;

use \TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Base Validator provides functionalities for using the derived validator within a validation stack.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
abstract class BaseValidator extends AbstractValidator
{
    private mixed $value;

    private string $valueClassName;

    /**
     * @param $valueClassName string The class name of the value
     * @throws InvalidValidationOptionsException
     */
    public function __construct(string $valueClassName)
    {
        parent::__construct();
        $this->valueClassName = $valueClassName;
    }

    /**
     * Set the value that needs to be validated.
     *
     * @param $value mixed The value of type value class name.
     * @return void
     */
    public function setValue(mixed $value): void
    {
        if (!$value instanceof $this->valueClassName) {
            throw new \InvalidArgumentException('Value must be an instance of ' . $this->valueClassName . '.', 1723126505626);
        }
        $this->value = $value;
    }

    /**
     * Validate the configured value.
     *
     * @return Result The validation result
     */
    public function validateValue(): Result
    {
        if (!$this->value) {
            throw new \InvalidArgumentException('No value set for validation.', 1723126168704);
        }

        return $this->validate($this->value);
    }
}
