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
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Search in document Middleware for plugin 'Search' of the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
abstract class BaseValidator extends AbstractValidator
{
    private $value;

    private $valueClassName;

    public function __construct($valueClassName)
    {
        parent::__construct();
        $this->valueClassName = $valueClassName;
    }

    public function setValue($value): void
    {
        if (!$value instanceof $this->valueClassName) {
            throw new \InvalidArgumentException('Value must be an instance of ' . $this->valueClassName . '.', 1723126505626);
        }
        $this->value = $value;
    }

    /**
     * @return Result
     */
    public function validateValue(): Result
    {
        if (!$this->value) {
            throw new \InvalidArgumentException('No value set for validation.', 1723126168704);
        }

        return $this->validate($this->value);
    }

    protected function addLibXmlErrors() {
        $errors = libxml_get_errors();

        foreach ($errors as $error) {
            $this->addError($error->message, $error->code);
        }

        libxml_clear_errors();
    }

}
