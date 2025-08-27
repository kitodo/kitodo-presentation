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
class Tei implements \Kitodo\Dlf\Common\FulltextInterface
{
    /**
     * This extracts the fulltext data from ALTO XML
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml The XML to extract the raw text from
     *
     * @return string The raw unformatted fulltext
     */
    public function getRawText(\SimpleXMLElement $xml): string
    {
        $rawText = '';

        // register ALTO namespace depending on document
        $this->registerTeiNamespace($xml);

        // Get all (presumed) words of the text.
        $strings = $xml->xpath('./TEI:text/TEI:body//TEI:head');
        $words = [];
        if (!empty($strings)) {
            for ($i = 0; $i < count($strings); $i++) {
                $attributes = $strings[$i]->attributes();
                if (isset($attributes['SUBS_TYPE'])) {
                    if ($attributes['SUBS_TYPE'] == 'HypPart1') {
                        $i++;
                        $words[] = $attributes['SUBS_CONTENT'];
                    }
                } else {
                    $words[] = $attributes['CONTENT'];
                }
            }
            $rawText = implode(' ', $words);
        }
        return $strings[0];
    }

    /**
     * This extracts the fulltext data from ALTO XML and returns it in MiniOCR format
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml The XML to extract the raw text from
     *
     * @return string The unformatted fulltext in MiniOCR format
     */
    public function getTextAsMiniOcr(\SimpleXMLElement $xml): string
    {
        // register ALTO namespace depending on document
        $this->registerTeiNamespace($xml);

        // get all text blocks
        $blocks = $xml->xpath('./alto:Layout/alto:Page/alto:PrintSpace//alto:TextBlock');

        if (empty($blocks)) {
            return '';
        }

        $miniOcr = new \SimpleXMLElement("<ocr></ocr>");

        foreach ($blocks as $block) {
            $newBlock = $miniOcr->addChild('b');
            foreach ($block->children() as $key => $value) {
                if ($key === "TextLine") {
                    $newLine = $newBlock->addChild('l');
                    foreach ($value->children() as $wordKey => $word) {
                        if ($wordKey == "String") {
                            $attributes = $word->attributes();
                            $newWord = $newLine->addChild('w', $this->getWord($attributes));
                            $newWord->addAttribute('x', $this->getCoordinates($attributes));
                        }
                    }
                }
            }
        }

        $miniOcrXml = $miniOcr->asXml();
        if (\is_string($miniOcrXml)) {
            return $miniOcrXml;
        }
        return '';
    }



    /**
     * This registers the necessary ALTO namespace for the current ALTO-XML
     *
     * @access private
     *
     * @param \SimpleXMLElement &$xml: The XML to register the namespace for
     */
    /**
     * This registers the necessary ALTO namespace for the current ALTO-XML
     *
     * @access private
     *
     * @param \SimpleXMLElement &$xml: The XML to register the namespace for
     */
    private function registerTeiNamespace(\SimpleXMLElement &$xml)
    {
        $namespace = $xml->getDocNamespaces();

        if (in_array('http://www.tei-c.org/ns/1.0', $namespace, true)) {
            $xml->registerXPathNamespace('TEI', 'http://www.tei-c.org/ns/1.0');
        }
    }
}
