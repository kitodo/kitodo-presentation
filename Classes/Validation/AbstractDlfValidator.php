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
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Notice;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Error\Warning;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Base Validator provides functionalities for using the derived validator within a validation stack.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
abstract class AbstractDlfValidator extends AbstractValidator
{
    use LoggerAwareTrait;

    protected string $valueClassName;

    /**
     * @param $valueClassName string The class name of the value
     */
    public function __construct(string $valueClassName)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->valueClassName = $valueClassName;
    }

    public function validate($value): Result
    {
        if (!$value instanceof $this->valueClassName) {
            $this->logger->debug('Value must be an instance of ' . $this->valueClassName . '.');
            throw new InvalidArgumentException('Type of value is not valid.', 1723126505626);
        }
        return parent::validate($value);
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
