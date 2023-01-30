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

use Kitodo\Dlf\Api\Orcid\Profile as OrcidProfile;
use Kitodo\Dlf\Api\Viaf\Profile as ViafProfile;

/**
 * Metadata MODS format class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Mods implements \Kitodo\Dlf\Common\MetadataInterface
{
    /**
     * The metadata XML
     *
     * @var \SimpleXMLElement
     **/
    private $xml;

    /**
     * The metadata array
     *
     * @var array
     **/
    private $metadata;

    /**
     * This extracts the essential MODS metadata from XML
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml: The XML to extract the metadata from
     * @param array &$metadata: The metadata array to fill
     *
     * @return void
     */
    public function extractMetadata(\SimpleXMLElement $xml, array &$metadata)
    {
        $this->xml = $xml;
        $this->metadata = $metadata;

        $this->xml->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

        $this->getAuthors();
        $this->getHolders();
        $this->getPlaces();
        $this->getYears();

        $metadata = $this->metadata;
    }

    /**
     * Get "author" and "author_sorting".
     *
     * @access private
     *
     * @return void
     */
    private function getAuthors() {
        $authors = $this->xml->xpath('./mods:name[./mods:role/mods:roleTerm[@type="code" and @authority="marcrelator"]="aut"]');

        // Get "author" and "author_sorting" again if that was too sophisticated.
        if (empty($authors)) {
            // Get all names which do not have any role term assigned and assume these are authors.
            $authors = $this->xml->xpath('./mods:name[not(./mods:role)]');
        }
        if (!empty($authors)) {
            for ($i = 0, $j = count($authors); $i < $j; $i++) {
                $authors[$i]->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

                $identifier = $authors[$i]->xpath('./mods:name/mods:nameIdentifier[@type="orcid"]');
                if ($this->settings['useExternalApisForMetadata'] && !empty((string) $identifier[0])) {
                    $this->getAuthorFromOrcidApi((string) $identifier[0], $authors, $i);
                } else {
                    $this->getAuthorFromXml($authors, $i);
                }
            }
        }
    }

    private function getAuthorFromOrcidApi($orcidId, $authors, $i) {
        $profile = new OrcidProfile($orcidId);
        $name = $profile->getFullName();
        if (!empty($name)) {
            $this->metadata['author'][$i] = [
                'name' => $name,
                'url' => 'https://orcid.org/' . $orcidId
            ];
        } else {
            //fallback into display form
            $this->getAuthorFromXmlDisplayForm($authors, $i);
        }
    }

    private function getAuthorFromXml($authors, $i) {
        $this->getAuthorFromXmlDisplayForm($authors, $i);

        $nameParts = $authors[$i]->xpath('./mods:namePart');

        if (empty($this->metadata['author'][$i]) && $nameParts) {
            $name = [];
            $k = 4;
            foreach ($nameParts as $namePart) {
                if (
                    isset($namePart['type'])
                    && (string) $namePart['type'] == 'family'
                ) {
                    $name[0] = (string) $namePart;
                } elseif (
                    isset($namePart['type'])
                    && (string) $namePart['type'] == 'given'
                ) {
                    $name[1] = (string) $namePart;
                } elseif (
                    isset($namePart['type'])
                    && (string) $namePart['type'] == 'termsOfAddress'
                ) {
                    $name[2] = (string) $namePart;
                } elseif (
                    isset($namePart['type'])
                    && (string) $namePart['type'] == 'date'
                ) {
                    $name[3] = (string) $namePart;
                } else {
                    $name[$k] = (string) $namePart;
                }
                $k++;
            }
            ksort($name);
            $this->metadata['author'][$i] = trim(implode(', ', $name));
        }
        // Append "valueURI" to name using Unicode unit separator.
        if (isset($authors[$i]['valueURI'])) {
            $this->metadata['author'][$i] .= chr(31) . (string) $authors[$i]['valueURI'];
        }
    }

    private function getAuthorFromXmlDisplayForm($authors, $i) {
        $displayForm = $authors[$i]->xpath('./mods:displayForm');
        if ($displayForm) {
            $this->metadata['author'][$i] = (string) $displayForm[0];
        }
    }

    /**
     * Get holder.
     *
     * @access private
     *
     * @return void
     */
    private function getHolders() {
        $holders = $this->xml->xpath('./mods:name[./mods:role/mods:roleTerm[@type="code" and @authority="marcrelator"]="prv"]');

        if (!empty($holders)) {
            for ($i = 0, $j = count($holders); $i < $j; $i++) {
                $holders[$i]->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

                $identifier = $holders[$i]->xpath('./mods:name/mods:nameIdentifier[@type="viaf"]');
                if ($this->settings['useExternalApisForMetadata'] && !empty((string) $identifier[0])) {
                    $this->getHolderFromViafApi((string) $identifier[0], $holders, $i);
                } else {
                    $this->getHolderFromXml($holders, $i);
                }
            }
        }
    }

    private function getHolderFromViafApi($viafId, $holders, $i) {
        $profile = new ViafProfile($viafId);
        $name = $profile->getFullName();
        if (!empty($name)) {
            $this->metadata['holder'][$i] = [
                'name' => $name,
                'url' => 'http://viaf.org/viaf/' . $viafId
            ];
        } else {
            //fallback into display form
            $this->getHolderFromXmlDisplayForm($holders, $i);
        }
    }

    private function getHolderFromXml($holders, $i) {
        $this->getHolderFromXmlDisplayForm($holders, $i);
        // Append "valueURI" to name using Unicode unit separator.
        if (isset($holders[$i]['valueURI'])) {
            $this->metadata['holder'][$i] .= chr(31) . (string) $holders[$i]['valueURI'];
        }
    }

    private function getHolderFromXmlDisplayForm($holders, $i) {
        // Check if there is a display form.
        $displayForm = $holders[$i]->xpath('./mods:displayForm');
        if ($displayForm) {
            $this->metadata['holder'][$i] = (string) $displayForm[0];
        }
    }

    /**
     * Get "place" and "place_sorting".
     *
     * @access private
     *
     * @return void
     */
    private function getPlaces() {
        $places = $this->xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:place/mods:placeTerm');
        // Get "place" and "place_sorting" again if that was to sophisticated.
        if (empty($places)) {
            // Get all places and assume these are places of publication.
            $places = $this->xml->xpath('./mods:originInfo/mods:place/mods:placeTerm');
        }
        if (!empty($places)) {
            foreach ($places as $place) {
                $this->metadata['place'][] = (string) $place;
                if (!$this->metadata['place_sorting'][0]) {
                    $this->metadata['place_sorting'][0] = preg_replace('/[[:punct:]]/', '', (string) $place);
                }
            }
        }
    }

    /**
     * Get "year" and "year_sorting".
     *
     * @access private
     *
     * @return void
     */
    private function getYears() {
        // Get "year_sorting".
        if (($years_sorting = $this->xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:dateOther[@type="order" and @encoding="w3cdtf"]'))) {
            foreach ($years_sorting as $year_sorting) {
                $this->metadata['year_sorting'][0] = intval($year_sorting);
            }
        }
        // Get "year" and "year_sorting" if not specified separately.
        $years = $this->xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:dateIssued[@keyDate="yes"]');
        // Get "year" and "year_sorting" again if that was to sophisticated.
        if (empty($years)) {
            // Get all dates and assume these are dates of publication.
            $years = $this->xml->xpath('./mods:originInfo/mods:dateIssued');
        }
        if (!empty($years)) {
            foreach ($years as $year) {
                $this->metadata['year'][] = (string) $year;
                if (!$this->metadata['year_sorting'][0]) {
                    $year_sorting = str_ireplace('x', '5', preg_replace('/[^\d.x]/i', '', (string) $year));
                    if (
                        strpos($year_sorting, '.')
                        || strlen($year_sorting) < 3
                    ) {
                        $year_sorting = ((intval(trim($year_sorting, '.')) - 1) * 100) + 50;
                    }
                    $this->metadata['year_sorting'][0] = intval($year_sorting);
                }
            }
        }
    }
}
