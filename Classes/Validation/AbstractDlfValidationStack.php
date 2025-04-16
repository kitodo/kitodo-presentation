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

use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class provides functions for implementing a validation stack.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
abstract class AbstractDlfValidationStack extends AbstractDlfValidator
{
    use LoggerAwareTrait;

    protected array $validators = [];

    public function __construct(string $valueClassName)
    {
        parent::__construct($valueClassName);
    }

    /**
     * Add validators by validation stack configuration to the internal validator stack.
     *
     * @param array $configuration The configuration of validators
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function addValidators(array $configuration): void
    {
        foreach ($configuration as $configurationItem) {
            if (!class_exists($configurationItem["className"])) {
                $this->logger->error('Unable to load class ' . $configurationItem["className"] . '.');
                throw new InvalidArgumentException('Unable to load validator class.', 1723200537037);
            }
            $this->addValidator($configurationItem["className"], $configurationItem["configuration"] ?? []);
        }
    }

    /**
     * Add validator to the internal validator stack.
     *
     * @param string $className Class name of the validator which was derived from Kitodo\Dlf\Validation\AbstractDlfValidator
     * @param array|null $configuration The configuration of validator
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    protected function addValidator(string $className, ?array $configuration = null): void
    {
        if ($configuration === null) {
            $validator = GeneralUtility::makeInstance($className);
        } else {
            $validator = GeneralUtility::makeInstance($className, $configuration);
        }

        if (!$validator instanceof AbstractDlfValidator) {
            $this->logger->error('Validator of class "' . $className . '" must be an instance of AbstractDlfValidator.');
            throw new InvalidArgumentException('Validator must be an instance of AbstractDlfValidator.', 1723121212747);
        }

        $this->validators[] = $validator;
    }

    /**
     * Check if value is valid across all validation classes of validation stack.
     *
     * @param $value The value of defined class name.
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    protected function isValid($value): void
    {
        if (!$value instanceof $this->valueClassName) {
            $this->logger->error('Value must be an instance of ' . $this->valueClassName . '.');
            throw new InvalidArgumentException('Type of value is not valid.', 1723127564821);
        }

        if (empty($this->validators)) {
            $this->logger->error('The validation stack has no validator.');
            throw new InvalidArgumentException('The validation stack has no validator.', 1724662426);
        }

        foreach ($this->validators as $index => $validator) {
            $validatorResult = $validator->validate($value);
            $stackResult = $this->result->forProperty((string) $index);
            if ($validatorResult->hasErrors()) {
                foreach ($validatorResult->getErrors() as $error) {
                    $stackResult->addError($error);
                }
            }
            if ($validatorResult->hasWarnings()) {
                foreach ($validatorResult->getWarnings() as $warning) {
                    $stackResult->addWarning($warning);
                }
            }
            if ($validatorResult->hasNotices()) {
                foreach ($validatorResult->getNotices() as $notice) {
                    $stackResult->addNotice($notice);
                }
            }
        }
    }
}
