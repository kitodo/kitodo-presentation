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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class provides functions for implementing a validation stack.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
abstract class BaseValidationStack extends BaseValidator
{
    const ITEM_KEY_TITLE = "title";
    const ITEM_KEY_BREAK_ON_ERROR = "breakOnError";
    const ITEM_KEY_VALIDATOR = "validator";

    protected array $validatorStack;

    private string $valueClassName;

    public function __construct(string $valueClassName, array $options = [])
    {
        parent::__construct($options);
        $this->valueClassName = $valueClassName;
    }

    /**
     * Add validators by validation stack configuration to the internal validator stack.
     *
     * @param array $configuration The configuration of validators
     * @return void
     */
    public function addValidators(array $configuration): void
    {
        foreach ($configuration as $configurationItem) {
            if (!class_exists($configurationItem["className"])) {
                throw new \InvalidArgumentException('Unable to load class ' . $configurationItem["className"] . '.', 1723200537037);
            }
            $breakOnError = !isset($configurationItem["breakOnError"]) || $configurationItem["breakOnError"] !== "false";
            $this->addValidator($configurationItem["className"], $configurationItem["title"], $breakOnError, $configurationItem["configuration"]);
        }
    }

    /**
     * Add validator to the internal validator stack.
     *
     * @param string $className Class name of the validator which was derived from Kitodo\Dlf\Validation\BaseValidator
     * @param string $title The title of the validator
     * @param bool $breakOnError True if the execution of validator stack is interrupted when validator throws an error
     * @param array|null $configuration The configuration of validator
     * @return void
     */
    protected function addValidator(string $className, string $title, bool $breakOnError = true, array $configuration = null): void
    {
        if ($configuration === null) {
            $validator = GeneralUtility::makeInstance($className);
        } else {
            $validator = GeneralUtility::makeInstance($className, $configuration);
        }

        if (!$validator instanceof BaseValidator) {
            throw new \InvalidArgumentException($className . ' must be an instance of BaseValidator.', 1723121212747);
        }

        $this->validatorStack[] = array(
            self::ITEM_KEY_TITLE => $title,
            self::ITEM_KEY_VALIDATOR => $validator,
            self::ITEM_KEY_BREAK_ON_ERROR => $breakOnError,
        );
    }

    /**
     * Check if value is valid across all validation classes of validation stack.
     *
     * @param $value mixed The value of defined class name.
     * @return void
     */
    protected function isValid(mixed $value): void
    {
        if (!$value instanceof $this->valueClassName) {
            throw new \InvalidArgumentException('Value must be an instance of ' . $this->valueClassName . '.', 1723127564821);
        }

        foreach ($this->validatorStack as $validationStackItem) {
            $validator = $validationStackItem[self::ITEM_KEY_VALIDATOR];

            // check whether the validator supports the class name of the value
            $result = $validator->setValue($value)->validateValue();

            foreach ($result->getErrors() as $error) {
                $this->addError($error->getMessage(), $error->getCode(), [], $validationStackItem[self::ITEM_KEY_TITLE]);
            }

            if ($validationStackItem[self::ITEM_KEY_BREAK_ON_ERROR] && $result->hasErrors()) {
                break;
            }
        }
    }
}