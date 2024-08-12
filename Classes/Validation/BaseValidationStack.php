<?php

namespace Kitodo\Dlf\Validation;

use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class BaseValidationStack extends BaseValidator
{
    const ITEM_KEY_TITLE = "title";
    const ITEM_KEY_BREAK_ON_ERROR = "breakOnError";
    const ITEM_KEY_VALIDATOR = "validator";

    protected array $validatorStack;

    private $valueClassName;

    public function __construct($valueClassName, array $options = [])
    {
        parent::__construct($options);
        $this->valueClassName = $valueClassName;
    }

    protected function addValidationItem(string $className, string $title, bool $breakOnError = true, array $configuration): void
    {

        $validator = GeneralUtility::makeInstance($className,$configuration);

        if (!$validator instanceof BaseValidator) {
            throw new \InvalidArgumentException('$className must be an instance of BaseValidator.', 1723121212747);
        }
        $this->validatorStack[] = array(
            self::ITEM_KEY_TITLE => $title,
            self::ITEM_KEY_VALIDATOR => $validator,
            self::ITEM_KEY_BREAK_ON_ERROR => $breakOnError,
        );
    }

    protected function isValid($value): void
    {
        if (!$value instanceof $this->valueClassName) {
            throw new \InvalidArgumentException('Value must be an instance of ' . $this->valueClassName . '.', 1723127564821);
        }

        foreach ($this->validatorStack as $validationStackItem) {
            $result = $validationStackItem[self::ITEM_KEY_VALIDATOR]->validate($value);
            foreach ($result->getErrors() as $error) {
                $this->addError($error->getMessage(), $error->getCode(), [], $validationStackItem[self::ITEM_KEY_TITLE]);
            }
            if ($validationStackItem[self::ITEM_KEY_BREAK_ON_ERROR] && $result->hasErrors()) {
                break;
            }
        }
    }
}
