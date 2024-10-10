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

use DOMDocument;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use SimpleXMLElement;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The validator validates the DOMDocument against an XSL Schematron and converts error output to validation errors.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SaxonXslToSvrlValidator extends AbstractDlfValidator implements LoggerAwareInterface
{
    private string $jar;

    private string $xsl;

    public function __construct(array $configuration)
    {
        parent::__construct(DOMDocument::class);
        $this->jar = GeneralUtility::getFileAbsFileName($configuration["jar"] ?? '');
        $this->xsl = GeneralUtility::getFileAbsFileName($configuration["xsl"] ?? '');
        if (empty($this->jar)) {
            $this->logger->error('Saxon JAR file not found.');
            throw new InvalidArgumentException('Saxon JAR file not found.', 1723121212747);
        }
        if (empty($this->xsl)) {
            $this->logger->error('XSL Schematron file not found.');
            throw new InvalidArgumentException('XSL Schematron file not found.', 1723121212747);
        }
    }

    protected function isValid($value): void
    {
        $svrl = $this->process($value);
        $this->addErrorsOfSvrl($svrl);
    }

    /**
     * @param mixed $value
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function process(mixed $value): string
    {
        // using source from standard input
        $process = new Process(['java', '-jar', $this->jar, '-xsl:' . $this->xsl, '-s:-'], null, null, $value->saveXML());
        $process->run();
        // executes after the command finish
        if (!$process->isSuccessful()) {
            $this->logger->error('Processing exits with code "' . $process->getExitCode() . '"');
            throw new InvalidArgumentException('Processing was not successful.', 1724862680);
        }
        return $process->getOutput();
    }

    /**
     * Add errors of schematron output.
     *
     * @param string $svrl
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function addErrorsOfSvrl(string $svrl): void
    {
        try {
            $xml = new SimpleXMLElement($svrl);
            $results = $xml->xpath("/svrl:schematron-output/svrl:failed-assert/svrl:text");

            foreach ($results as $error) {
                $this->addError($error->__toString(), 1724405095);
            }
        } catch (Exception $e) {
            throw new InvalidArgumentException('Schematron output XML could not be parsed.', 1724754882);
        }
    }
}
