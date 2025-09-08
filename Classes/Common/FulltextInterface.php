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

namespace Kitodo\Dlf\Common;

/**
 * Fulltext interface for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @abstract
 */
interface FulltextInterface
{

    /**
     * Set the page identifier.
     *
     * @access public
     *
     * @param string $pageId The page identifier of mets:div in the physical struct map of the METS.
     */
    public function setPageId(string $pageId): void;

    /**
     * This extracts raw fulltext data from XML
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml The XML to extract the metadata from
     *
     * @return string The raw unformatted fulltext
     */
    public function getRawText(\SimpleXMLElement $xml): string;

    /**
     * This extracts the fulltext data from ALTO XML and returns it in MiniOCR format
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml The XML to extract the raw text from
     *
     * @return string The unformatted fulltext in MiniOCR format
     */
    public function getTextAsMiniOcr(\SimpleXMLElement $xml): string;
}
