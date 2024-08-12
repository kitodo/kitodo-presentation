<?php

namespace Kitodo\Dlf\Validation;

class ApplicationProfileValidator extends BaseValidationStack {


    public function __construct(array $options = [])
    {
        parent::__construct($options);

        // XSD Validierung
        // Spezifische Validierung mittels PHP
        // $this->addValidationItem();
    }

}
