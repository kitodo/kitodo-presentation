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

/**
 * Interface 'tx_dlf_fulltext' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 * @abstract
 */
interface tx_dlf_fulltext {

    /**
     * This extracts raw fulltext data from XML
     *
     * @access	public
     *
     * @param	SimpleXMLElement		$xml: The XML to extract the metadata from
     *
     * @return	string			The raw unformatted fulltext
     */
    public static function getRawText(SimpleXMLElement $xml);

}
