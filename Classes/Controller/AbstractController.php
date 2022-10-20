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
use TYPO3\CMS\Core\Database\ConnectionPool;
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
        if (empty($this->requestData['page'])) {
            $this->requestData['page'] = 1;
        }
        $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);

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
     * @param array $requestData: The request data
     *
     * @return void
     */
    protected function loadDocument($requestData)
    {
        // Try to get document format from database
        if (!empty($requestData['id'])) {

            $doc = null;

            if (MathUtility::canBeInterpretedAsInteger($requestData['id'])) {
                // find document from repository by uid
                $this->document = $this->documentRepository->findOneByIdAndSettings((int) $requestData['id']);
                if ($this->document) {
                    $doc = Doc::getInstance($this->document->getLocation(), $this->settings, true);
                } else {
                    $this->logger->error('Invalid UID "' . $requestData['id'] . '" or PID "' . $this->settings['storagePid'] . '" for document loading');
                }
            } else if (GeneralUtility::isValidUrl($requestData['id'])) {

                $doc = Doc::getInstance($requestData['id'], $this->settings, true);

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

                    $this->document->setLocation($requestData['id']);
                } else {
                    $this->logger->error('Invalid location given "' . $requestData['id'] . '" for document loading');
                }
            }

            if ($this->document !== null && $doc !== null) {
                $this->document->setDoc($doc);
            }

        } elseif (!empty($requestData['recordId'])) {

            $this->document = $this->documentRepository->findOneByRecordId($requestData['recordId']);

            if ($this->document !== null) {
                $doc = Doc::getInstance($this->document->getLocation(), $this->settings, true);
                if ($this->document !== null && $doc !== null) {
                    $this->document->setDoc($doc);
                } else {
                    $this->logger->error('Failed to load document with record ID "' . $requestData['recordId'] . '"');
                }
            }
        } else {
            $this->logger->error('Invalid ID "' . $requestData['id'] . '" or PID "' . $this->settings['storagePid'] . '" for document loading');
        }
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
