<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
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
 * Metadata format class 'tx_dlf_mods' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_mods implements tx_dlf_format {

	/**
	 * This extracts the essential MODS metadata from XML
	 *
	 * @access	public
	 *
	 * @param	SimpleXMLElement		$xml: The XML to extract the metadata from
	 * @param	array		&$metadata: The metadata array to fill
	 *
	 * @return	void
	 */
	public function extractMetadata(SimpleXMLElement $xml, array &$metadata) {

		$xml->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

		// Get "author" and "author_sorting".
		$authors = $xml->xpath('./mods:name[./mods:role/mods:roleTerm[@type="code"][@authority="marcrelator"]="aut"]');

		// Get "author" and "author_sorting" again if that was to sophisticated.
		if (!$authors) {

			// Get all names which do not have any role term assigned and assume these are authors.
			$authors = $xml->xpath('./mods:name[not(./mods:role)]');

		}

		if (is_array($authors)) {

			for ($i = 0, $j = count($authors); $i < $j; $i++) {

				$authors[$i]->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

				// Check if there are separate family and given names.
				if (($nameParts = $authors[$i]->xpath('./mods:namePart'))) {

					$name = array ();

					$k = 4;

					foreach ($nameParts as $namePart) {

						if (isset($namePart['type']) && (string) $namePart['type'] == 'family') {

							$name[0] = (string) $namePart;

						} elseif (isset($namePart['type']) && (string) $namePart['type'] == 'given') {

							$name[1] = (string) $namePart;

						} elseif (isset($namePart['type']) && (string) $namePart['type'] == 'termsOfAddress') {

							$name[2] = (string) $namePart;

						} elseif (isset($namePart['type']) && (string) $namePart['type'] == 'date') {

							$name[3] = (string) $namePart;

						} else {

							$name[$k] = (string) $namePart;

						}

						$k++;

					}

					ksort($name);

					$metadata['author'][$i] = trim(implode(', ', $name));

				}

				// Check if there is a display form.
				if (($displayForm = $authors[$i]->xpath('./mods:displayForm'))) {

					$metadata['author'][$i] = (string) $displayForm[0];

				}

			}

		}

		// Get "place" and "place_sorting".
		$places = $xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:place/mods:placeTerm');

		// Get "place" and "place_sorting" again if that was to sophisticated.
		if (!$places) {

			// Get all places and assume these are places of publication.
			$places = $xml->xpath('./mods:originInfo/mods:place/mods:placeTerm');

		}

		if (is_array($places)) {

			foreach ($places as $place) {

				$metadata['place'][] = (string) $place;

				if (!$metadata['place_sorting'][0]) {

					$metadata['place_sorting'][0] = preg_replace('/[[:punct:]]/', '', (string) $place);

				}

			}

		}

		// Get "year_sorting".
		if (($years_sorting = $xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:dateOther[@type="order"][@encoding="w3cdtf"]'))) {

			foreach ($years_sorting as $year_sorting) {

				$metadata['year_sorting'][0] = intval($year_sorting);

			}

		}

		// Get "year" and "year_sorting" if not specified separately.
		$years = $xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:dateIssued[@keyDate="yes"]');

		// Get "year" and "year_sorting" again if that was to sophisticated.
		if (!$years) {

			// Get all dates and assume these are dates of publication.
			$years = $xml->xpath('./mods:originInfo/mods:dateIssued');

		}

		if (is_array($years)) {

			foreach ($years as $year) {

				$metadata['year'][] = (string) $year;

				if (!$metadata['year_sorting'][0]) {

					$year_sorting = str_ireplace('x', '5', preg_replace('/[^\d.x]/i', '', (string) $year));

					if (strpos($year_sorting, '.') || strlen($year_sorting) < 3) {

						$year_sorting = ((intval(trim($year_sorting, '.')) - 1) * 100) + 50;

					}

					$metadata['year_sorting'][0] = intval($year_sorting);

				}

			}

		}

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_mods.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_mods.php']);
}
