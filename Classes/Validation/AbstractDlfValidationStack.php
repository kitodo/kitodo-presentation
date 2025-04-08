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
use TYPO3\CMS\Extbase\Error\Notice;
use TYPO3\CMS\Extbase\Error\Warning;
use TYPO3\CMS\Extbase\Validation\Error;

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
            $this->logger->error($validator . ' must be an instance of AbstractDlfValidator.');
            throw new InvalidArgumentException($validator . 'must be an instance of AbstractDlfValidator.', 1723121212747);
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

        if (!$this->hasValidators()) {
            $this->logger->error('The validation stack has no validator.');
            throw new InvalidArgumentException('The validation stack has no validator.', 1724662426);
        }

        foreach ($this->validators as $index => $validator) {
            $validatorResult = $validator->validate($value);
            $stackResult = $this->result->forProperty(strval($index));
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

    public function hasValidators(): bool
    {
        return !empty($this->validators);
    }

    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * @param $className
     * @param $message
     * @param $code
     * @param array $arguments
     * @param $title
     * @return void
     */
    protected function addErrorForValidator($className, $message, $code, array $arguments = [], $title = '')
    {
        $this->result->forProperty($className)->addError(new Error((string)$message, (int)$code, $arguments, (string)$title));
    }

    /**
     * @param $className
     * @param $message
     * @param $code
     * @param array $arguments
     * @param $title
     * @return void
     */
    protected function addWarningForValidator($className, $message, $code, array $arguments = [], $title = '')
    {
        $this->result->forProperty($className)->addWarning(new Warning((string)$message, (int)$code, $arguments, (string)$title));
    }

    /**
     * @param $className
     * @param $message
     * @param $code
     * @param array $arguments
     * @param $title
     * @return void
     */
    protected function addNoticeForValidator($className, $message, $code, array $arguments = [], $title = '')
    {
        $this->result->forProperty($className)->addNotice(new Notice((string)$message, (int)$code, $arguments, (string)$title));
    }

}
