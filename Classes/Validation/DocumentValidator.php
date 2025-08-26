<?php

declare(strict_types=1);

/*
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Validation;

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for document validation. Currently used for validating metadata
 * fields but in the future should be extended also for other fields.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DocumentValidator
{
     /**
     * @access protected
     * @var Logger This holds the logger
     */
    protected Logger $logger;

    /**
     * @access private
     * @var array
     */
    private array $metadata;

    /**
     * @access private
     * @var array
     */
    private array $requiredMetadataFields;

    /**
     * @access private
     * @var ?\SimpleXMLElement
     */
    private ?\SimpleXMLElement $xml;

    /**
     * Constructs DocumentValidator instance.
     *
     * @access public
     *
     * @param array $metadata
     * @param array $requiredMetadataFields
     *
     * @return void
     */
    public function __construct(array $metadata = [], array $requiredMetadataFields = [], ?\SimpleXMLElement $xml = null)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->metadata = $metadata;
        $this->requiredMetadataFields = $requiredMetadataFields;
        $this->xml = $xml;
    }

    /**
     * Check if metadata array contains all mandatory fields before save.
     *
     * @access public
     *
     * @return bool
     */
    public function hasAllMandatoryMetadataFields(): bool
    {
        if ($this->metadata['is_administrative'][0]) {
            foreach ($this->requiredMetadataFields as $requiredMetadataField) {
                if (empty($this->metadata[$requiredMetadataField][0])) {
                    $this->logger->error('Missing required metadata field "' . $requiredMetadataField . '".');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if xml contains at least one logical structure with given type.
     *
     * @access public
     * 
     * @param string $type e.g. documentary, newspaper or object
     *
     * @return bool
     */
    public function hasCorrectLogicalStructure(string $type): bool
    {
        $expectedNodes = $this->xml->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="' . $type . '"]');
        if ($expectedNodes) {
            return true;
        }

        $existingNodes = $this->xml->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div');
        if ($existingNodes) {
            $this->logger->error('Document contains logical structure but @TYPE="' . $type . '" is missing.');
            return false;
        }

        $this->logger->error('Document does not contain logical structure.');
        return false;
    }

    /**
     * Check if xml contains at least one physical structure with type 'physSequence'.
     *
     * @access public
     *
     * @return bool
     */
    public function hasCorrectPhysicalStructure(): bool
    {
        $physSequenceNodes = $this->xml->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div[@TYPE="physSequence"]');
        if ($physSequenceNodes) {
            return true;
        }

        $physicalStructureNodes = $this->xml->xpath('./mets:structMap[@TYPE="PHYSICAL"]/mets:div');
        if ($physicalStructureNodes) {
            $this->logger->error('Document contains physical structure but @TYPE="physSequence" is missing.');
            return false;
        }

        $this->logger->error('Document does not contain physical structure.');
        return false;
    }
}
