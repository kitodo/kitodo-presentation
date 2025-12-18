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

namespace Kitodo\Dlf\Service;

use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Domain\Model\Document;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * (Service For Abstract Controller) Service that decouples the loading of documents from each controller to only once.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DocumentService
{
    /**
     * @access protected
     * @var Document|null This holds the current document
     */
    protected ?Document $document = null;
    /**
     * @access protected
     * @var array
     */
    protected $settings;
    /**
     * @access protected
     * @var array
     */
    protected $logger;
    protected DocumentRepository $documentRepository;

    public function __construct()
    {
        $this->documentRepository = GeneralUtility::makeInstance(DocumentRepository::class);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    protected function init()
    {
        return true;
    }
    protected function reset()
    {
    }
    /**
     * @access public
     * @param int $recordId
     * @param array $settings
     * @return ?Document
     */
    public function getDocument($documentId, $recordId, $settings)
    {
        if ($this->document === null) {
            $this->serviceLoadDocument($documentId, $recordId, $settings);
        }
        return $this->document;
    }
    /**
     * @access public
     * @param int $recordId
     * @param array $settings
     */
    private function serviceLoadDocument($documentId, $recordId, $settings)
    {
        $this->settings = $settings;
        // Get document ID from request data if not passed as parameter.
        if (!$documentId && !empty($recordId)) {
            $documentId = $recordId;
        }

        // Try to get document format from database
        if (!empty($documentId)) {


            $doc = null;

            if (MathUtility::canBeInterpretedAsInteger($documentId)) {
                $doc = $this->getDocumentByUid($documentId);
            } elseif (GeneralUtility::isValidUrl($documentId)) {
                $doc = $this->getDocumentByUrl($documentId);
            }

            if ($this->document !== null && $doc !== null) {
                $this->document->setCurrentDocument($doc);
            }

        } elseif (!empty($this->requestData['recordId'])) {

            $this->document = $this->documentRepository->findOneByRecordId($this->requestData['recordId']);

            if ($this->document !== null) {
                $doc = AbstractDocument::getInstance($this->document->getLocation(), $this->settings, false);
                if ($doc !== null) {
                    $this->document->setCurrentDocument($doc);
                } else {
                    $this->logger->error('Failed to load document with record ID "' . $this->requestData['recordId'] . '"');
                }
            }
        } else {
            $this->logger->error('Invalid ID "' . $documentId . '" or PID "' . $this->settings['storagePid'] . '" for document loading');
        }
    }
    /**
     * Get document from repository by uid.
     *
     * @access private
     *
     * @param int $documentId The document's UID
     *
     * @return AbstractDocument
     */
    private function getDocumentByUid(int $documentId): AbstractDocument|null
    {
        $doc = null;
        $this->document = $this->documentRepository->findOneByIdAndSettings($documentId);

        if ($this->document) {
            $doc = AbstractDocument::getInstance($this->document->getLocation(), $this->settings, false);
        } else {
            $this->logger->error('Invalid UID "' . $documentId . '" or PID "' . $this->settings['storagePid'] . '" for document loading');
        }

        return $doc;
    }

    /**
     * Get document by URL.
     *
     * @access private
     *
     * @param string $documentId The document's URL
     *
     * @return AbstractDocument
     */
    private function getDocumentByUrl(string $documentId)
    {
        $doc = AbstractDocument::getInstance($documentId, $this->settings, false);

        if ($doc !== null) {
            if ($doc->recordId) {
                // find document from repository by recordId
                $docFromRepository = $this->documentRepository->findOneByRecordId($doc->recordId);
                if ($docFromRepository !== null) {
                    $this->document = $docFromRepository;
                } else {
                    // create new dummy Document object
                    $this->document = GeneralUtility::makeInstance(Document::class);
                }
            }

            // Make sure configuration PID is set when applicable
            if ($doc->cPid == 0) {
                $doc->cPid = max($this->settings['storagePid'], 0);
            }

            $this->document->setLocation($documentId);
        } else {
            $this->logger->error('Invalid location given "' . $documentId . '" for document loading');
        }

        return $doc;
    }
}
