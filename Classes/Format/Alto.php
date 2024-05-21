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
class Alto implements \Kitodo\Dlf\Common\FulltextInterface
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
        $this->registerAltoNamespace($xml);

        // Get all (presumed) words of the text.
        $strings = $xml->xpath('./alto:Layout/alto:Page/alto:PrintSpace//alto:TextBlock/alto:TextLine/alto:String');
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
        return $rawText;
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
        $this->registerAltoNamespace($xml);

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
     * This extracts and parses the word from attribute
     *
     * @access private
     *
     * @param \SimpleXMLElement $attributes The XML to extract the word
     *
     * @return string The parsed word extracted from attribute
     */
    private function getWord(\SimpleXMLElement $attributes): string
    {
        if (!empty($attributes['SUBS_CONTENT'])) {
            if ($attributes['SUBS_TYPE'] == 'HypPart1') {
                return htmlspecialchars((string) $attributes['SUBS_CONTENT']);
            }
            return ' ';
        }
        return htmlspecialchars((string) $attributes['CONTENT']) . ' ';
    }

    /**
     * This extracts and parses the word coordinates from attributes
     *
     * @access private
     *
     * @param \SimpleXMLElement $attributes The XML to extract the word coordinates
     *
     * @return string The parsed word coordinates extracted from attribute
     */
    private function getCoordinates(\SimpleXMLElement $attributes): string
    {
        return (string) $attributes['HPOS'] . ' ' . (string) $attributes['VPOS'] . ' ' . (string) $attributes['WIDTH'] . ' ' . (string) $attributes['HEIGHT'];
    }

    /**
     * This registers the necessary ALTO namespace for the current ALTO-XML
     *
     * @access private
     *
     * @param \SimpleXMLElement &$xml: The XML to register the namespace for
     */
    private function registerAltoNamespace(\SimpleXMLElement &$xml)
    {
        $namespace = $xml->getDocNamespaces();

        if (in_array('http://www.loc.gov/standards/alto/ns-v2#', $namespace, true)) {
            $xml->registerXPathNamespace('alto', 'http://www.loc.gov/standards/alto/ns-v2#');
        } elseif (in_array('http://www.loc.gov/standards/alto/ns-v3#', $namespace, true)) {
            $xml->registerXPathNamespace('alto', 'http://www.loc.gov/standards/alto/ns-v3#');
        } elseif (in_array('http://www.loc.gov/standards/alto/ns-v4#', $namespace, true)) {
            $xml->registerXPathNamespace('alto', 'http://www.loc.gov/standards/alto/ns-v4#');
        }
    }
}
