<?php

namespace Kitodo\Dlf\Validation;

use SimpleXMLElement;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class SaxonValidator extends BaseValidator {

    private string $jar;

    private string $xsl;

    public function __construct(array $configuration)
    {
        // sudo apt-get update
        // sudo apt install default-jdk

        parent::__construct(\DOMDocument::class);
        $this->jar = $configuration["jar"];
        $this->xsl = $configuration["xsl"];
    }

    protected function isValid($value)
    {
        // using source from standard input
        $process = new Process(['java','-jar', $this->jar, '-xsl:'.$this->xsl, '-s:-', '<(echo "'.$value->saveXML().'")']);

        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $xml = new SimpleXMLElement($process->getOutput());
        $results = $xml->xpath("/svrl:schematron-output/svrl:failed-assert[@role = 'error']/svrl:text");

        foreach ($results as $error) {
            $this->addError($error, 31);
        }
    }

}
