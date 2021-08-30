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

namespace Kitodo\Dlf\Common\Document;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait XMLDocument
{
    /**
     * Register all available data formats
     *
     * @access protected
     *
     * @return void
     */
    protected function loadFormats()
    {
        if (!$this->formatsLoaded) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_formats');

            // Get available data formats from database.
            $result = $queryBuilder
                ->select(
                    'tx_dlf_formats.type AS type',
                    'tx_dlf_formats.root AS root',
                    'tx_dlf_formats.namespace AS namespace',
                    'tx_dlf_formats.class AS class'
                )
                ->from('tx_dlf_formats')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_formats.pid', 0)
                )
                ->execute();

            while ($resArray = $result->fetch()) {
                // Update format registry.
                $this->formats[$resArray['type']] = [
                    'rootElement' => $resArray['root'],
                    'namespaceURI' => $resArray['namespace'],
                    'class' => $resArray['class']
                ];
            }

            $this->formatsLoaded = true;
        }
    }

    /**
     * XML specific part of loading a location
     *
     * @access protected
     *
     * @param string $location: The URL of the file to load
     *
     * @return string|bool string on success or false on failure
     *
     * @see Document::loadLocation($location)
     */
    protected function loadXMLLocation($location) {
        $fileResource = GeneralUtility::getUrl($location);
        if ($fileResource !== false) {
            $xml = Helper::getXmlFileAsString($fileResource);
            if ($xml !== false) {
                return $xml;
            }
        }
        $this->logger->error('Could not load XML file from "' . $location . '"');
        return false;
    }

    /**
     * Register all available namespaces for a \SimpleXMLElement object
     *
     * @access public
     *
     * @param \SimpleXMLElement|\DOMXPath &$obj: \SimpleXMLElement or \DOMXPath object
     *
     * @return void
     */
    protected function registerNamespaces(&$obj)
    {
        // TODO Check usage, because it is public and may be used by extensions
        // TODO XML specific method does not seem to be used anywhere outside this class within the project
        // Changed to protected while moved to trait
        $this->loadFormats();
        // Do we have a \SimpleXMLElement or \DOMXPath object?
        if ($obj instanceof \SimpleXMLElement) {
            $method = 'registerXPathNamespace';
        } elseif ($obj instanceof \DOMXPath) {
            $method = 'registerNamespace';
        } else {
            $this->logger->error('Given object is neither a SimpleXMLElement nor a DOMXPath instance');
            return;
        }
        // Register metadata format's namespaces.
        foreach ($this->formats as $enc => $conf) {
            $obj->$method(strtolower($enc), $conf['namespaceURI']);
        }
    }
}