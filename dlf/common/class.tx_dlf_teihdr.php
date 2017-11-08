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
 * Metadata format class 'tx_dlf_teihdr' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_teihdr implements tx_dlf_format {

	/**
	 * This extracts the essential TEIHDR metadata from XML
	 *
	 * @access	public
	 *
	 * @param	SimpleXMLElement		$xml: The XML to extract the metadata from
	 * @param	array		&$metadata: The metadata array to fill
	 *
	 * @return	void
	 */
	public function extractMetadata(SimpleXMLElement $xml, array &$metadata) {

		$xml->registerXPathNamespace('teihdr', 'http://www.tei-c.org/ns/1.0');

	}

}
