<?php

namespace Kitodo\Dlf\Validation;

class XmlSchemesValidator extends BaseValidator
{
    private $schemes;

    public function __construct(array $configuration)
    {
        parent::__construct(\DOMDocument::class);
        $this->schemes = $configuration;
    }

    public function isSchemeValid($value): bool
    {
        $xsd = '<?xml version="1.0" encoding="utf-8"?><xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">';
        foreach ($this->schemes as $scheme) {
            $xsd .= '<xs:import namespace="'.$scheme["namespace"].'" schemaLocation="'.$scheme["schemaLocation"].'"/>';
        }
        $xsd .= '</xs:schema>';
        return $value->schemaValidateSource($xsd);
    }

    protected function isValid($value): void
    {
        libxml_use_internal_errors(true);
        if (!$this->isSchemeValid($value)) {
            $this->addLibXmlErrors();
        }
        libxml_use_internal_errors(false);
    }

}
