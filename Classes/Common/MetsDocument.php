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

use \DOMElement;
use \DOMXPath;
use \SimpleXMLElement;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Ubl\Iiif\Tools\IiifHelper;
use Ubl\Iiif\Services\AbstractImageService;

/**
 * MetsDocument class for the 'dlf' extension.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @property int $cPid this holds the PID for the configuration
 * @property-read array $formats this holds the configuration for all supported metadata encodings
 * @property bool $formatsLoaded flag with information if the available metadata formats are loaded
 * @property-read bool $hasFulltext flag with information if there are any fulltext files available
 * @property array $lastSearchedPhysicalPage the last searched logical and physical page
 * @property array $logicalUnits this holds the logical units
 * @property-read array $metadataArray this holds the documents' parsed metadata array
 * @property bool $metadataArrayLoaded flag with information if the metadata array is loaded
 * @property-read int $numPages the holds the total number of pages
 * @property-read int $parentId this holds the UID of the parent document or zero if not multi-volumed
 * @property-read array $physicalStructure this holds the physical structure
 * @property-read array $physicalStructureInfo this holds the physical structure metadata
 * @property bool $physicalStructureLoaded flag with information if the physical structure is loaded
 * @property-read int $pid this holds the PID of the document or zero if not in database
 * @property array $rawTextArray this holds the documents' raw text pages with their corresponding structMap//div's ID (METS) or Range / Manifest / Sequence ID (IIIF) as array key
 * @property-read bool $ready Is the document instantiated successfully?
 * @property-read string $recordId the METS file's / IIIF manifest's record identifier
 * @property-read int $rootId this holds the UID of the root document or zero if not multi-volumed
 * @property-read array $smLinks this holds the smLinks between logical and physical structMap
 * @property bool $smLinksLoaded flag with information if the smLinks are loaded
 * @property-read array $tableOfContents this holds the logical structure
 * @property bool $tableOfContentsLoaded flag with information if the table of contents is loaded
 * @property-read string $thumbnail this holds the document's thumbnail location
 * @property bool $thumbnailLoaded flag with information if the thumbnail is loaded
 * @property SimpleXMLElement $xml this holds the whole XML file as SimpleXMLElement object
 * @property-read array $mdSec associative array of METS metadata sections indexed by their IDs.
 * @property bool $mdSecLoaded flag with information if the array of METS metadata sections is loaded
 * @property-read array $dmdSec subset of `$mdSec` storing only the dmdSec entries; kept for compatibility.
 * @property-read array $fileGrps this holds the file ID -> USE concordance
 * @property bool $fileGrpsLoaded flag with information if file groups array is loaded
 * @property-read array $fileInfos additional information about files (e.g., ADMID), indexed by ID.
 * @property-read SimpleXMLElement $mets this holds the XML file's METS part as SimpleXMLElement object
 * @property-read string $parentHref URL of the parent document (determined via mptr element), or empty string if none is available
 */
final class MetsDocument extends AbstractDocument
{
    /**
     * @access protected
     * @var string[] Subsections / tags that may occur within `<mets:amdSec>`
     *
     * @link https://www.loc.gov/standards/mets/docs/mets.v1-9.html#amdSec
     * @link https://www.loc.gov/standards/mets/docs/mets.v1-9.html#mdSecType
     */
    protected const ALLOWED_AMD_SEC = ['techMD', 'rightsMD', 'sourceMD', 'digiprovMD'];

    /**
     * @access protected
     * @var string This holds the whole XML file as string for serialization purposes
     *
     * @see __sleep() / __wakeup()
     */
    protected string $asXML = '';

    /**
     * @access protected
     * @var array This maps the ID of each amdSec to the IDs of its children (techMD etc.). When an ADMID references an amdSec instead of techMD etc., this is used to iterate the child elements.
     */
    protected array $amdSecChildIds = [];

    /**
     * @access protected
     * @var array Associative array of METS metadata sections indexed by their IDs.
     */
    protected array $mdSec = [];

    /**
     * @access protected
     * @var bool Are the METS file's metadata sections loaded?
     *
     * @see MetsDocument::$mdSec
     */
    protected bool $mdSecLoaded = false;

    /**
     * @access protected
     * @var array Subset of $mdSec storing only the dmdSec entries; kept for compatibility.
     */
    protected array $dmdSec = [];

    /**
     * @access protected
     * @var array This holds the file ID -> USE concordance
     *
     * @see magicGetFileGrps()
     */
    protected array $fileGrps = [];

    /**
     * @access protected
     * @var bool Are the image file groups loaded?
     *
     * @see $fileGrps
     */
    protected bool $fileGrpsLoaded = false;

    /**
     * @access protected
     * @var SimpleXMLElement This holds the XML file's METS part as SimpleXMLElement object
     */
    protected SimpleXMLElement $mets;

    /**
     * @access protected
     * @var string URL of the parent document (determined via mptr element), or empty string if none is available
     */
    protected string $parentHref = '';

    /**
     * @access protected
     * @var array the extension settings
     */
    protected array $settings = [];

    /**
     * This adds metadata from METS structural map to metadata array.
     *
     * @access public
     *
     * @param array &$metadata The metadata array to extend
     * @param string $id The "@ID" attribute of the logical structure node
     *
     * @return void
     */
    public function addMetadataFromMets(array &$metadata, string $id): void
    {
        $details = $this->getLogicalStructure($id);
        if (!empty($details)) {
            $metadata['mets_order'][0] = $details['order'];
            $metadata['mets_label'][0] = $details['label'];
            $metadata['mets_orderlabel'][0] = $details['orderlabel'];
        }
    }

    /**
     * @see AbstractDocument::establishRecordId()
     */
    protected function establishRecordId(int $pid): void
    {
        // Check for METS object @ID.
        if (!empty($this->mets['OBJID'])) {
            $this->recordId = (string) $this->mets['OBJID'];
        }
        // Get hook objects.
        $hookObjects = Helper::getHookObjects('Classes/Common/MetsDocument.php');
        // Apply hooks.
        foreach ($hookObjects as $hookObj) {
            if (method_exists($hookObj, 'postProcessRecordId')) {
                $hookObj->postProcessRecordId($this->xml, $this->recordId);
            }
        }
    }

    /**
     * @see AbstractDocument::getDownloadLocation()
     */
    public function getDownloadLocation(string $id): string
    {
        $file = $this->getFileInfo($id);
        if ($file['mimeType'] === 'application/vnd.kitodo.iiif') {
            $file['location'] = (strrpos($file['location'], 'info.json') === strlen($file['location']) - 9) ? $file['location'] : (strrpos($file['location'], '/') === strlen($file['location']) ? $file['location'] . 'info.json' : $file['location'] . '/info.json');
            $conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey, 'iiif');
            IiifHelper::setUrlReader(IiifUrlReader::getInstance());
            IiifHelper::setMaxThumbnailHeight($conf['thumbnailHeight']);
            IiifHelper::setMaxThumbnailWidth($conf['thumbnailWidth']);
            $service = IiifHelper::loadIiifResource($file['location']);
            if ($service instanceof AbstractImageService) {
                return $service->getImageUrl();
            }
        } elseif ($file['mimeType'] === 'application/vnd.netfpx') {
            $baseURL = $file['location'] . (strpos($file['location'], '?') === false ? '?' : '');
            // TODO CVT is an optional IIP server capability; in theory, capabilities should be determined in the object request with '&obj=IIP-server'
            return $baseURL . '&CVT=jpeg';
        }
        return $file['location'];
    }

    /**
     * {@inheritDoc}
     * @see AbstractDocument::getFileInfo()
     */
    public function getFileInfo($id): ?array
    {
        $this->magicGetFileGrps();

        if (isset($this->fileInfos[$id]) && empty($this->fileInfos[$id]['location'])) {
            $this->fileInfos[$id]['location'] = $this->getFileLocation($id);
        }

        if (isset($this->fileInfos[$id]) && empty($this->fileInfos[$id]['mimeType'])) {
            $this->fileInfos[$id]['mimeType'] = $this->getFileMimeType($id);
        }

        return $this->fileInfos[$id] ?? null;
    }

    /**
     * @see AbstractDocument::getFileLocation()
     */
    public function getFileLocation(string $id): string
    {
        $location = $this->mets->xpath('./mets:fileSec/mets:fileGrp/mets:file[@ID="' . $id . '"]/mets:FLocat[@LOCTYPE="URL"]');
        if (
            !empty($id)
            && !empty($location)
        ) {
            return (string) $location[0]->attributes('http://www.w3.org/1999/xlink')->href;
        } else {
            $this->logger->warning('There is no file node with @ID "' . $id . '"');
            return '';
        }
    }

    /**
     * @see AbstractDocument::getFileMimeType()
     */
    public function getFileMimeType(string $id): string
    {
        $mimetype = $this->mets->xpath('./mets:fileSec/mets:fileGrp/mets:file[@ID="' . $id . '"]/@MIMETYPE');
        if (
            !empty($id)
            && !empty($mimetype)
        ) {
            return (string) $mimetype[0];
        } else {
            $this->logger->warning('There is no file node with @ID "' . $id . '" or no MIME type specified');
            return '';
        }
    }

    /**
     * @see AbstractDocument::getLogicalStructure()
     */
    public function getLogicalStructure(string $id, bool $recursive = false): array
    {
        $details = [];
        // Is the requested logical unit already loaded?
        if (
            !$recursive
            && !empty($this->logicalUnits[$id])
        ) {
            // Yes. Return it.
            return $this->logicalUnits[$id];
        } elseif (!empty($id)) {
            // Get specified logical unit.
            $divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $id . '"]');
        } else {
            // Get all logical units at top level.
            $divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div');
        }
        if (!empty($divs)) {
            if (!$recursive) {
                // Get the details for the first xpath hit.
                $details = $this->getLogicalStructureInfo($divs[0]);
            } else {
                // Walk the logical structure recursively and fill the whole table of contents.
                foreach ($divs as $div) {
                    $this->tableOfContents[] = $this->getLogicalStructureInfo($div, $recursive);
                }
            }
        }
        return $details;
    }

    /**
     * This gets details about a logical structure element
     *
     * @access protected
     *
     * @param SimpleXMLElement $structure The logical structure node
     * @param bool $recursive Whether to include the child elements
     *
     * @return array Array of the element's id, label, type and physical page indexes/mptr link
     */
    protected function getLogicalStructureInfo(SimpleXMLElement $structure, bool $recursive = false): array
    {
        $attributes = $structure->attributes();

        // Extract identity information.
        $details = [
            'id' => (string) $attributes['ID'],
            'dmdId' => isset($attributes['DMDID']) ? (string) $attributes['DMDID'] : '',
            'admId' => isset($attributes['ADMID']) ? (string) $attributes['ADMID'] : '',
            'order' => isset($attributes['ORDER']) ? (string) $attributes['ORDER'] : '',
            'label' => isset($attributes['LABEL']) ? (string) $attributes['LABEL'] : '',
            'orderlabel' => isset($attributes['ORDERLABEL']) ? (string) $attributes['ORDERLABEL'] : '',
            'contentIds' => isset($attributes['CONTENTIDS']) ? (string) $attributes['CONTENTIDS'] : '',
            'volume' => '',
            'year' => '',
            'pagination' => '',
            'type' => isset($attributes['TYPE']) ? (string) $attributes['TYPE'] : '',
            'description' => '',
            'thumbnailId' => null,
            'files' => [],
        ];

        // Set volume and year information only if no label is set and this is the toplevel structure element.
        if (empty($details['label']) && empty($details['orderlabel'])) {
            $metadata = $this->getMetadata($details['id']);
            $details['volume'] = $metadata['volume'][0] ?? '';
            $details['year'] = $metadata['year'][0] ?? '';
        }

        // add description for 3D objects
        if ($details['type'] == 'object') {
            $metadata = $this->getMetadata($details['id']);
            $details['description'] = $metadata['description'][0] ?? '';
        }

        // Load smLinks.
        $this->magicGetSmLinks();
        // Load physical structure.
        $this->magicGetPhysicalStructure();

        $this->getPage($details, $structure->children('http://www.loc.gov/METS/')->mptr);
        $this->getFiles($details, $structure->children('http://www.loc.gov/METS/')->fptr);

        // Keep for later usage.
        $this->logicalUnits[$details['id']] = $details;
        // Walk the structure recursively? And are there any children of the current element?
        if (
            $recursive
            && count($structure->children('http://www.loc.gov/METS/')->div)
        ) {
            $details['children'] = [];
            foreach ($structure->children('http://www.loc.gov/METS/')->div as $child) {
                // Repeat for all children.
                $details['children'][] = $this->getLogicalStructureInfo($child, true);
            }
        }
        return $details;
    }

    /**
     * Get the files this structure element is pointing at.
     *
     * @param ?SimpleXMLElement $filePointers
     *
     * @return void
     */
    private function getFiles(array &$details, ?SimpleXMLElement $filePointers): void
    {
        $fileUse = $this->magicGetFileGrps();
        // Get the file representations from fileSec node.
        foreach ($filePointers as $filePointer) {
            $fileId = (string) $filePointer->attributes()->FILEID;
            // Check if file has valid @USE attribute.
            if (!empty($fileUse[$fileId])) {
                $details['files'][$fileUse[$fileId]] = $fileId;
            }
        }
    }

    /**
     * Get the physical page or external file this structure element is pointing at.
     *
     * @access private
     *
     * @param array $details passed as reference
     * @param ?SimpleXMLElement $metsPointers
     *
     * @return void
     */
    private function getPage(array &$details, ?SimpleXMLElement $metsPointers): void
    {
        if (count($metsPointers)) {
            // Yes. Get the file reference.
            $details['points'] = (string) $metsPointers[0]->attributes('http://www.w3.org/1999/xlink')->href;
        } elseif (
            !empty($this->physicalStructure)
            && array_key_exists($details['id'], $this->smLinks['l2p'])
        ) {
            // Link logical structure to the first corresponding physical page/track.
            $details['points'] = max((int) array_search($this->smLinks['l2p'][$details['id']][0], $this->physicalStructure, true), 1);
            $details['thumbnailId'] = $this->getThumbnail();
            // Get page/track number of the first page/track related to this structure element.
            $details['pagination'] = $this->physicalStructureInfo[$this->smLinks['l2p'][$details['id']][0]]['orderlabel'];
        } elseif ($details['id'] == $this->getToplevelId()) {
            // Point to self if this is the toplevel structure.
            $details['points'] = 1;
            $details['thumbnailId'] = $this->getThumbnail();
        }
        if ($details['thumbnailId'] === null) {
            unset($details['thumbnailId']);
        }
    }

    /**
     * Get thumbnail for logical structure info.
     *
     * @access private
     *
     * @param string $id empty if top level document, else passed the id of parent document
     *
     * @return ?string thumbnail or null if not found
     */
    private function getThumbnail(string $id = '')
    {
        // Load plugin configuration.
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey, 'files');
        $fileGrpsThumb = GeneralUtility::trimExplode(',', $extConf['fileGrpThumbs']);

        $thumbnail = null;

        if (!empty($this->physicalStructure)) {
            while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                if (empty($id)) {
                    $thumbnail = $this->physicalStructureInfo[$this->physicalStructure[1]]['files'][$fileGrpThumb] ?? null;
                } else {
                    $parentId = $this->smLinks['l2p'][$id][0] ?? null;
                    $thumbnail = $this->physicalStructureInfo[$parentId]['files'][$fileGrpThumb] ?? null;
                }

                if (!empty($thumbnail)) {
                    break;
                }
            }
        }
        return $thumbnail;
    }

    /**
     * @see AbstractDocument::getMetadata()
     */
    public function getMetadata(string $id, int $cPid = 0): array
    {
        $cPid = $this->ensureValidPid($cPid);

        if ($cPid == 0) {
            $this->logger->warning('Invalid PID for metadata definitions');
            return [];
        }

        $metadata = $this->getMetadataFromArray($id, $cPid);

        if (empty($metadata)) {
            return [];
        }

        $metadata = $this->processMetadataSections($id, $cPid, $metadata);

        if (!empty($metadata)) {
            $metadata = $this->setDefaultTitleAndDate($metadata);
        }

        return $metadata;
    }

    /**
     * Ensure that pId is valid.
     *
     * @access private
     *
     * @param integer $cPid
     *
     * @return integer
     */
    private function ensureValidPid(int $cPid): int
    {
        $cPid = max($cPid, 0);
        if ($cPid == 0 && ($this->cPid || $this->pid)) {
            // Retain current PID.
            $cPid = $this->cPid ?: $this->pid;
        }
        return $cPid;
    }

    /**
     * Get metadata from array.
     *
     * @access private
     *
     * @param string $id
     * @param integer $cPid
     *
     * @return array
     */
    private function getMetadataFromArray(string $id, int $cPid): array
    {
        if (!empty($this->metadataArray[$id]) && $this->metadataArray[0] == $cPid) {
            return $this->metadataArray[$id];
        }
        return $this->initializeMetadata('METS');
    }

    /**
     * Process metadata sections.
     *
     * @access private
     *
     * @param string $id
     * @param integer $cPid
     * @param array $metadata
     *
     * @return array
     */
    private function processMetadataSections(string $id, int $cPid, array $metadata): array
    {
        $mdIds = $this->getMetadataIds($id);
        if (empty($mdIds)) {
            // There is no metadata section for this structure node.
            return [];
        }
        // Array used as set of available section types (dmdSec, techMD, ...)
        $metadataSections = [];
        // Load available metadata formats and metadata sections.
        $this->loadFormats();
        $this->magicGetMdSec();

        $metadata['type'] = $this->getLogicalUnitType($id);

        if (!empty($this->mdSec)) {
            foreach ($mdIds as $dmdId) {
                $mdSectionType = $this->mdSec[$dmdId]['section'];

                if ($this->hasMetadataSection($metadataSections, $mdSectionType, 'dmdSec')) {
                    continue;
                }

                if (!$this->extractAndProcessMetadata($dmdId, $mdSectionType, $metadata, $cPid, $metadataSections)) {
                    continue;
                }

                $metadataSections[] = $mdSectionType;
            }
        }

        // Files are not expected to reference a dmdSec
        if (isset($this->fileInfos[$id]) || in_array('dmdSec', $metadataSections)) {
            return $metadata;
        } else {
            $this->logger->warning('No supported descriptive metadata found for logical structure with @ID "' . $id . '"');
            return [];
        }
    }

    /**
     * Get logical unit type.
     *
     * @access private
     *
     * @param string $id
     *
     * @return array
     */
    private function getLogicalUnitType(string $id): array
    {
        if (!empty($this->logicalUnits[$id])) {
            return [$this->logicalUnits[$id]['type']];
        } else {
            $struct = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $id . '"]/@TYPE');
            if (!empty($struct)) {
                return [(string) $struct[0]];
            }
        }
        return [];
    }

    /**
     * Extract and process metadata.
     *
     * @access private
     *
     * @param string $dmdId
     * @param string $mdSectionType
     * @param array $metadata
     * @param integer $cPid
     * @param array $metadataSections
     *
     * @return boolean
     */
    private function extractAndProcessMetadata(string $dmdId, string $mdSectionType, array &$metadata, int $cPid, array $metadataSections): bool
    {
        if ($this->hasMetadataSection($metadataSections, $mdSectionType, 'dmdSec')) {
            return true;
        }

        $metadataExtracted = $this->extractMetadataIfTypeSupported($dmdId, $mdSectionType, $metadata);

        if (!$metadataExtracted) {
            return false;
        }

        $additionalMetadata = $this->getAdditionalMetadataFromDatabase($cPid, $dmdId);
        // We need a \DOMDocument here, because SimpleXML doesn't support XPath functions properly.
        $domNode = dom_import_simplexml($this->mdSec[$dmdId]['xml']);
        $domXPath = new DOMXPath($domNode->ownerDocument);
        $this->registerNamespaces($domXPath);

        $this->processAdditionalMetadata($additionalMetadata, $domXPath, $domNode, $metadata);

        return true;
    }

    /**
     * Check if searched metadata section is stored in the array.
     *
     * @access private
     *
     * @param array $metadataSections
     * @param string $currentMetadataSection
     * @param string $searchedMetadataSection
     *
     * @return boolean
     */
    private function hasMetadataSection(array $metadataSections, string $currentMetadataSection, string $searchedMetadataSection): bool
    {
        return $currentMetadataSection === $searchedMetadataSection && in_array($searchedMetadataSection, $metadataSections);
    }

    /**
     * Process additional metadata.
     *
     * @access private
     *
     * @param array $additionalMetadata
     * @param DOMXPath $domXPath
     * @param DOMElement $domNode
     * @param array $metadata
     *
     * @return void
     */
    private function processAdditionalMetadata(array $additionalMetadata, DOMXPath $domXPath, DOMElement $domNode, array &$metadata): void
    {
        foreach ($additionalMetadata as $resArray) {
            $this->setMetadataFieldValues($resArray, $domXPath, $domNode, $metadata);
            $this->setDefaultMetadataValue($resArray, $metadata);
            $this->setSortableMetadataValue($resArray, $domXPath, $domNode, $metadata);
        }
    }

    /**
     * Set metadata field values.
     *
     * @access private
     *
     * @param array $resArray
     * @param DOMXPath $domXPath
     * @param DOMElement $domNode
     * @param array $metadata
     *
     * @return void
     */
    private function setMetadataFieldValues(array $resArray, DOMXPath $domXPath, DOMElement $domNode, array &$metadata): void
    {
        if ($resArray['format'] > 0 && !empty($resArray['xpath'])) {
            $values = $domXPath->evaluate($resArray['xpath'], $domNode);
            if ($values instanceof \DOMNodeList && $values->length > 0) {
                $metadata[$resArray['index_name']] = [];
                foreach ($values as $value) {
                    $metadata[$resArray['index_name']][] = trim((string) $value->nodeValue);
                }
            } elseif (!($values instanceof \DOMNodeList)) {
                $metadata[$resArray['index_name']] = [trim((string) $values)];
            }
        }
    }

    /**
     * Set default metadata value.
     *
     * @access private
     *
     * @param array $resArray
     * @param array $metadata
     *
     * @return void
     */
    private function setDefaultMetadataValue(array $resArray, array &$metadata): void
    {
        if (empty($metadata[$resArray['index_name']][0]) && strlen($resArray['default_value']) > 0) {
            $metadata[$resArray['index_name']] = [$resArray['default_value']];
        }
    }

    /**
     * Set sortable metadata value.
     *
     * @access private
     *
     * @param array $resArray
     * @param  $domXPath
     * @param DOMElement $domNode
     * @param array $metadata
     *
     * @return void
     */
    private function setSortableMetadataValue(array $resArray, DOMXPath $domXPath, DOMElement $domNode, array &$metadata): void
    {
        if (!empty($metadata[$resArray['index_name']]) && $resArray['is_sortable']) {
            if ($resArray['format'] > 0 && !empty($resArray['xpath_sorting'])) {
                $values = $domXPath->evaluate($resArray['xpath_sorting'], $domNode);
                if ($values instanceof \DOMNodeList && $values->length > 0) {
                    $metadata[$resArray['index_name'] . '_sorting'][0] = trim((string) $values->item(0)->nodeValue);
                } elseif (!($values instanceof \DOMNodeList)) {
                    $metadata[$resArray['index_name'] . '_sorting'][0] = trim((string) $values);
                }
            }
            if (empty($metadata[$resArray['index_name'] . '_sorting'][0])) {
                $metadata[$resArray['index_name'] . '_sorting'][0] = $metadata[$resArray['index_name']][0];
            }
        }
    }

    /**
     * Set default title and date if those metadata is not set.
     *
     * @access private
     *
     * @param array $metadata
     *
     * @return array
     */
    private function setDefaultTitleAndDate(array $metadata): array
    {
        // Set title to empty string if not present.
        if (empty($metadata['title'][0])) {
            $metadata['title'][0] = '';
            $metadata['title_sorting'][0] = '';
        }

        // Set title_sorting to title as default.
        if (empty($metadata['title_sorting'][0])) {
            $metadata['title_sorting'][0] = $metadata['title'][0];
        }

        // Set date to empty string if not present.
        if (empty($metadata['date'][0])) {
            $metadata['date'][0] = '';
        }

        return $metadata;
    }

    /**
     * Extract metadata if metadata type is supported.
     *
     * @access private
     *
     * @param string $dmdId descriptive metadata id
     * @param string $mdSectionType metadata section type
     * @param array &$metadata
     *
     * @return bool true if extraction successful, false otherwise
     */
    private function extractMetadataIfTypeSupported(string $dmdId, string $mdSectionType, array &$metadata)
    {
        // Is this metadata format supported?
        if (!empty($this->formats[$this->mdSec[$dmdId]['type']])) {
            if (!empty($this->formats[$this->mdSec[$dmdId]['type']]['class'])) {
                $class = $this->formats[$this->mdSec[$dmdId]['type']]['class'];
                // Get the metadata from class.
                if (class_exists($class)) {
                    $obj = GeneralUtility::makeInstance($class);
                    if ($obj instanceof MetadataInterface) {
                        $obj->extractMetadata($this->mdSec[$dmdId]['xml'], $metadata, GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey, 'general')['useExternalApisForMetadata']);
                        return true;
                    }
                } else {
                    $this->logger->warning('Invalid class/method "' . $class . '->extractMetadata()" for metadata format "' . $this->mdSec[$dmdId]['type'] . '"');
                }
            }
        } else {
            $this->logger->notice('Unsupported metadata format "' . $this->mdSec[$dmdId]['type'] . '" in ' . $mdSectionType . ' with @ID "' . $dmdId . '"');
        }
        return false;
    }

    /**
     * Get additional data from database.
     *
     * @access private
     *
     * @param int $cPid page id
     * @param string $dmdId descriptive metadata id
     *
     * @return array additional metadata data queried from database
     */
    private function getAdditionalMetadataFromDatabase(int $cPid, string $dmdId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        // Get all metadata with configured xpath and applicable format first.
        $resultWithFormat = $queryBuilder
            ->select(
                'tx_dlf_metadata.index_name AS index_name',
                'tx_dlf_metadataformat_joins.xpath AS xpath',
                'tx_dlf_metadataformat_joins.xpath_sorting AS xpath_sorting',
                'tx_dlf_metadata.is_sortable AS is_sortable',
                'tx_dlf_metadata.default_value AS default_value',
                'tx_dlf_metadata.format AS format'
            )
            ->from('tx_dlf_metadata')
            ->innerJoin(
                'tx_dlf_metadata',
                'tx_dlf_metadataformat',
                'tx_dlf_metadataformat_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_metadataformat_joins.parent_id',
                    'tx_dlf_metadata.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_metadataformat_joins',
                'tx_dlf_formats',
                'tx_dlf_formats_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_formats_joins.uid',
                    'tx_dlf_metadataformat_joins.encoded'
                )
            )
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', $cPid),
                $queryBuilder->expr()->eq('tx_dlf_metadata.l18n_parent', 0),
                $queryBuilder->expr()->eq('tx_dlf_metadataformat_joins.pid', $cPid),
                $queryBuilder->expr()->eq('tx_dlf_formats_joins.type', $queryBuilder->createNamedParameter($this->mdSec[$dmdId]['type']))
            )
            ->execute();
        // Get all metadata without a format, but with a default value next.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');
            // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $resultWithoutFormat = $queryBuilder
            ->select(
                'tx_dlf_metadata.index_name AS index_name',
                'tx_dlf_metadata.is_sortable AS is_sortable',
                'tx_dlf_metadata.default_value AS default_value',
                'tx_dlf_metadata.format AS format'
            )
            ->from('tx_dlf_metadata')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', $cPid),
                $queryBuilder->expr()->eq('tx_dlf_metadata.l18n_parent', 0),
                $queryBuilder->expr()->eq('tx_dlf_metadata.format', 0),
                $queryBuilder->expr()->neq('tx_dlf_metadata.default_value', $queryBuilder->createNamedParameter(''))
            )
            ->execute();
        // Merge both result sets.
        return array_merge($resultWithFormat->fetchAllAssociative(), $resultWithoutFormat->fetchAllAssociative());
    }

    /**
     * Get IDs of (descriptive and administrative) metadata sections
     * referenced by node of given $id. The $id may refer to either
     * a logical structure node or to a file.
     *
     * @access protected
     *
     * @param string $id The "@ID" attribute of the file node
     *
     * @return array
     */
    protected function getMetadataIds(string $id): array
    {
        // Load amdSecChildIds concordance
        $this->magicGetMdSec();
        $fileInfo = $this->getFileInfo($id);

        // Get DMDID and ADMID of logical structure node
        if (!empty($this->logicalUnits[$id])) {
            $dmdIds = $this->logicalUnits[$id]['dmdId'] ?? '';
            $admIds = $this->logicalUnits[$id]['admId'] ?? '';
        } else {
            $mdSec = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $id . '"]')[0];
            if ($mdSec) {
                $dmdIds = (string) $mdSec->attributes()->DMDID;
                $admIds = (string) $mdSec->attributes()->ADMID;
            } elseif (isset($fileInfo)) {
                $dmdIds = $fileInfo['dmdId'];
                $admIds = $fileInfo['admId'];
            } else {
                $dmdIds = '';
                $admIds = '';
            }
        }

        // Handle multiple DMDIDs/ADMIDs
        $allMdIds = explode(' ', $dmdIds);

        foreach (explode(' ', $admIds) as $admId) {
            if (isset($this->mdSec[$admId])) {
                // $admId references an actual metadata section such as techMD
                $allMdIds[] = $admId;
            } elseif (isset($this->amdSecChildIds[$admId])) {
                // $admId references a <mets:amdSec> element. Resolve child elements.
                foreach ($this->amdSecChildIds[$admId] as $childId) {
                    $allMdIds[] = $childId;
                }
            }
        }

        return array_filter(
            $allMdIds,
            function ($element) {
                return !empty($element);
            }
        );
    }

    /**
     * @see AbstractDocument::getFullText()
     */
    public function getFullText(string $id): string
    {
        $fullText = '';

        // Load fileGrps and check for full text files.
        $this->magicGetFileGrps();
        if ($this->hasFulltext) {
            $fullText = $this->getFullTextFromXml($id);
        }
        return $fullText;
    }

    /**
     * @see AbstractDocument::getStructureDepth()
     */
    public function getStructureDepth(string $logId)
    {
        $ancestors = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $logId . '"]/ancestor::*');
        if (!empty($ancestors)) {
            return count($ancestors);
        } else {
            return 0;
        }
    }

    /**
     * @see AbstractDocument::init()
     */
    protected function init(string $location, array $settings): void
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(get_class($this));
        $this->settings = $settings;
        // Get METS node from XML file.
        $this->registerNamespaces($this->xml);
        $mets = $this->xml->xpath('//mets:mets');
        if (!empty($mets)) {
            $this->mets = $mets[0];
            // Register namespaces.
            $this->registerNamespaces($this->mets);
        } else {
            if (!empty($location)) {
                $this->logger->error('No METS part found in document with location "' . $location . '".');
            } elseif (!empty($this->recordId)) {
                $this->logger->error('No METS part found in document with recordId "' . $this->recordId . '".');
            } else {
                $this->logger->error('No METS part found in current document.');
            }
        }
    }

    /**
     * @see AbstractDocument::loadLocation()
     */
    protected function loadLocation(string $location): bool
    {
        $fileResource = Helper::getUrl($location);
        if ($fileResource !== false) {
            $xml = Helper::getXmlFileAsString($fileResource);
            // Set some basic properties.
            if ($xml !== false) {
                $this->xml = $xml;
                return true;
            }
        }
        $this->logger->error('Could not load XML file from "' . $location . '"');
        return false;
    }

    /**
     * @see AbstractDocument::ensureHasFulltextIsSet()
     */
    protected function ensureHasFulltextIsSet(): void
    {
        // Are the fileGrps already loaded?
        if (!$this->fileGrpsLoaded) {
            $this->magicGetFileGrps();
        }
    }

    /**
     * @see AbstractDocument::setPreloadedDocument()
     */
    protected function setPreloadedDocument($preloadedDocument): bool
    {

        if ($preloadedDocument instanceof SimpleXMLElement) {
            $this->xml = $preloadedDocument;
            return true;
        }
        return false;
    }

    /**
     * @see AbstractDocument::getDocument()
     */
    protected function getDocument(): SimpleXMLElement
    {
        return $this->mets;
    }

    /**
     * This builds an array of the document's metadata sections
     *
     * @access protected
     *
     * @return array Array of metadata sections with their IDs as array key
     */
    protected function magicGetMdSec(): array
    {
        if (!$this->mdSecLoaded) {
            $this->loadFormats();

            foreach ($this->mets->xpath('./mets:dmdSec') as $dmdSecTag) {
                $dmdSec = $this->processMdSec($dmdSecTag);

                if ($dmdSec !== null) {
                    $this->mdSec[$dmdSec['id']] = $dmdSec;
                    $this->dmdSec[$dmdSec['id']] = $dmdSec;
                }
            }

            foreach ($this->mets->xpath('./mets:amdSec') as $amdSecTag) {
                $childIds = [];

                foreach ($amdSecTag->children('http://www.loc.gov/METS/') as $mdSecTag) {
                    if (!in_array($mdSecTag->getName(), self::ALLOWED_AMD_SEC)) {
                        continue;
                    }

                    // TODO: Should we check that the format may occur within this type (e.g., to ignore VIDEOMD within rightsMD)?
                    $mdSec = $this->processMdSec($mdSecTag);

                    if ($mdSec !== null) {
                        $this->mdSec[$mdSec['id']] = $mdSec;

                        $childIds[] = $mdSec['id'];
                    }
                }

                $amdSecId = (string) $amdSecTag->attributes()->ID;
                if (!empty($amdSecId)) {
                    $this->amdSecChildIds[$amdSecId] = $childIds;
                }
            }

            $this->mdSecLoaded = true;
        }
        return $this->mdSec;
    }

    /**
     * Gets the document's metadata sections
     *
     * @access protected
     *
     * @return array Array of metadata sections with their IDs as array key
     */
    protected function magicGetDmdSec(): array
    {
        $this->magicGetMdSec();
        return $this->dmdSec;
    }

    /**
     * Processes an element of METS `mdSecType`.
     *
     * @access protected
     *
     * @param SimpleXMLElement $element
     *
     * @return array|null The processed metadata section
     */
    protected function processMdSec(SimpleXMLElement $element): ?array
    {
        $mdId = (string) $element->attributes()->ID;
        if (empty($mdId)) {
            return null;
        }

        $this->registerNamespaces($element);

        $type = '';
        $mdType = $element->xpath('./mets:mdWrap[not(@MDTYPE="OTHER")]/@MDTYPE');
        $otherMdType = $element->xpath('./mets:mdWrap[@MDTYPE="OTHER"]/@OTHERMDTYPE');

        if (!empty($mdType) && !empty($this->formats[(string) $mdType[0]])) {
            $type = (string) $mdType[0];
            $xml = $element->xpath('./mets:mdWrap[@MDTYPE="' . $type . '"]/mets:xmlData/' . strtolower($type) . ':' . $this->formats[$type]['rootElement']);
        } elseif (!empty($otherMdType) && !empty($this->formats[(string) $otherMdType[0]])) {
            $type = (string) $otherMdType[0];
            $xml = $element->xpath('./mets:mdWrap[@MDTYPE="OTHER"][@OTHERMDTYPE="' . $type . '"]/mets:xmlData/' . strtolower($type) . ':' . $this->formats[$type]['rootElement']);
        }

        if (empty($xml)) {
            return null;
        }

        $this->registerNamespaces($xml[0]);

        return [
            'id' => $mdId,
            'section' => $element->getName(),
            'type' => $type,
            'xml' => $xml[0],
        ];
    }

    /**
     * This builds the file ID -> USE concordance
     *
     * @access protected
     *
     * @return array Array of file use groups with file IDs
     */
    protected function magicGetFileGrps(): array
    {
        if (!$this->fileGrpsLoaded) {
            // Get configured USE attributes.
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey, 'files');
            $useGrps = GeneralUtility::trimExplode(',', $extConf['fileGrpImages']);

            $configKeys = [
                'fileGrpThumbs',
                'fileGrpDownload',
                'fileGrpFulltext',
                'fileGrpAudio'
            ];

            foreach ($configKeys as $key) {
                if (!empty($extConf[$key])) {
                    $useGrps = array_merge($useGrps, GeneralUtility::trimExplode(',', $extConf[$key]));
                }
            }

            foreach ($useGrps as $useGrp) {
                // Perform XPath query for each configured USE attribute
                $fileGrps = $this->mets->xpath("./mets:fileSec/mets:fileGrp[@USE='$useGrp']");
                if (!empty($fileGrps)) {
                    foreach ($fileGrps as $fileGrp) {
                        foreach ($fileGrp->children('http://www.loc.gov/METS/')->file as $file) {
                            $fileId = (string) $file->attributes()->ID;
                            $this->fileGrps[$fileId] = $useGrp;
                            $this->fileInfos[$fileId] = [
                                'fileGrp' => $useGrp,
                                'admId' => (string) $file->attributes()->ADMID,
                                'dmdId' => (string) $file->attributes()->DMDID,
                            ];
                        }
                    }
                }
            }
            // Are there any fulltext files available?
            if (
                !empty($extConf['fileGrpFulltext'])
                && array_intersect(GeneralUtility::trimExplode(',', $extConf['fileGrpFulltext']), $this->fileGrps) !== []
            ) {
                $this->hasFulltext = true;
            }
            $this->fileGrpsLoaded = true;
        }
        return $this->fileGrps;
    }

    /**
     * @see AbstractDocument::prepareMetadataArray()
     */
    protected function prepareMetadataArray(int $cPid): void
    {
        $ids = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID]/@ID');
        // Get all logical structure nodes with metadata.
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $this->metadataArray[(string) $id] = $this->getMetadata((string) $id, $cPid);
            }
        }
        // Set current PID for metadata definitions.
    }

    /**
     * This returns $this->mets via __get()
     *
     * @access protected
     *
     * @return SimpleXMLElement The XML's METS part as SimpleXMLElement object
     */
    protected function magicGetMets(): SimpleXMLElement
    {
        return $this->mets;
    }

    /**
     * @see AbstractDocument::magicGetPhysicalStructure()
     */
    protected function magicGetPhysicalStructure(): array
    {
        // Is there no physical structure array yet?
        if (!$this->physicalStructureLoaded) {
            // Does the document have a structMap node of type "PHYSICAL"?
            $elementNodes = $this->mets->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div[@TYPE="physSequence"]/mets:div');
            if (!empty($elementNodes)) {
                // Get file groups.
                $fileUse = $this->magicGetFileGrps();
                // Get the physical sequence's metadata.
                $physicalNodes = $this->mets->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div[@TYPE="physSequence"]');
                $firstNode = $physicalNodes[0];
                $id = (string) $firstNode['ID'];
                $this->physicalStructureInfo[$id]['id'] = $id;
                $this->physicalStructureInfo[$id]['dmdId'] = isset($firstNode['DMDID']) ? (string) $firstNode['DMDID'] : '';
                $this->physicalStructureInfo[$id]['admId'] = isset($firstNode['ADMID']) ? (string) $firstNode['ADMID'] : '';
                $this->physicalStructureInfo[$id]['order'] = isset($firstNode['ORDER']) ? (string) $firstNode['ORDER'] : '';
                $this->physicalStructureInfo[$id]['label'] = isset($firstNode['LABEL']) ? (string) $firstNode['LABEL'] : '';
                $this->physicalStructureInfo[$id]['orderlabel'] = isset($firstNode['ORDERLABEL']) ? (string) $firstNode['ORDERLABEL'] : '';
                $this->physicalStructureInfo[$id]['type'] = (string) $firstNode['TYPE'];
                $this->physicalStructureInfo[$id]['contentIds'] = isset($firstNode['CONTENTIDS']) ? (string) $firstNode['CONTENTIDS'] : '';

                $this->getFileRepresentation($id, $firstNode);

                $this->physicalStructure = array_merge([$id], $this->getPhysicalElements($elementNodes, $fileUse));
            }
            $this->physicalStructureLoaded = true;
        }
        return $this->physicalStructure;
    }

    /**
     * Get the file representations from fileSec node.
     *
     * @access private
     *
     * @param string $id
     * @param SimpleXMLElement $physicalNode
     *
     * @return void
     */
    private function getFileRepresentation(string $id, SimpleXMLElement $physicalNode): void
    {
        // Get file groups.
        $fileUse = $this->magicGetFileGrps();

        foreach ($physicalNode->children('http://www.loc.gov/METS/')->fptr as $fptr) {
            $fileId = (string) $fptr->attributes()->FILEID;
            // Check if file has valid @USE attribute.
            if (!empty($fileUse[$fileId])) {
                $this->physicalStructureInfo[$id]['files'][$fileUse[$fileId]] = $fileId;
            }
        }
    }

    /**
     * Build the physical elements' array from the physical structMap node.
     *
     * @access private
     *
     * @param array $elementNodes
     * @param array $fileUse
     *
     * @return array
     */
    private function getPhysicalElements(array $elementNodes, array $fileUse): array
    {
        $elements = [];
        $id = '';

        foreach ($elementNodes as $elementNode) {
            $id = (string) $elementNode['ID'];
            $order = (int) $elementNode['ORDER'];
            $elements[$order] = $id;
            $this->physicalStructureInfo[$elements[$order]]['id'] = $id;
            $this->physicalStructureInfo[$elements[$order]]['dmdId'] = isset($elementNode['DMDID']) ? (string) $elementNode['DMDID'] : '';
            $this->physicalStructureInfo[$elements[$order]]['admId'] = isset($elementNode['ADMID']) ? (string) $elementNode['ADMID'] : '';
            $this->physicalStructureInfo[$elements[$order]]['order'] = isset($elementNode['ORDER']) ? (string) $elementNode['ORDER'] : '';
            $this->physicalStructureInfo[$elements[$order]]['label'] = isset($elementNode['LABEL']) ? (string) $elementNode['LABEL'] : '';
            $this->physicalStructureInfo[$elements[$order]]['orderlabel'] = isset($elementNode['ORDERLABEL']) ? (string) $elementNode['ORDERLABEL'] : '';
            $this->physicalStructureInfo[$elements[$order]]['type'] = (string) $elementNode['TYPE'];
            $this->physicalStructureInfo[$elements[$order]]['contentIds'] = isset($elementNode['CONTENTIDS']) ? (string) $elementNode['CONTENTIDS'] : '';
            // Get the file representations from fileSec node.
            foreach ($elementNode->children('http://www.loc.gov/METS/')->fptr as $fptr) {
                // Check if file has valid @USE attribute.
                if (!empty($fileUse[(string) $fptr->attributes()->FILEID])) {
                    $this->physicalStructureInfo[$elements[$order]]['files'][$fileUse[(string) $fptr->attributes()->FILEID]] = (string) $fptr->attributes()->FILEID;
                }
            }
        }

        // Sort array by keys (= @ORDER).
        ksort($elements);
        // Set total number of pages/tracks.
        $this->numPages = count($elements);

        return $elements;
    }

    /**
     * @see AbstractDocument::magicGetSmLinks()
     */
    protected function magicGetSmLinks(): array
    {
        if (!$this->smLinksLoaded) {
            $smLinks = $this->mets->xpath('./mets:structLink/mets:smLink');
            if (!empty($smLinks)) {
                foreach ($smLinks as $smLink) {
                    $this->smLinks['l2p'][(string) $smLink->attributes('http://www.w3.org/1999/xlink')->from][] = (string) $smLink->attributes('http://www.w3.org/1999/xlink')->to;
                    $this->smLinks['p2l'][(string) $smLink->attributes('http://www.w3.org/1999/xlink')->to][] = (string) $smLink->attributes('http://www.w3.org/1999/xlink')->from;
                }
            }
            $this->smLinksLoaded = true;
        }
        return $this->smLinks;
    }

    /**
     * @see AbstractDocument::magicGetThumbnail()
     */
    protected function magicGetThumbnail(bool $forceReload = false): string
    {
        if (
            !$this->thumbnailLoaded
            || $forceReload
        ) {
            // Retain current PID.
            $cPid = $this->cPid ?: $this->pid;
            if (!$cPid) {
                $this->logger->error('Invalid PID ' . $cPid . ' for structure definitions');
                $this->thumbnailLoaded = true;
                return $this->thumbnail;
            }
            // Load extension configuration.
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::$extKey, 'files');
            if (empty($extConf['fileGrpThumbs'])) {
                $this->logger->warning('No fileGrp for thumbnails specified');
                $this->thumbnailLoaded = true;
                return $this->thumbnail;
            }
            $strctId = $this->getToplevelId();
            $metadata = $this->getToplevelMetadata($cPid);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_structures');

            // Get structure element to get thumbnail from.
            $result = $queryBuilder
                ->select('tx_dlf_structures.thumbnail AS thumbnail')
                ->from('tx_dlf_structures')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_structures.pid', $cPid),
                    $queryBuilder->expr()->eq('tx_dlf_structures.index_name', $queryBuilder->expr()->literal($metadata['type'][0])),
                    Helper::whereExpression('tx_dlf_structures')
                )
                ->setMaxResults(1)
                ->execute();

            $allResults = $result->fetchAllAssociative();

            if (count($allResults) == 1) {
                $resArray = $allResults[0];
                // Get desired thumbnail structure if not the toplevel structure itself.
                if (!empty($resArray['thumbnail'])) {
                    $strctType = Helper::getIndexNameFromUid($resArray['thumbnail'], 'tx_dlf_structures', $cPid);
                    // Check if this document has a structure element of the desired type.
                    $strctIds = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@TYPE="' . $strctType . '"]/@ID');
                    if (!empty($strctIds)) {
                        $strctId = (string) $strctIds[0];
                    }
                }
                // Load smLinks.
                $this->magicGetSmLinks();
                // Get thumbnail location.
                $fileGrpsThumb = GeneralUtility::trimExplode(',', $extConf['fileGrpThumbs']);
                while ($fileGrpThumb = array_shift($fileGrpsThumb)) {
                    if (
                        $this->magicGetPhysicalStructure()
                        && !empty($this->smLinks['l2p'][$strctId])
                        && !empty($this->physicalStructureInfo[$this->smLinks['l2p'][$strctId][0]]['files'][$fileGrpThumb])
                    ) {
                        $this->thumbnail = $this->getFileLocation($this->physicalStructureInfo[$this->smLinks['l2p'][$strctId][0]]['files'][$fileGrpThumb]);
                        break;
                    } elseif (
                        !empty($this->physicalStructure)
                        && !empty($this->physicalStructureInfo[$this->physicalStructure[1]]['files'][$fileGrpThumb])
                    ) {
                        $this->thumbnail = $this->getFileLocation($this->physicalStructureInfo[$this->physicalStructure[1]]['files'][$fileGrpThumb]);
                        break;
                    }
                }
            } else {
                $this->logger->error('No structure of type "' . $metadata['type'][0] . '" found in database');
            }
            $this->thumbnailLoaded = true;
        }
        return $this->thumbnail;
    }

    /**
     * @see AbstractDocument::getToplevelId()
     */
    public function getToplevelId(): string
    {
        if (empty($this->toplevelId)) {
            // Get all logical structure nodes with metadata, but without associated METS-Pointers.
            $divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID and not(./mets:mptr)]');
            if (!empty($divs)) {
                // Load smLinks.
                $this->magicGetSmLinks();
                foreach ($divs as $div) {
                    $id = (string) $div['ID'];
                    // Are there physical structure nodes for this logical structure?
                    if (array_key_exists($id, $this->smLinks['l2p'])) {
                        // Yes. That's what we're looking for.
                        $this->toplevelId = $id;
                        break;
                    } elseif (empty($this->toplevelId)) {
                        // No. Remember this anyway, but keep looking for a better one.
                        $this->toplevelId = $id;
                    }
                }
            }
        }
        return $this->toplevelId;
    }

    /**
     * Try to determine URL of parent document.
     *
     * @access public
     *
     * @return string
     */
    public function magicGetParentHref(): string
    {
        if (empty($this->parentHref)) {
            // Get the closest ancestor of the current document which has a MPTR child.
            $parentMptr = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $this->toplevelId . '"]/ancestor::mets:div[./mets:mptr][1]/mets:mptr');
            if (!empty($parentMptr)) {
                $this->parentHref = (string) $parentMptr[0]->attributes('http://www.w3.org/1999/xlink')->href;
            }
        }

        return $this->parentHref;
    }

    /**
     * This magic method is executed prior to any serialization of the object
     * @see __wakeup()
     *
     * @access public
     *
     * @return array Properties to be serialized
     */
    public function __sleep(): array
    {
        // SimpleXMLElement objects can't be serialized, thus save the XML as string for serialization
        $this->asXML = $this->xml->asXML();
        return ['pid', 'recordId', 'parentId', 'asXML'];
    }

    /**
     * This magic method is used for setting a string value for the object
     *
     * @access public
     *
     * @return string String representing the METS object
     */
    public function __toString(): string
    {
        $xml = new \DOMDocument('1.0', 'utf-8');
        $xml->appendChild($xml->importNode(dom_import_simplexml($this->mets), true));
        $xml->formatOutput = true;
        return $xml->saveXML();
    }

    /**
     * This magic method is executed after the object is deserialized
     * @see __sleep()
     *
     * @access public
     *
     * @return void
     */
    public function __wakeup(): void
    {
        $xml = Helper::getXmlFileAsString($this->asXML);
        if ($xml !== false) {
            $this->asXML = '';
            $this->xml = $xml;
            // Rebuild the unserializable properties.
            $this->init('', $this->settings);
        } else {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
            $this->logger->error('Could not load XML after deserialization');
        }
    }
}
