<?php

namespace Kitodo\Dlf\Validation;

use \TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

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
