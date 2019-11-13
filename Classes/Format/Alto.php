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
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Alto implements \Kitodo\Dlf\Common\FulltextInterface
{
    /**
     * This extracts the fulltext data from ALTO XML
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml: The XML to extract the raw text from
     *
     * @return string The raw unformatted fulltext
     */
    public function getRawText(\SimpleXMLElement $xml)
    {
        $rawText = '';
        $xml->registerXPathNamespace('alto', 'http://www.loc.gov/standards/alto/ns-v2#');
        // Get all (presumed) words of the text.
        $words = $xml->xpath('./alto:Layout/alto:Page/alto:PrintSpace//alto:TextBlock/alto:TextLine/alto:String/@CONTENT');
        if (!empty($words)) {
            $rawText = implode(' ', $words);
        }
        return $rawText;
    }
}
