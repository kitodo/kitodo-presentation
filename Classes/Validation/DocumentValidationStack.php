<?php

namespace Kitodo\Dlf\Validation;

class DocumentValidationStack extends BaseValidationStack
{

    public function __construct(array $configuration, array $options = [])
    {
        parent::__construct(\DOMDocument::class, $options);
        $this->initialize($configuration);
    }

    /**
     * @param array $configuration
     * @return void
     */
    public function initialize(array $configuration): void
    {
        foreach ($configuration as $configurationItem) {
            if (!class_exists($configurationItem["className"])) {
                throw new \InvalidArgumentException('Unable to load class ' . $configurationItem["className"] . '.', 1723200537037);
            }
            $breakOnError = !isset($configurationItem["breakOnError"]) || $configurationItem["breakOnError"] !== false;
            $this->addValidationItem($configurationItem["className"], $configurationItem["title"], $breakOnError, $configurationItem["configuration"]);
        }
    }

}
