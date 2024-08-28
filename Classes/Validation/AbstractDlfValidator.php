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

    protected string $valueClassName;

    /**
     * @param $valueClassName string The class name of the value
     */
    public function __construct(string $valueClassName)
    {
        parent::__construct();
        $this->valueClassName = $valueClassName;
    }

    public function validate($value)
    {
        if (!$value instanceof $this->valueClassName) {
            throw new InvalidArgumentException('Value must be an instance of ' . $this->valueClassName . '.', 1723126505626);
        }
        return parent::validate($value);
    }
}
