<?php

namespace Kitodo\Dlf\Validation;

use SimpleXMLElement;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class SaxonValidator extends AbstractValidator {

    protected function isValid($value)
    {
        $path = "/var/www/html/public/typo3conf/ext/dlf/Resources/Private/Saxon/";
        $process = new Process(['java','-jar', $path.'saxon-he-10.6.jar', '-xsl:'.$path.'ddb_validierung_mets-mods-ap-digitalisierte-medien.xsl', '-s:'.$path.'mets.xml']);

       // $process = new Process(['/usr/bin/java -jar '. $path .'saxon-he-10.6.jar -xsl:ddb_validierung_mets-mods-ap-digitalisierte-medien.xsl -s:mets.xml']);
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
