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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Controller\AbstractController;
use Kitodo\Dlf\Domain\Model\Format;
use Kitodo\Dlf\Domain\Model\SolrCore;
use Kitodo\Dlf\Domain\Repository\FormatRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\ViewInterface;

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
     * @var array
     */
    protected array $pageInfo;

    /**
     * @access protected
     * @var array All configured site languages
     */
    protected array $siteLanguages;

    /**
     * @access protected
     * @var LocalizationFactory Language factory to get language key/values by our own.
     */
    protected LocalizationFactory $languageFactory;

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
     * Returns a response object with either the given html string or the current rendered view as content.
     * 
     * @access protected
     * 
     * @param bool $isError whether to render the non-error or error template
     * 
     * @param array $extraData extra view data used to render the template (in addition to $viewData of AbstractController)
     * 
     * @return ResponseInterface the response
     */
    protected function templateResponse(bool $isError, array $extraData): ResponseInterface
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();

        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple($this->viewData);
        $moduleTemplate->assignMultiple($extraData);
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
        $this->pid = (int) ($this->request->getQueryParams()['id'] ?? null);

        $frameworkConfiguration = $this->configurationManager->getConfiguration($this->configurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        $frameworkConfiguration['persistence']['storagePid'] = $this->pid;
        $this->configurationManager->setConfiguration($frameworkConfiguration);

        $this->languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->pid);
        } catch (SiteNotFoundException $e) {
            $site = new NullSite();
        }
        $this->siteLanguages = $site->getLanguages();
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
        // Include formats definition file.
        $formatsDefaults = $this->getRecords('Format');

        $doPersist = false;

        foreach ($formatsDefaults as $type => $values) {
            // if default format record is not found, add it to the repository
            if ($this->formatRepository->findOneBy(['type' => $type]) === null) {
                $newRecord = GeneralUtility::makeInstance(Format::class);
                $newRecord->setType($type);
                $newRecord->setRoot($values['root']);
                $newRecord->setNamespace($values['namespace']);
                $newRecord->setClass($values['class']);
                $this->formatRepository->add($newRecord);

                $doPersist = true;
            }
        }

        // We must persist here, if we changed anything.
        if ($doPersist === true) {
            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            $persistenceManager->persistAll();
        }

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
        // Include metadata definition file.
        $metadataDefaults = $this->getRecords('Metadata');

        // load language file in own array
        $metadataLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_metadata.xlf', $this->siteLanguages[0]->getTypo3Language());

        $insertedFormats = $this->formatRepository->findAll();

        $availableFormats = [];
        foreach ($insertedFormats as $insertedFormat) {
            $availableFormats[$insertedFormat->getRoot()] = $insertedFormat->getUid();
        }

        $defaultWrap = BackendUtility::getTcaFieldConfiguration('tx_dlf_metadata', 'wrap')['default'];

        $data = [];
        foreach ($metadataDefaults as $indexName => $values) {
            $formatIds = [];

            foreach ($values['format'] as $format) {
                $format['encoded'] = $availableFormats[$format['format_root']];
                unset($format['format_root']);
                $formatIds[] = uniqid('NEW');
                $data['tx_dlf_metadataformat'][end($formatIds)] = $format;
                $data['tx_dlf_metadataformat'][end($formatIds)]['pid'] = $this->pid;
            }

            $data['tx_dlf_metadata'][uniqid('NEW')] = [
                'pid' => $this->pid,
                'label' => $this->getLLL('metadata.' . $indexName, $this->siteLanguages[0]->getTypo3Language(), $metadataLabels),
                'index_name' => $indexName,
                'format' => implode(',', $formatIds),
                'default_value' => $values['default_value'],
                'wrap' => !empty($values['wrap']) ? $values['wrap'] : $defaultWrap,
                'index_tokenized' => $values['index_tokenized'],
                'index_stored' => $values['index_stored'],
                'index_indexed' => $values['index_indexed'],
                'index_boost' => $values['index_boost'],
                'is_sortable' => $values['is_sortable'],
                'is_facet' => $values['is_facet'],
                'is_listed' => $values['is_listed'],
                'index_autocomplete' => $values['index_autocomplete'],
            ];
        }

        $metadataIds = Helper::processDatabaseAsAdmin($data, [], true);

        $insertedMetadata = [];
        foreach ($metadataIds as $id => $uid) {
            /** @var \Kitodo\Dlf\Domain\Model\Metadata $metadata */
            $metadata = $this->metadataRepository->findByUid($uid);
            // id array contains also ids of formats
            if ($metadata != null) {
                $insertedMetadata[$uid] = $metadata->getIndexName();
            }
        }

        foreach ($this->siteLanguages as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                // skip default language
                continue;
            }

            $translateData = [];
            foreach ($insertedMetadata as $id => $indexName) {
                $translateData['tx_dlf_metadata'][uniqid('NEW')] = [
                    'pid' => $this->pid,
                    'sys_language_uid' => $siteLanguage->getLanguageId(),
                    'l18n_parent' => $id,
                    'label' => $this->getLLL('metadata.' . $indexName, $siteLanguage->getTypo3Language(), $metadataLabels),
                ];
            }

            Helper::processDatabaseAsAdmin($translateData);
        }

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
        $doPersist = false;

        // load language file in own array
        $beLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_be.xlf', $this->siteLanguages[0]->getTypo3Language());

        if ($this->solrCoreRepository->findOneBy(['pid' => $this->pid]) === null) {
            $newRecord = GeneralUtility::makeInstance(SolrCore::class);
            $newRecord->setLabel($this->getLLL('flexform.solrcore', $this->siteLanguages[0]->getTypo3Language(), $beLabels). ' (PID ' . $this->pid . ')');
            $indexName = Solr::createCore('');
            if (!empty($indexName)) {
                $newRecord->setIndexName($indexName);

                $this->solrCoreRepository->add($newRecord);

                $doPersist = true;
            }
        }

        // We must persist here, if we changed anything.
        if ($doPersist === true) {
            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            $persistenceManager->persistAll();
        }

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
        // Include structure definition file.
        $structureDefaults = $this->getRecords('Structure');

        // load language file in own array
        $structureLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_structure.xlf', $this->siteLanguages[0]->getTypo3Language());

        $data = [];
        foreach ($structureDefaults as $indexName => $values) {
            $data['tx_dlf_structures'][uniqid('NEW')] = [
                'pid' => $this->pid,
                'toplevel' => $values['toplevel'],
                'label' => $this->getLLL('structure.' . $indexName, $this->siteLanguages[0]->getTypo3Language(), $structureLabels),
                'index_name' => $indexName,
                'oai_name' => $values['oai_name'],
                'thumbnail' => 0,
            ];
        }
        $structureIds = Helper::processDatabaseAsAdmin($data, [], true);

        $insertedStructures = [];
        foreach ($structureIds as $id => $uid) {
            /** @var \Kitodo\Dlf\Domain\Model\Structure $structure */
            $structure = $this->structureRepository->findByUid($uid);
            $insertedStructures[$uid] = $structure->getIndexName();
        }

        foreach ($this->siteLanguages as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                // skip default language
                continue;
            }

            $translateData = [];
            foreach ($insertedStructures as $id => $indexName) {
                $translateData['tx_dlf_structures'][uniqid('NEW')] = [
                    'pid' => $this->pid,
                    'sys_language_uid' => $siteLanguage->getLanguageId(),
                    'l18n_parent' => $id,
                    'label' => $this->getLLL('structure.' . $indexName, $siteLanguage->getTypo3Language(), $structureLabels),
                ];
            }

            Helper::processDatabaseAsAdmin($translateData);
        }

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

        $this->pageInfo = BackendUtility::readPageAccess($this->pid, $GLOBALS['BE_USER']->getPagePermsClause(1));

        if (!isset($this->pageInfo['doktype']) || $this->pageInfo['doktype'] != 254) {
            return $this->redirect('error');
        }

        $formatsDefaults = $this->getRecords('Format');
        $recordInfos['formats']['numCurrent'] = $this->formatRepository->countAll();
        $recordInfos['formats']['numDefault'] = count($formatsDefaults);

        $structuresDefaults = $this->getRecords('Structure');
        $recordInfos['structures']['numCurrent'] = $this->structureRepository->count(['pid' => $this->pid]);
        $recordInfos['structures']['numDefault'] = count($structuresDefaults);

        $metadataDefaults = $this->getRecords('Metadata');
        $recordInfos['metadata']['numCurrent'] = $this->metadataRepository->count(['pid' => $this->pid]);
        $recordInfos['metadata']['numDefault'] = count($metadataDefaults);

        $recordInfos['solrcore']['numCurrent'] = $this->solrCoreRepository->count(['pid' => $this->pid]);

        $viewData = ['recordInfos' => $recordInfos];

        return $this->templateResponse(false, $viewData);
    }

    /**
     * Error function - there is nothing to do at the moment.
     *
     * @access public
     *
     * @return void
     */
    // @phpstan-ignore-next-line
    public function errorAction(): ResponseInterface
    {
        return $this->templateResponse(true, []);
    }

    /**
     * Get language label for given key and language.
     * 
     * @access private
     *
     * @param string $index
     * @param string $lang
     * @param array $langArray
     *
     * @return string
     */
    private function getLLL(string $index, string $lang, array $langArray): string
    {
        if (isset($langArray[$lang][$index][0]['target'])) {
            return $langArray[$lang][$index][0]['target'];
        } elseif (isset($langArray['default'][$index][0]['target'])) {
            return $langArray['default'][$index][0]['target'];
        } else {
            return 'Missing translation for ' . $index;
        }
    }

    /**
     * Get records from file for given record type.
     *
     * @access private
     *
     * @param string $recordType
     *
     * @return array
     */
    private function getRecords(string $recordType): array
    {
        $filePath = GeneralUtility::getFileAbsFileName('EXT:dlf/Resources/Private/Data/' . $recordType . 'Defaults.json');
        if (file_exists($filePath)) {
            $fileContents = file_get_contents($filePath);
            $records = json_decode($fileContents, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $records;
            }
        }
        return [];
    }
}
