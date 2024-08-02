<?php

namespace Kitodo\Dlf\Validation;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;

class XmlValidator extends AbstractValidator
{
    private array $validatorStack;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->validatorStack = [
            UrlValidator::class,
            SaxonValidator::class
        ];
    }

    protected function isValid($value): void
    {
        foreach ($this->validatorStack as $validator) {
            $result = GeneralUtility::makeInstance($validator)->validate($value);
            foreach ($result->getErrors() as $error) {
                $this->addError($error->getMessage(), $error->getCode(), [], $validator);
            }
        }
    }

}
