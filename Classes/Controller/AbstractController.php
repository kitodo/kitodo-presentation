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

namespace Kitodo\Dlf\Controller;

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Abstract controller class for most of the plugin controller.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DocumentRepository
     */
    protected $documentRepository;

    /**
     * @param DocumentRepository $documentRepository
     */
    public function injectDocumentRepository(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * This holds the current document
     *
     * @var \Kitodo\Dlf\Domain\Model\Document
     * @access protected
     */
    protected $document;

    /**
     * @var array
     */
    protected $documentArray;

    /**
     * @var array
     * @access protected
     */
    protected $extConf;

    /**
     * This holds the request parameter
     *
     * @var array
     * @access protected
     */
    protected $requestData;

    /**
     * This holds some common data for the fluid view
     *
     * @var array
     * @access protected
     */
    protected $viewData;

    /**
     * Initialize the plugin controller
     *
     * @access protected
     * @return void
     */
    protected function initialize()
    {
        $this->requestData = GeneralUtility::_GPmerged('tx_dlf');

        // Sanitize user input to prevent XSS attacks.
        $this->sanitizeRequestData();

        // Get extension configuration.
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        $this->viewData = [
            'pageUid' => $GLOBALS['TSFE']->id,
            'uniqueId'=> uniqid(),
            'requestData' => $this->requestData
        ];
    }

    /**
     * Loads the current document into $this->document
     *
     * @access protected
     *
     * @param mixed $documentId: The document's UID (fallback: $this->requestData[id])
     *
     * @return void
     */
    protected function loadDocument($documentId = 0)
    {
        // Get document ID from request data if not passed as parameter.
        if ($documentId === 0 && !empty($this->requestData['id'])) {
            $documentId = $this->requestData['id'];
        }

        // Try to get document format from database
        if (!empty($documentId)) {

            $doc = null;

            if (MathUtility::canBeInterpretedAsInteger($documentId)) {
                // find document from repository by uid
                $this->document = $this->documentRepository->findOneByIdAndSettings($documentId);
                if ($this->document) {
                    $doc = Doc::getInstance($this->document->getLocation(), $this->settings, true);
                } else {
                    $this->logger->error('Invalid UID "' . $documentId . '" or PID "' . $this->settings['storagePid'] . '" for document loading');
                }
            } else if (GeneralUtility::isValidUrl($documentId)) {


                $doc = Doc::getInstance($documentId, $this->settings, true);

                if (isset($this->settings['multiViewType']) && $doc->tableOfContents[0]['type'] === $this->settings['multiViewType']) {
                    $childDocuments = $doc->tableOfContents[0]['children'];
                    foreach ($childDocuments as $document) {
                        $this->documentArray[] = Doc::getInstance($document['points'], $this->settings, true);
                    }
                } else {
                    $this->documentArray[] = $doc;
                }
                if ($this->requestData['multipleSource'] && is_array($this->requestData['multipleSource'])) {
                    foreach ($this->requestData['multipleSource'] as $location) {
                        $document = Doc::getInstance($location, $this->settings, true);
                        if ($document !== null) {
                            $this->documentArray[] = $document;
                        }
                    }
                }

                if ($doc !== null) {
                    if ($doc->recordId) {
                        $this->document = $this->documentRepository->findOneByRecordId($doc->recordId);
                    }

                    if ($this->document === null) {
                        // create new dummy Document object
                        $this->document = GeneralUtility::makeInstance(Document::class);
                    }

                    // Make sure configuration PID is set when applicable
                    if ($doc->cPid == 0) {
                        $doc->cPid = max(intval($this->settings['storagePid']), 0);
                    }

                    $this->document->setLocation($documentId);
                } else {
                    $this->logger->error('Invalid location given "' . $documentId . '" for document loading');
                }
            }

            if ($this->document !== null && $doc !== null) {
                $this->document->setDoc($doc);
            }

        } elseif (!empty($this->requestData['recordId'])) {

            $this->document = $this->documentRepository->findOneByRecordId($this->requestData['recordId']);

            if ($this->document !== null) {
                $doc = Doc::getInstance($this->document->getLocation(), $this->settings, true);
                if ($this->document !== null && $doc !== null) {
                    $this->document->setDoc($doc);
                } else {
                    $this->logger->error('Failed to load document with record ID "' . $this->requestData['recordId'] . '"');
                }
            }
        } else {
            $this->logger->error('Invalid ID "' . $documentId . '" or PID "' . $this->settings['storagePid'] . '" for document loading');
        }
    }

    /**
     * Configure URL for proxy.
     *
     * @access protected
     *
     * @param string $url URL for proxy configuration
     *
     * @return void
     */
    protected function configureProxyUrl(&$url) {
        $this->uriBuilder->reset()
            ->setTargetPageUid($GLOBALS['TSFE']->id)
            ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']))
            ->setArguments([
                'eID' => 'tx_dlf_pageview_proxy',
                'url' => $url,
                'uHash' => GeneralUtility::hmac($url, 'PageViewProxy')
                ])
            ->build();
    }

    /**
     * Checks if doc is missing or is empty (no pages)
     *
     * @return boolean
     */
    protected function isDocMissingOrEmpty()
    {
        $multiViewType = $this->settings['multiViewType'] ?? '';
        return $this->isDocMissing() || ($this->document->getDoc()->numPages < 1 && $this->document->getDoc()->tableOfContents[0]['type'] !== $multiViewType);
    }

    /**
     * Checks if doc is missing
     *
     * @return boolean
     */
    protected function isDocMissing()
    {
        return $this->document === null || $this->document->getDoc() === null;
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Safely gets Parameters from request
     * if they exist
     *
     * @param string $parameterName
     *
     * @return null|string|array
     */
    protected function getParametersSafely($parameterName)
    {
        if ($this->request->hasArgument($parameterName)) {
            return $this->request->getArgument($parameterName);
        }
        return null;
    }

    /**
     * Sanitize input variables.
     *
     * @access protected
     *
     * @return void
     */
    protected function sanitizeRequestData()
    {
        // tx_dlf[id] may only be an UID or URI.
        if (
            !empty($this->requestData['id'])
            && !MathUtility::canBeInterpretedAsInteger($this->requestData['id'])
            && !GeneralUtility::isValidUrl($this->requestData['id'])
        ) {
            $this->logger->warning('Invalid ID or URI "' . $this->requestData['id'] . '" for document loading');
            unset($this->requestData['id']);
        }

        // tx_dlf[page] may only be a positive integer or valid XML ID.
        if (
            !empty($this->requestData['page'])
            && !MathUtility::canBeInterpretedAsInteger($this->requestData['page'])
            && !Helper::isValidXmlId($this->requestData['page'])
        ) {
            $this->requestData['page'] = 1;
        }

        // tx_dlf[double] may only be 0 or 1.
        $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
    }

    /**
     * Sets page value.
     *
     * @access protected
     *
     * @return void
     */
    protected function setPage() {
        if (!empty($this->requestData['logicalPage'])) {
            $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
            // The logical page parameter should not appear again
            unset($this->requestData['logicalPage']);
        }

        $this->setDefaultPage();
    }

    /**
     * Sets default page value.
     *
     * @access protected
     *
     * @return void
     */
    protected function setDefaultPage() {
        // Set default values if not set.
        // $this->requestData['page'] may be integer or string (physical structure @ID)
        if (
            (int) $this->requestData['page'] > 0
            || empty($this->requestData['page']
                || is_array($this->requestData['docPage']))
        ) {
            if (isset($this->settings['multiViewType']) && $this->document->getDoc()->tableOfContents[0]['type'] === $this->settings['multiViewType']) {
                $i = 0;
                foreach ($this->documentArray as $document) {
                    if ($document !== null) {
                        $this->requestData['docPage'][$i] = MathUtility::forceIntegerInRange((int) $this->requestData['docPage'][$i], 1, $document->numPages, 1);
                        $i++;
                    }
                }
            } else {
                $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
            }
        } else {
            $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
        }
        // reassign viewData to get correct page
        $this->viewData['requestData'] = $this->requestData;
    }

    /**
     * This is the constructor
     *
     * @access public
     *
     * @return void
     */
    public function __construct()
    {
        $this->initialize();
    }
}
