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
use TYPO3\CMS\Extbase\Error\Result;
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
}
