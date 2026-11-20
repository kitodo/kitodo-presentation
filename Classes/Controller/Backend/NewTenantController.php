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

namespace Kitodo\Dlf\Controller\Backend;

use Kitodo\Dlf\Bootstrap\BootstrapService;
use Kitodo\Dlf\Controller\AbstractController;
use Kitodo\Dlf\Domain\Repository\FormatRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Service\TenantService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller class for the backend module 'New Tenant'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class NewTenantController extends AbstractController
{
    /**
     * @access protected
     * @var non-negative-int
     */
    protected int $pid;

    /**
     * @access protected
     * @var mixed[]
     */
    protected array $pageInfo;

    /**
     * @access protected
     * @var FormatRepository
     */
    protected FormatRepository $formatRepository;

    /**
     * @access public
     *
     * @param FormatRepository $formatRepository
     *
     * @return void
     */
    public function injectFormatRepository(FormatRepository $formatRepository): void
    {
        $this->formatRepository = $formatRepository;
    }

    /**
     * @access protected
     * @var MetadataRepository
     */
    protected MetadataRepository $metadataRepository;

    /**
     * @access public
     *
     * @param MetadataRepository $metadataRepository
     *
     * @return void
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository): void
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @access protected
     * @var StructureRepository
     */
    protected StructureRepository $structureRepository;

    /**
     * @access public
     *
     * @param StructureRepository $structureRepository
     *
     * @return void
     */
    public function injectStructureRepository(StructureRepository $structureRepository): void
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @access protected
     * @var SolrCoreRepository
     */
    protected SolrCoreRepository $solrCoreRepository;

    /**
     * @access public
     *
     * @param SolrCoreRepository $solrCoreRepository
     *
     * @return void
     */
    public function injectSolrCoreRepository(SolrCoreRepository $solrCoreRepository): void
    {
        $this->solrCoreRepository = $solrCoreRepository;
    }

    /**
     * @access protected
     * @var TenantService
     */
    protected TenantService $tenantService;

    /**
     * @access public
     *
     * @param TenantService $tenantService
     *
     * @return void
     */
    public function injectTenantService(TenantService $tenantService): void
    {
        $this->tenantService = $tenantService;
    }

    /**
     * @access protected
     * @var BootstrapService
     */
    protected BootstrapService $bootstrapService;

    /**
     * @access public
     *
     * @param BootstrapService $bootstrapService
     *
     * @return void
     */
    public function injectBootstrapService(BootstrapService $bootstrapService): void
    {
        $this->bootstrapService = $bootstrapService;
    }

    /**
     * Returns a response object with either the given html string or the current rendered view as content.
     *
     * @access protected
     *
     * @param bool $isError whether to render the non-error or error template
     *
     * @param mixed[] $extraData extra view data used to render the template (in addition to $viewData of AbstractController)
     *
     * @return ResponseInterface the response
     */
    protected function templateResponse(bool $isError, array $extraData): ResponseInterface
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($this->request);
        $this->view->assignMultiple(
            [
                'requestData' => $this->requestData,
                'uniqueId' => $this->uniqueId
            ]
        );
        $moduleTemplate->assignMultiple($extraData);
        $moduleTemplate->assign('scenarios', $this->bootstrapService->getScenarios());
        $moduleTemplate->setFlashMessageQueue($messageQueue);
        $template = $isError ? 'Backend/NewTenant/Error' : 'Backend/NewTenant/Index';
        return $moduleTemplate->renderResponse($template);
    }

    /**
     * Initialization for all actions
     *
     * @access protected
     *
     * @return void
     */
    protected function initializeAction(): void
    {
        $this->pid = max(0, (int) ($this->request->getQueryParams()['id'] ?? 0));

        $this->formatRepository->useStoragePid($this->pid);
        $this->metadataRepository->useStoragePid($this->pid);
        $this->solrCoreRepository->useStoragePid($this->pid);
        $this->structureRepository->useStoragePid($this->pid);
    }

    /**
     * Action adding formats records
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function addFormatAction(): ResponseInterface
    {
        $this->tenantService->addFormats($this->pid);

        return $this->redirect('index');
    }

    /**
     * Action adding metadata records
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function addMetadataAction(): ResponseInterface
    {
        $this->tenantService->addMetadata($this->pid);

        return $this->redirect('index');
    }

    /**
     * Action adding Solr core records
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function addSolrCoreAction(): ResponseInterface
    {
        $this->tenantService->addSolrCore($this->pid);

        return $this->redirect('index');
    }

    /**
     * Action adding structure records
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function addStructureAction(): ResponseInterface
    {
        $this->tenantService->addStructures($this->pid);

        return $this->redirect('index');
    }

    /**
     * Action creating (or replacing) the demo site of the given scenario
     *
     * @access public
     *
     * @param string $scenario Name of the scenario, see BootstrapService::getScenarios()
     *
     * @return ResponseInterface the response
     */
    public function bootstrapAction(string $scenario): ResponseInterface
    {
        $langPrefix = 'LLL:EXT:dlf/Resources/Private/Language/locallang_be.xlf:';
        $storagePid = 0;
        try {
            // Use scheme and host of the backend, since sites with a matching host take precedence over sites without.
            $pageUids = $this->bootstrapService->bootstrap($scenario, $this->request->getAttribute('normalizedParams')->getSiteUrl());
            $storagePid = $pageUids['storage'];
            $message = LocalizationUtility::translate($langPrefix . 'bootstrap.success', null, [$scenario, $pageUids['root']]);
            $severity = ContextualFeedbackSeverity::OK;
            BackendUtility::setUpdateSignal('updatePageTree');
        } catch (\Exception $e) {
            $message = LocalizationUtility::translate($langPrefix . 'bootstrap.failure', null, [$scenario, $e->getMessage()]);
            $severity = ContextualFeedbackSeverity::ERROR;
        }
        GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier()
            ->enqueue(new FlashMessage((string) $message, '', $severity, true));

        // Continue with the new storage folder to show its records. The current page may have been deleted with the previous demo site.
        return $this->redirectToUri($this->uriBuilder->reset()->setArguments(['id' => $storagePid])->uriFor('index'));
    }

    /**
     * Main function of the module
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function indexAction(): ResponseInterface
    {
        $recordInfos = [];

        $this->pageInfo = BackendUtility::readPageAccess($this->pid, $GLOBALS['BE_USER']->getPagePermsClause(1)) ?: [];

        if (!isset($this->pageInfo['doktype']) || $this->pageInfo['doktype'] != 254) {
            return $this->redirect('error');
        }

        $formatRecords = $this->tenantService->getRecords('format');
        $recordInfos['formats']['numCurrent'] = $this->formatRepository->countAll();
        $recordInfos['formats']['numDefault'] = count($formatRecords);

        $structureRecords = $this->tenantService->getRecords('structure');
        $recordInfos['structures']['numCurrent'] = $this->structureRepository->countAll();
        $recordInfos['structures']['numDefault'] = count($structureRecords);

        $metadataRecords = $this->tenantService->getRecords('metadata');
        $recordInfos['metadata']['numCurrent'] = $this->metadataRepository->countAll();
        $recordInfos['metadata']['numDefault'] = count($metadataRecords);

        $recordInfos['solrcore']['numCurrent'] = $this->solrCoreRepository->countAll();

        $viewData = ['recordInfos' => $recordInfos, 'pageUid' => $this->pid];

        return $this->templateResponse(false, $viewData);
    }

    /**
     * Error function - there is nothing to do at the moment.
     *
     * @access public
     *
     * @return ResponseInterface
     */
    public function errorAction(): ResponseInterface
    {
        return $this->templateResponse(true, []);
    }
}
