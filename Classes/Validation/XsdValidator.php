<?php

namespace Kitodo\Dlf\Validation;

class XsdValidator extends BaseValidator
{
    private $schema;

    public function __construct(array $configuration)
    {
        parent::__construct(\DOMDocument::class);
        $this->schema = $configuration["schema"];
    }

    protected function isValid($value): void
    {
        libxml_use_internal_errors(true);
        if (!$value->schemaValidate($this->schema)) {
            $this->addLibXmlErrors();
        }
        libxml_use_internal_errors(false);
    }

}
