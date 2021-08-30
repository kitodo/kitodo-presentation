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

use Kitodo\Dlf\Common\FulltextInterface;
use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Document class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @property-read bool $hasFullText Are there any full text files available?
 * @property array $rawTextArray array containing raw text
 * @abstract
 */
abstract class FullTextDocument extends Document
{
    /**
     * The extension key
     *
     * @var string
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * Are there any fulltext files available? This also includes IIIF text annotations
     * with motivation 'painting' if Kitodo.Presentation is configured to store text
     * annotations as fulltext.
     *
     * @var bool
     * @access protected
     */
    protected $hasFullText = false;

    /**
     * This holds the documents' raw text pages with their corresponding
     * structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
     *
     * @var array
     * @access protected
     */
    protected $rawTextArray = [];

    /**
     * This extracts the OCR full text for a physical structure node / IIIF Manifest / Canvas. Text might be
     * given as ALTO for METS or as annotations or ALTO for IIIF resources.
     *
     * @access public
     *
     * @abstract
     *
     * @param string $id: The @ID attribute of the physical structure node (METS) or the @id property
     * of the Manifest / Range (IIIF)
     *
     * @return string The OCR full text
     */
    public abstract function getFullText($id);

    /**
     * Analyze the document if it contains any full text that needs to be indexed.
     *
     * @access protected
     *
     * @abstract
     */
    protected abstract function ensureHasFullTextIsSet();

    /**
     * This extracts the OCR full text for a physical structure node / IIIF Manifest / Canvas from an
     * XML full text representation (currently only ALTO). For IIIF manifests, ALTO documents have
     * to be given in the Canvas' / Manifest's "seeAlso" property.
     *
     * @param string $id: The @ID attribute of the physical structure node (METS) or the @id property
     * of the Manifest / Range (IIIF)
     *
     * @return string The OCR full text
     */
    protected function getFullTextFromXml($id)
    {
        $fullText = '';
        // Load available text formats, ...
        $this->loadFormats();
        // ... physical structure ...
        $this->_getPhysicalStructure();
        // ... and extension configuration.
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey);
        $fileGrpsFulltext = GeneralUtility::trimExplode(',', $extConf['fileGrpFulltext']);
        if (!empty($this->physicalStructureInfo[$id])) {
            while ($fileGrpFulltext = array_shift($fileGrpsFulltext)) {
                if (!empty($this->physicalStructureInfo[$id]['files'][$fileGrpFulltext])) {
                    // Get full text file.
                    $fileContent = GeneralUtility::getUrl($this->getFileLocation($this->physicalStructureInfo[$id]['files'][$fileGrpFulltext]));
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
                $class = $this->formats[$textFormat]['class'];
                // Get the raw text from class.
                if (
                    class_exists($class)
                    && ($obj = GeneralUtility::makeInstance($class)) instanceof FulltextInterface
                ) {
                    // Load XML from file.
                    $ocrTextXml = Helper::getXmlFileAsString($fileContent);
                    $textMiniOcr = $obj->getTextAsMiniOcr($ocrTextXml);
                    $this->rawTextArray[$id] = $textMiniOcr;
                } else {
                    $this->logger->warning('Invalid class/method "' . $class . '->getRawText()" for text format "' . $textFormat . '"');
                }
            }
            $fullText = $textMiniOcr;
        } else {
            $this->logger->warning('Unsupported text format "' . $textFormat . '" in physical node with @ID "' . $id . '"');
        }
        return $fullText;
    }

    /**
     * Get format of the OCR full text
     *
     * @access private
     *
     * @param string $fileContent: content of the XML file
     *
     * @return string The format of the OCR full text
     */
    private function getTextFormat($fileContent)
    {
        // Get the root element's name as text format.
        return strtoupper(Helper::getXmlFileAsString($fileContent)->getName());
    }

    /**
     * This returns $this->hasFullText via __get()
     *
     * @access protected
     *
     * @return bool Are there any full text files available?
     */
    protected function _getHasFullText()
    {
        $this->ensureHasFullTextIsSet();
        return $this->hasFullText;
    }
}
