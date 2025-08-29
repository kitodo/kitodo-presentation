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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FullTextReader
{
    /**
     * @access private
     * @var Logger This holds the logger
     */
    private Logger $logger;

    /**
     * @access private
     * @var array This holds all formats
     */
    private array $formats;

    /**
     * Constructor
     *
     * @param array $formats
     */
    public function __construct(array $formats)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(get_class($this));
        $this->formats = $formats;
    }

    /**
     * This extracts the OCR full text for a physical structure node / IIIF Manifest / Canvas from an
     * XML full text representation. For IIIF manifests, ALTO documents have
     * to be given in the Canvas' / Manifest's "seeAlso" property.
     *
     * @param string $id The "@ID" attribute of the physical structure node (METS) or the "@id" property
     * of the Manifest / Range (IIIF)
     * @param array $fileLocations The locations of the XML files
     * @param mixed $physicalStructureNode The physical structure node (METS) or the Manifest / Range (IIIF)
     *
     * @return string The OCR full text
     */
    public function getFromXml(string $id, array $fileLocations, $physicalStructureNode): string
    {
        $fullText = '';

        $useGroupsFulltext = $this->getFullTextUseGroups();
        $textFormat = "";
        if (!empty($physicalStructureNode)) {
            while ($useGroupFulltext = array_shift($useGroupsFulltext)) {
                if (!empty($physicalStructureNode['files'][$useGroupFulltext])) {
                    // Get full text file.
                    $fileContent = GeneralUtility::getUrl($fileLocations[$useGroupFulltext]);
                    if ($fileContent !== false) {
                        $textFormat = $this->getTextFormat($fileContent);
                    } else {
                        $this->logger->warning('Couldn\'t load full text file for structure node @ID "' . $id . '"');
                        return $fullText;
                    }
                    break;
                }
            }
        } else {
            $this->logger->warning('Invalid structure node @ID "' . $id . '"');
            return $fullText;
        }
        // Is this text format supported?
        // This part actually differs from previous version of indexed OCR
        if (!empty($fileContent) && !empty($this->formats[$textFormat])) {
            $textMiniOcr = '';
            if (!empty($this->formats[$textFormat]['class'])) {
                $textMiniOcr = $this->getRawTextFromClass($id, $fileContent, $textFormat);
            }
            $fullText = $textMiniOcr;
        } else {
            $this->logger->warning('Unsupported text format "' . $textFormat . '" in physical node with @ID "' . $id . '"');
        }

        return $fullText;
    }

    /**
     * Get raw text from class for given format.
     *
     * @access private
     *
     * @param string $id The "@ID" attribute of the physical structure node (METS) or the "@id" property
     * of the Manifest / Range (IIIF)
     * @param string $fileContent The content of the XML file
     * @param string $textFormat
     *
     * @return string
     */
    private function getRawTextFromClass(string $id, string $fileContent, string $textFormat): string
    {
        $textMiniOcr = '';
        $class = $this->formats[$textFormat]['class'];
        // Get the raw text from class.
        if (class_exists($class)) {
            $obj = GeneralUtility::makeInstance($class);
            if ($obj instanceof FulltextInterface) {
                // Load XML from file.
                $ocrTextXml = Helper::getXmlFileAsString($fileContent);
                $obj->setPageId($id);
                $textMiniOcr = $obj->getTextAsMiniOcr($ocrTextXml);
            } else {
                $this->logger->warning('Invalid class/method "' . $class . '->getRawText()" for text format "' . $textFormat . '"');
            }
        } else {
            $this->logger->warning('Class "' . $class . ' does not exists for "' . $textFormat . ' text format"');
        }
        return $textMiniOcr;
    }

    /**
     * Get full text file groups from extension configuration.
     *
     * @return array
     */
    private function getFullTextUseGroups(): array
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf', 'files');
        return GeneralUtility::trimExplode(',', $extConf['useGroupsFulltext']);
    }

    /**
     * Get format of the OCR full text
     *
     * @access private
     *
     * @param string $fileContent The content of the XML file
     *
     * @return string The format of the OCR full text
     */
    private function getTextFormat(string $fileContent): string
    {
        $xml = Helper::getXmlFileAsString($fileContent);

        if ($xml !== false) {
            // Get the root element's name as text format.
            return strtoupper($xml->getName());
        } else {
            return '';
        }
    }
}
