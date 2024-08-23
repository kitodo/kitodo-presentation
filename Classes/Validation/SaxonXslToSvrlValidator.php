<?php

declare(strict_types=1);

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Validation;

use SimpleXMLElement;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * The validator validates the DOMDocument against an XSL Schematron and converts error output to validation errors.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SaxonXslToSvrlValidator extends BaseValidator {

    private string $jar;

    private string $xsl;

    public function __construct(array $configuration)
    {
        parent::__construct(\DOMDocument::class);
        $this->jar = $configuration["jar"];
        $this->xsl = $configuration["xsl"];
    }

    protected function isValid($value)
    {
        // using source from standard input
        $process = new Process(['java','-jar', $this->jar, '-xsl:'.$this->xsl, '-s:-'], null, null, $value->saveXML());

        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $xml = new SimpleXMLElement($process->getOutput());
        $results = $xml->xpath("/svrl:schematron-output/svrl:failed-assert[@role = 'error']/svrl:text");

        foreach ($results as $error) {
            $this->addError($error, 1724405095);
        }
    }

}
