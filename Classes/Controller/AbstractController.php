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

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Configuration\UseGroupsConfiguration;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Abstract controller class for most of the plugin controller.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @abstract
 */
abstract class AbstractController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @access protected
     * @var DocumentRepository
     */
    protected DocumentRepository $documentRepository;

    /**
     * @access public
     *
     * @param DocumentRepository $documentRepository
     *
     * @return void
     */
    public function injectDocumentRepository(DocumentRepository $documentRepository): void
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * @access protected
     * @var Document|null This holds the current document
     */
    protected ?Document $document = null;

    /**
     * @access protected
     * @var array
     */
    protected $multiViewDocuments = [];

    /**
     * @access protected
     * @var array
     */
    protected array $extConf;

    /**
     * @access protected
     * @var array This holds the request parameter
     */
    protected array $requestData;

    /**
     * @access protected
     * @var array This holds some common data for the fluid view
     */
    protected array $viewData;

    /**
     * @access protected
     * @var int This holds the current page UID (only in frontend context)
     */
    protected int $pageUid;

    /**
     * Holds the configured useGroups as array.
     *
     * @access protected
     * @var \Kitodo\Dlf\Configuration\UseGroupsConfiguration
     */
    protected UseGroupsConfiguration $useGroupsConfiguration;

    /**
     * Initialize the plugin controller
     *
     * @access protected
     *
     * @param RequestInterface $request the HTTP request
     *
     * @return void
     */
    protected function initialize(RequestInterface $request): void
    {
        /** @var Request $request */
        $this->requestData = $request->getQueryParams()['tx_dlf'] ?? [];
        $this->requestData['page'] = $this->requestData['page'] ?? 1;
        if ($request->getAttribute('applicationType') === 1) {
            $this->pageUid = $request->getAttribute('routing')->getPageId();
        }

        // Sanitize user input to prevent XSS attacks.
        $this->sanitizeRequestData();

        // Get extension configuration.
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        $this->useGroupsConfiguration = UseGroupsConfiguration::getInstance();

        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $this->viewData = [
            'pageUid' => $this->pageUid ?? 0,
            'uniqueId' => uniqid(),
            'requestData' => $this->requestData
        ];



        try {
            $this->viewData['publicResourcePath'] = PathUtility::getPublicResourceWebPath('EXT:dlf/Resources/Public');
        } catch (InvalidFileException) {
            $this->logger->warning('Public resource path of the dlf extension could not be determined');
        }

    }

    /**
     * Get the multiview plugin configuration.
     *
     * @return array|null The configuration
     */
    public function getMultiViewPluginConfig(): ?array
    {
        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $fullTypoScript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        return $fullTypoScript['plugin.']['tx_dlf_multiview.'] ?? null;
    }

    /**
     * Add a document the list of multi view documents
     *
     * @param string $url Url to the document
     * @param int $page Page in document to select
     * @param int $sourceKey Key of document url in multiViewSource parameter array. Defaults to -1 if the document URL does not originate from this parameter.
     * @return void
     */
    public function addMultiViewDocument(string $url, int $page = 0, int $sourceKey = -1): void
    {
        $index = count($this->multiViewDocuments);
        $this->multiViewDocuments[$index]['url'] = $url;
        $this->multiViewDocuments[$index]['encodedUrl'] = urlencode($this->multiViewDocuments[$index]['url']);
        if (strpos($url, '#') !== false) {
            $page = (int) explode('#', $url)[1];
        }
        $this->multiViewDocuments[$index]['page'] = $page;
        $this->multiViewDocuments[$index]['sourceKey'] = $sourceKey;
    }

    /**
     * Checks whether the multiview plugin setting `multiDocumentTypes` contains the type parameter value.
     *
     * @param string $type of the document
     * @return bool True if the multiview plugin is configured and its multiDocumentTypes setting contains the given type value.
     */
    public function isMultiDocumentType(string $type): bool
    {
        $multiViewPluginConfig = $this->getMultiViewPluginConfig();
        if (!$multiViewPluginConfig !== null
            && !empty($multiViewPluginConfig['settings.'])
            && !empty($multiViewPluginConfig['settings.']['multiDocumentTypes'])
        ) {
            $multiDocumentTypes = $multiViewPluginConfig['settings.']['multiDocumentTypes'];
        }
        return !empty($multiDocumentTypes) && in_array($type, array_map('trim', explode(',', $multiDocumentTypes)));
    }

    /**
     * Build the multiview documents.
     *
     * @param string $docUrl The URL of the document.
     * @param AbstractDocument $doc The document itself.
     * @return void
     */
    protected function buildMultiViewDocuments(string $docUrl, AbstractDocument $doc): void
    {
        if ($this->getMultiViewPluginConfig() === null) {
            $this->logger->debug("The multiview plugin is not configured.");
            return;
        }

        if ($this->isMultiDocumentType($doc->tableOfContents[0]['type'])) {
            $childDocuments = $doc->tableOfContents[0]['children'];
            foreach ($childDocuments as $document) {
                $this->addMultiViewDocument($document['points']);
            }
        } else {
            $this->addMultiViewDocument($docUrl, $this->requestData['page']);
        }
        if (isset($this->requestData['multiViewSource']) && is_array($this->requestData['multiViewSource'])) {
            foreach ($this->requestData['multiViewSource'] as $sourceKey => $documentUrl) {
                $sourceDocument = AbstractDocument::getInstance($documentUrl, $this->settings);
                if ($sourceDocument !== null) {
                    if ($this->isMultiDocumentType($sourceDocument->tableOfContents[0]['type'])) {
                        $childDocuments = $sourceDocument->tableOfContents[0]['children'];
                        foreach ($childDocuments as $sourceDocument) {
                            $this->addMultiViewDocument($sourceDocument['points'], 0, $sourceKey);
                        }
                    } else {
                        $this->addMultiViewDocument($documentUrl, 0, $sourceKey);
                    }
                }
            }
        }
    }

    /**
     * Loads the current document into $this->document
     *
     * @access protected
     *
     * @param string $documentId The document's UID or URL (id), fallback: record ID (recordId)
     *
     * @return void
     */
    protected function loadDocument(string $documentId = ''): void
    {
        // Sanitize FlexForm settings to avoid later casting.
        $this->sanitizeSettings();

        // Get document ID from request data if not passed as parameter.
        if (!$documentId && !empty($this->requestData['id'])) {
            $documentId = $this->requestData['id'];
        }

        // Try to get document format from database
        if (!empty($documentId)) {


            $doc = null;

            if (MathUtility::canBeInterpretedAsInteger($documentId)) {
                $doc = $this->getDocumentByUid((int) $documentId);
            } elseif (GeneralUtility::isValidUrl($documentId)) {
                $doc = $this->getDocumentByUrl($documentId);
            }

            if ($this->document !== null && $doc !== null) {
                $this->document->setCurrentDocument($doc);
            }

        } elseif (!empty($this->requestData['recordId'])) {

            $this->document = $this->documentRepository->findOneBy(['recordId' => $this->requestData['recordId']]);

            if ($this->document !== null) {
                $doc = AbstractDocument::getInstance($this->document->getLocation(), $this->settings);
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
     * Configure URL for proxy.
     *
     * @access protected
     *
     * @param string $url URL for proxy configuration
     *
     * @return void
     */
    protected function configureProxyUrl(string &$url): void
    {
        $this->uriBuilder->reset()
            ->setTargetPageUid($this->pageUid)
            ->setCreateAbsoluteUri(!empty($this->extConf['general']['forceAbsoluteUrl']))
            ->setArguments(
                [
                    'eID' => 'tx_dlf_pageview_proxy',
                    'url' => $url,
                    'uHash' => GeneralUtility::hmac($url, 'PageViewProxy')
                ]
            )
            ->build();
    }

    /**
     * Checks if doc of its fulltext is missing or is empty (no pages)
     *
     * @access protected
     *
     * @return bool
     */
    protected function isDocOrFulltextMissingOrEmpty(): bool
    {
        return $this->isDocMissingOrEmpty() || empty($this->useGroupsConfiguration->getFulltext());
    }

    /**
     * Checks if doc is missing or is empty (no pages)
     *
     * @access protected
     *
     * @return bool
     */
    protected function isDocMissingOrEmpty(): bool
    {
        return $this->isDocMissing() || $this->document->getCurrentDocument()->numPages < 1;
    }

    /**
     * Checks if doc is missing
     *
     * @access protected
     *
     * @return bool
     */
    protected function isDocMissing(): bool
    {
        return $this->document === null || $this->document->getCurrentDocument() === null;
    }

    /**
     * Returns the LanguageService
     *
     * @access protected
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Safely gets parameters from request if they exist
     *
     * @access protected
     *
     * @param string $parameterName
     * @param array $pluginNames
     *
     * @return null|string|array
     */
    protected function getParametersSafely(string $parameterName, array $pluginNames = [])
    {
        if ($this->request->hasArgument($parameterName)) {
            return $this->request->getArgument($parameterName);
        }

        if (!empty($pluginNames)) {
            $pluginParameter = $this->getPluginParameterFromArgument($parameterName, $pluginNames);
            if ($pluginParameter !== null) {
                return $pluginParameter;
            }
        }

        $parsedBody = $this->request->getParsedBody();
        if ($parsedBody) {
            $bodyParameter = $this->getParameterFromRequestData($parameterName, $parsedBody, $pluginNames);
            if ($bodyParameter !== null) {
                return $bodyParameter;
            }
        }

        $queryParams = $this->request->getQueryParams();
        if ($queryParams) {
            $queryParameter = $this->getParameterFromRequestData($parameterName, $queryParams, $pluginNames);
            if ($queryParameter !== null) {
                return $queryParameter;
            }
        }

        return null;
    }

    /**
     * Safely gets plugin parameters from argument if they exist
     *
     * @param string $parameterName
     * @param array $pluginNames
     *
     * @return null|string|array
     */
    private function getPluginParameterFromArgument(string $parameterName, array $pluginNames)
    {
        foreach ($pluginNames as $pluginName) {
            if ($this->request->hasArgument($pluginName)) {
                $pluginRequest = $this->request->getArgument($pluginName);
                if (array_key_exists($parameterName, $pluginRequest)) {
                    return $pluginRequest[$parameterName];
                }
            }
        }
        return null;
    }

    /**
     * Safely gets parameters from request if they exist
     *
     * @param string $parameterName
     * @param array $pluginNames
     *
     * @return null|string|array
     */
    private function getParameterFromRequestData(string $parameterName, array $requestData, array $pluginNames)
    {
        if (array_key_exists($parameterName, $requestData)) {
            return $requestData[$parameterName];
        }

        foreach ($pluginNames as $pluginName) {
            if (array_key_exists($pluginName, $requestData)) {
                $pluginRequest = $requestData[$pluginName];
                if (array_key_exists($parameterName, $pluginRequest)) {
                    return $pluginRequest[$parameterName];
                }
            }
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
    protected function sanitizeRequestData(): void
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
        $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'] ?? 0, 0, 1);
    }

    /**
     * Sanitize settings from FlexForm.
     *
     * @access protected
     *
     * @return void
     */
    protected function sanitizeSettings(): void
    {
        $this->setDefaultIntSetting('storagePid', 0);

        if ($this instanceof MetadataController) {
            $this->setDefaultIntSetting('rootline', 0);
            $this->setDefaultIntSetting('originalIiifMetadata', 0);
            $this->setDefaultIntSetting('displayIiifDescription', 1);
            $this->setDefaultIntSetting('displayIiifRights', 1);
            $this->setDefaultIntSetting('displayIiifLinks', 1);
        }

        if ($this instanceof NavigationController) {
            $this->setDefaultIntSetting('pageStep', 5);
        }

        if ($this instanceof OaiPmhController) {
            $this->setDefaultIntSetting('limit', 5);
            $this->setDefaultIntSetting('solr_limit', 50000);
        }

        if ($this instanceof PageViewController) {
            $this->setDefaultIntSetting('useInternalProxy', 0);
        }
    }

    /**
     * Sets default value for setting if not yet set.
     *
     * @access protected
     *
     * @param string $setting name of setting
     * @param int $value for being set if empty
     *
     * @return void
     */
    protected function setDefaultIntSetting(string $setting, int $value): void
    {
        if (!array_key_exists($setting, $this->settings) || empty($this->settings[$setting])) {
            $this->settings[$setting] = $value;
            $this->logger->info('Setting "' . $setting . '" not set, using default value "' . $value . '" in ' . get_class($this) . '.');
        } else {
            $this->settings[$setting] = (int) $this->settings[$setting];
        }
    }

    /**
     * Sets page value.
     *
     * @access protected
     *
     * @return void
     */
    protected function setPage(): void
    {
        if (!empty($this->requestData['logicalPage'])) {
            $this->requestData['page'] = $this->document->getCurrentDocument()->getPhysicalPage($this->requestData['logicalPage']);
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
    protected function setDefaultPage(): void
    {
        // Set default values if not set.
        if (!isset($this->requestData['page'])
            || empty($this->requestData['page'])
            || (int) $this->requestData['page'] <= 0) {
            $this->requestData['page'] = 1;
        }

        $this->requestData['page'] = MathUtility::forceIntegerInRange($this->requestData['page'], 1, $this->document->getCurrentDocument()->numPages, 1);

        // reassign viewData to get correct page
        $this->viewData['requestData'] = $this->requestData;
    }

    /**
     * Wrapper for ActionController::processRequest in order to initialize things
     * without using a constructor.
     *
     * @access public
     *
     * @param RequestInterface $request the request
     *
     * @return ResponseInterface the response
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        $this->initialize($request);
        return parent::processRequest($request);
    }

    /**
     * build simple pagination
     *
     * @param PaginationInterface $pagination
     * @param PaginatorInterface $paginator
     * @return array
     */
    //TODO: clean this function
    protected function buildSimplePagination(PaginationInterface $pagination, PaginatorInterface $paginator): array
    {
        $firstPage = $pagination->getFirstPageNumber();
        $lastPage = $pagination->getLastPageNumber();
        $currentPageNumber = $paginator->getCurrentPageNumber();

        $pages = [];
        $pagesSect = [];
        $aRange = [];
        $nRange = 5;    // ToDo: should be made configurable

        // lower limit of the range
        $nBottom = $currentPageNumber - $nRange;
        // upper limit of the range
        $nTop = $currentPageNumber + $nRange;
        // page range
        for ($i = $nBottom; $i <= $nTop; $i++) {
            if ($i > 0 and $i <= $lastPage) {
                $aRange[] = $i;
            };
        };

        // check whether the first screen page is > 1, if yes then points must be added
        if ($aRange[0] > 1) {
            $pagesSect[] = ['label' => '...', 'startRecordNumber' => '...'];
        };
        $lastStartRecordNumberGrid = 0; // due to validity outside the loop
        foreach (range($firstPage, $lastPage) as $i) {
            // detect which pagination is active: ListView or GridView
            if (get_class($pagination) == 'TYPO3\CMS\Core\Pagination\SimplePagination') {  // ListView
                $lastStartRecordNumberGrid = $i; // save last $startRecordNumber for LastPage button

                $pages[$i] = [
                    'label' => $i,
                    'startRecordNumber' => $i
                ];

                // Check if screen page is in range
                // <f:for each="{pagination.pagesR}" as="page">
                if (in_array($i, $aRange)) {
                    $pagesSect[] = ['label' => $i, 'startRecordNumber' => $i];
                };
            } else { // GridView
                // to calculate the values for generation the links for the pagination pages
                /** @var \Kitodo\Dlf\Pagination\PageGridPaginator $paginator */
                $itemsPerPage = $paginator->getPublicItemsPerPage();

                $startRecordNumber = $itemsPerPage * $i;
                $startRecordNumber = $startRecordNumber + 1;
                $startRecordNumber = $startRecordNumber - $itemsPerPage;

                $lastStartRecordNumberGrid = $startRecordNumber; // save last $startRecordNumber for LastPage button

                // array with label as screen/pagination page number
                // and startRecordNumber for correct structure of the link
                //<f:link.action action="{action}"
                //      addQueryString="untrusted"
                //      argumentsToBeExcludedFromQueryString="{0: 'tx_dlf[page]'}"
                //      additionalParams="{'tx_dlf[page]': page.startRecordNumber}"
                //      arguments="{search: lastSearch}">{page.label}</f:link.action>
                $pages[$i] = [
                    'label' => $i,
                    'startRecordNumber' => $startRecordNumber
                ];

                // Check if screen page is in range
                if (in_array($i, $aRange)) {
                    $pagesSect[] = ['label' => $i, 'startRecordNumber' => $startRecordNumber];
                };
            };
        };

        // check whether the last element from $aRange <= last screen page, if yes then points must be added
        if ($aRange[array_key_last($aRange)] < $lastPage) {
            $pagesSect[] = ['label' => '...', 'startRecordNumber' => '...'];
        }

        // Safely get the next and previous page numbers
        $nextPageNumber = isset($pages[$currentPageNumber + 1]) ? $pages[$currentPageNumber + 1]['startRecordNumber'] : null;
        $previousPageNumber = isset($pages[$currentPageNumber - 1]) ? $pages[$currentPageNumber - 1]['startRecordNumber'] : null;

        // 'startRecordNumber' is not required in GridView, only the variant for each loop is required
        // 'endRecordNumber' is not required in both views
        //
        // lastPageNumber       =>  last screen page
        // lastPageNumber       =>  Document page to build the last screen page. This is the first document
        //                          of the last block of 10 (or less) documents on the last screen page
        // firstPageNumber      =>  always 1
        // nextPageNumber       =>  Document page to build the next screen page
        // nextPageNumberG      =>  Number of the screen page for the next screen page
        // previousPageNumber   =>  Document page to build up the previous screen page
        // previousPageNumberG  =>  Number of the screen page for the previous screen page
        // currentPageNumber    =>  Number of the current screen page
        // pagesG               =>  Array with two keys
        //    label             =>  Number of the screen page
        //    startRecordNumber =>  First document of this block of 10 documents on the same screen page
        return [
            'lastPageNumber' => $lastPage,
            'lastPageNumberG' => $lastStartRecordNumberGrid,
            'firstPageNumber' => $firstPage,
            'nextPageNumber' => $nextPageNumber,
            'nextPageNumberG' => $currentPageNumber + 1,
            'previousPageNumber' => $previousPageNumber,
            'previousPageNumberG' => $currentPageNumber - 1,
            'startRecordNumber' => $pagination->getStartRecordNumber(),
            'endRecordNumber' => $pagination->getEndRecordNumber(),
            'currentPageNumber' => $currentPageNumber,
            'pages' => range($firstPage, $lastPage),
            'pagesG' => $pages,
            'pagesR' => $pagesSect
        ];
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
    private function getDocumentByUid(int $documentId)
    {
        $doc = null;
        $this->document = $this->documentRepository->findOneByIdAndSettings($documentId);

        if ($this->document) {
            $doc = AbstractDocument::getInstance($this->document->getLocation(), $this->settings);
            if ($doc !== null) {
                $doc->configPid = $this->document->getPid();
                $this->buildMultiViewDocuments($this->document->getLocation(), $doc);
            }
        }

        if (!$this->document || $doc === null) {
            $this->logger->error('Invalid UID "' . $documentId . '" or PID "' . $this->settings['storagePid'] . '" for document loading');
        }

        return $doc;
    }

    /**
     * Get document by URL.
     *
     * @access protected
     *
     * @param string $documentUrl The document's URL
     *
     * @return AbstractDocument
     */
    protected function getDocumentByUrl(string $documentUrl)
    {
        $doc = AbstractDocument::getInstance($documentUrl, $this->settings);

        if ($doc !== null) {
            $this->buildMultiViewDocuments($documentUrl, $doc);

            $this->document = GeneralUtility::makeInstance(Document::class);

            if ($doc->recordId) {
                // find document from repository by recordId
                $docFromRepository = $this->documentRepository->findOneBy(['recordId' => $doc->recordId]);
                if ($docFromRepository !== null) {
                    $this->document = $docFromRepository;
                }
            }

            $this->document->setLocation($documentUrl);
        } else {
            $this->logger->error('Invalid location given "' . $documentUrl . '" for document loading');
        }

        return $doc;
    }

    /**
     * For testing purposes only.
     */
    public function setSettingsForTest($settings)
    {
        $this->settings = $settings;
    }
}
