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

namespace Kitodo\Dlf\Hooks;

/**
 * Hooks and hacks for Kitodo.Production
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class KitodoProductionHacks
{
    /**
     * Hook for \Kitodo\Dlf\Common\MetsDocument::establishRecordId()
     * When using Kitodo.Production the record identifier is saved only in MODS, but not
     * in METS. To get it anyway, we have to do some magic.
     *
     * @access public
     *
     * @param \SimpleXMLElement &$xml The XML object
     * @param mixed $recordId The record identifier
     *
     * @return void
     */
    public function postProcessRecordId(\SimpleXMLElement &$xml, &$recordId): void
    {
        if (!$recordId) {
            $xml->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
            // Get all logical structure nodes with metadata, but without associated METS-Pointers.
            $divs = $xml->xpath('//mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID and not(./mets:mptr)]');
            if (is_array($divs)) {
                $smLinks = $xml->xpath('//mets:structLink/mets:smLink');
                if (!empty($smLinks)) {
                    $links = [];

                    foreach ($smLinks as $smLink) {
                        $links[(string) $smLink->attributes('http://www.w3.org/1999/xlink')->from][] = (string) $smLink->attributes('http://www.w3.org/1999/xlink')->to;
                    }

                    foreach ($divs as $div) {
                        if (!empty($links[(string) $div['ID']])) {
                            $id = (string) $div['DMDID'];
                            break;
                        }
                    }
                }
                if (empty($id)) {
                    $id = (string) $divs[0]['DMDID'];
                }
                $dmdIds = explode(' ', $id);
                foreach ($dmdIds as $dmdId) {
                    $recordIds = $xml->xpath('//mets:dmdSec[@ID="' . $dmdId . '"]//mods:mods/mods:recordInfo/mods:recordIdentifier');
                    if (!empty($recordIds)) {
                        $recordId = (string) $recordIds[0];
                        break;
                    }
                }
            }
        }
    }
}
