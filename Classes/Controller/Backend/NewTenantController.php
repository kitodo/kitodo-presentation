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

use Kitodo\Dlf\Controller\AbstractController;
use Kitodo\Dlf\Service\BootstrapRootSetupService;
use Kitodo\Dlf\Service\TenantDefaultsSetupService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @var int
     */
    protected int $pid;

    /**
     * @access protected
     * @var mixed[]
     */
    protected array $pageInfo;

    /**
     * @access protected
     */
    protected TenantDefaultsSetupService $tenantDefaultsSetupService;

    /**
     * @access public
     */
    public function injectTenantDefaultsSetupService(TenantDefaultsSetupService $tenantDefaultsSetupService): void
    {
        $this->tenantDefaultsSetupService = $tenantDefaultsSetupService;
    }

    /**
     * @access protected
     */
    protected BootstrapRootSetupService $bootstrapRootSetupService;

    /**
     * @access public
     */
    public function injectBootstrapRootSetupService(BootstrapRootSetupService $bootstrapRootSetupService): void
    {
        $this->bootstrapRootSetupService = $bootstrapRootSetupService;
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
    protected function templateResponse(string $template, array $extraData): ResponseInterface
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple($this->viewData);
        $moduleTemplate->assignMultiple($extraData);
        $moduleTemplate->setFlashMessageQueue($messageQueue);
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
        $this->pid = (int) ($this->request->getQueryParams()['id'] ?? null);

        $frameworkConfiguration = $this->configurationManager->getConfiguration($this->configurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        $frameworkConfiguration['persistence']['storagePid'] = $this->pid;
        $this->configurationManager->setConfiguration($frameworkConfiguration);

    }


    /**
     * Action creating a default page tree from the virtual root page.
     *
     * @access public
     */
    public function createPagesAction(): ResponseInterface
    {
        if ($this->pid !== 0) {
            return $this->redirect('error');
        }

        $this->bootstrapRootSetupService->runSetup();

        return $this->redirect('index', null, null, ['refreshPageTree' => 1]);
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
        $this->tenantDefaultsSetupService->run($this->pid, [
            'formats' => true,
            'structures' => false,
            'metadata' => false,
            'solrCore' => false,
        ]);

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
        $this->tenantDefaultsSetupService->run($this->pid, [
            'formats' => false,
            'structures' => false,
            'metadata' => true,
            'solrCore' => false,
        ]);

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
        $this->tenantDefaultsSetupService->run($this->pid, [
            'formats' => false,
            'structures' => false,
            'metadata' => false,
            'solrCore' => true,
        ]);

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
        $this->tenantDefaultsSetupService->run($this->pid, [
            'formats' => false,
            'structures' => true,
            'metadata' => false,
            'solrCore' => false,
        ]);

        return $this->redirect('index');
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

        if ($this->pid === 0) {
            return $this->templateResponse('Backend/NewTenant/Root', [
                'refreshPageTree' => (bool)($this->request->getQueryParams()['refreshPageTree'] ?? false),
            ]);
        }

        $this->pageInfo = BackendUtility::readPageAccess($this->pid, $GLOBALS['BE_USER']->getPagePermsClause(1)) ?: [];

        if (!isset($this->pageInfo['doktype']) || $this->pageInfo['doktype'] != 254) {
            return $this->redirect('error');
        }

        $recordInfos['formats']['numCurrent'] = $this->countRecords('tx_dlf_formats');
        $recordInfos['formats']['numDefault'] = count($this->getRecords('Format'));

        $recordInfos['structures']['numCurrent'] = $this->countRecords('tx_dlf_structures');
        $recordInfos['structures']['numDefault'] = count($this->getRecords('Structure'));

        $recordInfos['metadata']['numCurrent'] = $this->countRecords('tx_dlf_metadata');
        $recordInfos['metadata']['numDefault'] = count($this->getRecords('Metadata'));

        $recordInfos['solrcore']['numCurrent'] = $this->countRecords('tx_dlf_solrcores');

        $viewData = ['recordInfos' => $recordInfos];

        return $this->templateResponse('Backend/NewTenant/Index', $viewData);
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
        return $this->templateResponse('Backend/NewTenant/Error', []);
    }

    /**
     * Count non-deleted records on the current configuration folder.
     */
    private function countRecords(string $table): int
    {
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        return (int)$queryBuilder
            ->count('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Get records from file for given record type.
     *
     * @access private
     *
     * @param string $recordType
     *
     * @return mixed[]
     */
    private function getRecords(string $recordType): array
    {
        $filePath = GeneralUtility::getFileAbsFileName('EXT:dlf/Resources/Private/Data/' . $recordType . 'Defaults.json');
        if (!file_exists($filePath)) {
            return [];
        }

        $fileContents = file_get_contents($filePath);
        if (!is_string($fileContents)) {
            return [];
        }

        $records = json_decode($fileContents, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($records) ? $records : [];
    }

}
