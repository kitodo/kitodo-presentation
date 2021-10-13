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

use Kitodo\Dlf\Format\Item;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Model3D class for the 'dlf' extension.
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
final class Model3D extends Document
{
    /**
     * The extension key
     *
     * @var string
     * @static
     * @access public
     */
    public static $extKey = 'dlf';

    /**
     * This holds the XML file's ITEM part as \SimpleXMLElement object
     *
     * @var \SimpleXMLElement
     * @access protected
     */
    protected $item;

    /**
     * {@inheritDoc}
     * @see Document::getDownloadLocation($id)
     */
    public function getDownloadLocation($id)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::getFileLocation($id)
     */
    public function getFileLocation($id)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::getFileMimeType($id)
     */
    public function getFileMimeType($id)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::getFileLocation($id)
     */
    public function getLogicalStructure($id, $recursive = false)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::getMetadata($id, $cPid = 0)
     */
    public function getMetadata($id, $cPid = 0)
    {
        // Get metadata from parsed metadata array if available.
        if (
            !empty($this->metadataArray[$id])
            && $this->metadataArray[0] == $cPid
        ) {
            return $this->metadataArray[$id];
        }
        // Initialize metadata array with empty values.
        $metadata = [
            'title' => [],
            'description' => [],
            'author' => [],
            'preview_image' => [],
            'upload' => [],
            'document_format' => ['ITEM']
        ];

        $item = GeneralUtility::makeInstance(Item::class);
        $item->extractMetadata($this->xml, $metadata);

        return $metadata;
    }

    /**
     * {@inheritDoc}
     * @see Document::establishRecordId($pid)
     */
    protected function establishRecordId($pid)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::getDocument()
     */
    protected function getDocument()
    {
        return $this->item;
    }

    /**
     * {@inheritDoc}
     * @see Document::init()
     */
    protected function init()
    {
        // Get ITEM node from XML file.
        $item = $this->xml->xpath('/response/item[@key="0"]');
        if (!empty($item)) {
            $this->item = $item;
        } else {
            $this->logger->error('No ITEM part found in document with UID ' . $this->uid);
        }
    }

    /**
     * {@inheritDoc}
     * @see Document::loadLocation($location)
     */
    protected function loadLocation($location)
    {
        $xml = $this->loadXMLLocation($location);
        // Set some basic properties.
        if ($xml !== false) {
            $this->xml = $xml;
            return true;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     * @see Document::getParentDocumentUidForSaving($pid, $core, $owner)
     */
    protected function getParentDocumentUidForSaving($pid, $core, $owner)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::prepareMetadataArray($cPid)
     */
    protected function prepareMetadataArray($cPid)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::setPreloadedDocument($preloadedDocument)
     */
    protected function setPreloadedDocument($preloadedDocument)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::_getPhysicalStructure()
     */
    protected function _getPhysicalStructure()
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::_getSmLinks()
     */
    protected function _getSmLinks()
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::_getThumbnail($forceReload = false)
     */
    protected function _getThumbnail($forceReload = false)
    {
        // Nothing to do here, at the moment
    }

    /**
     * {@inheritDoc}
     * @see Document::_getToplevelId()
     */
    protected function _getToplevelId()
    {
        // Nothing to do here, at the moment
    }
}