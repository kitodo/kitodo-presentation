<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Kitodo. Key to digital objects e.V. <contact@kitodo.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Fulltext format class 'tx_dlf_alto' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_alto implements tx_dlf_fulltext {

	/**
	 * This extracts the fulltext data from ALTO XML
	 *
	 * @access	public
	 *
	 * @param	SimpleXMLElement		$xml: The XML to extract the metadata from
	 *
	 * @return	string			The raw unformatted fulltext
	 */
	public static function getRawText(SimpleXMLElement $xml) {

		$xml->registerXPathNamespace('alto', 'http://www.loc.gov/standards/alto/ns-v2#');

		// Get all (presumed) words of the text.
		$words = $xml->xpath('./alto:Layout/alto:Page/alto:PrintSpace/alto:TextBlock/alto:TextLine/alto:String/@CONTENT');

		return implode(' ', $words);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_alto.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_alto.php']);
}
