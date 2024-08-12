<?php

namespace Kitodo\Dlf\Validation;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class XmlValidator extends AbstractValidator
{

    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    protected function isValid($value)
    {
        libxml_use_internal_errors(true);

        simplexml_load_file($value);

        $errors = libxml_get_errors();

        foreach ($errors as $error) {
            $this->addError($error->message, $error->code, [], "XmlValidator");
        }

        libxml_clear_errors();
    }
}
