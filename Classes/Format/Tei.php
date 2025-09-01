<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Format;

use Kitodo\Dlf\Common\FulltextInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SimpleXMLElement;

/**
 * Fulltext ALTO format class for the 'dlf' extension
 *
 * ** This currently supports ALTO 2.x / 3.x / 4.x **
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Tei implements FulltextInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $pageId;

    public function setPageId(string $pageId): void
    {
        $this->pageId = $pageId;
    }

    /**
     * This extracts the fulltext data from TEI XML
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml The XML to extract the raw text from
     *
     * @return string The raw unformatted fulltext
     */
    public function getRawText(\SimpleXMLElement $xml): string
    {
        if (empty($this->pageId)) {
            $this->logger->warning('Text could not be retrieved from TEI because the page ID is empty.');
            return '';
        }

        // register ALTO namespace depending on document
        $this->registerTeiNamespace($xml);

        // Get all (presumed) words of the text.
        $contentXml = $xml->xpath('./TEI:text')[0]->asXML();

        // Remove tags but keep their content
        $contentXml = preg_replace('/<\/?(?:body|front|div|head|titlePage)[^>]*>/u', '', $contentXml);

        // Replace linebreaks
        $contentXml = preg_replace('/<lb(?:\s[^>]*)?\/>/u', '', $contentXml);
        $contentXml = preg_replace('/\s+/', ' ', $contentXml);

        // Extract content between each <pb /> and the next <pb /> or end of string
        $pattern = '/<pb[^>]*facs="([^"]+)"[^>]*\/>([\s\S]*?)(?=<pb[^>]*\/>|$)/u';
        $facs = [];

        // Use preg_match_all to get all matches at once
        if (preg_match_all($pattern, $contentXml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $facsMatch = trim($match[1]);
                $facsId = str_starts_with($facsMatch, "#") ? substr($facsMatch, 1) : $facsMatch;
                $facs[$facsId] = trim(strip_tags($match[2])); // Everything until next <pb /> or end of string
            }
        }

        if (!array_key_exists($this->pageId, $facs)) {
            $this->logger->debug('The page break attribute "facs" with the page identifier postfix "' . $this->pageId . '" could not be found in the TEI document');
            return '';
        }

        return $facs[$this->pageId];
    }

    /**
     * This extracts the fulltext data from TEI XML and returns it in MiniOCR format
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml The XML to extract the raw text from
     *
     * @return string The unformatted fulltext in MiniOCR format
     */
    public function getTextAsMiniOcr(\SimpleXMLElement $xml): string
    {
        $rawText = $this->getRawText($xml);

        if (empty($rawText)) {
            return '';
        }

        $miniOcr = new SimpleXMLElement("<ocr></ocr>");
        $miniOcr->addChild('b', $rawText);
        $miniOcrXml = $miniOcr->asXml();
        if (\is_string($miniOcrXml)) {
            return $miniOcrXml;
        }
        return '';
    }

    /**
     * This registers the necessary TEI namespace for the current TEI-XML
     *
     * @access private
     *
     * @param \SimpleXMLElement &$xml: The XML to register the namespace for
     */
    private function registerTeiNamespace(\SimpleXMLElement $xml)
    {
        $namespace = $xml->getDocNamespaces();

        if (in_array('http://www.tei-c.org/ns/1.0', $namespace, true)) {
            $xml->registerXPathNamespace('TEI', 'http://www.tei-c.org/ns/1.0');
        }
    }
}
