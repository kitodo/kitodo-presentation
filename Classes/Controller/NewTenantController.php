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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Domain\Model\Format;
use Kitodo\Dlf\Domain\Model\Metadata;
use Kitodo\Dlf\Domain\Model\MetadataFormat;
use Kitodo\Dlf\Domain\Model\Structure;
use Kitodo\Dlf\Domain\Repository\FormatRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;


/**
 * Class for the NewTenant backend module
 *
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class NewTenantController extends AbstractController
{
    /**
     * @var int
     */
    protected $pid;

    /**
     * @var array
     */
    protected $pageInfo;

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Backend\View\BackendTemplateView::class;

    /**
     * @var FormatRepository
     */
    protected $formatRepository;

    /**
     * @param FormatRepository $formatRepository
     */
    public function injectFormatRepository(FormatRepository $formatRepository)
    {
        $this->formatRepository = $formatRepository;
    }

    /**
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @var StructureRepository
     */
    protected $structureRepository;

    /**
     * @param StructureRepository $structureRepository
     */
    public function injectStructureRepository(StructureRepository $structureRepository)
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @var SolrCoreRepository
     */
    protected $solrCoreRepository;

    /**
     * @param SolrCoreRepository $solrCoreRepository
     */
    public function injectSolrCoreRepository(SolrCoreRepository $solrCoreRepository)
    {
        $this->solrCoreRepository = $solrCoreRepository;
    }

    /**
     * Initialization for all actions
     *
     */
    protected function initializeAction()
    {
        // Load backend localization file.
        $this->getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_be.xlf');
        $this->getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_mod_newtenant.xlf');
        $this->getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_structure.xlf');
        $this->getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_metadata.xlf');

        $this->pid = (int) GeneralUtility::_GP('id');

        $frameworkConfiguration = $this->configurationManager->getConfiguration($this->configurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        $frameworkConfiguration['persistence']['storagePid'] = $this->pid;
        $this->configurationManager->setConfiguration($frameworkConfiguration);
    }


    /**
     * Action adding formats records
     */
    public function addFormatAction()
    {
        // Include formats definition file.
        $formatsDefaults = include(ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/FormatDefaults.php');

        $frameworkConfiguration = $this->configurationManager->getConfiguration($this->configurationManager::CONFIGURATION_TYPE_FRAMEWORK);
        // tx_dlf_formats are stored on PID = 0
        $frameworkConfiguration['persistence']['storagePid'] = 0;
        $this->configurationManager->setConfiguration($frameworkConfiguration);

        $doPersist = false;

        foreach ($formatsDefaults as $type => $values) {
            // if default format record is not found, add it to the repository
            if ($this->formatRepository->findOneByType($type) === null) {
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

        $this->forward('index');
    }

    /**
     * Action adding metadata records
     */
    public function addMetadataAction()
    {
        // Include metadata definition file.
        $metadataDefaults = include(ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/MetadataDefaults.php');

        $doPersist = false;

        foreach ($metadataDefaults as $indexName => $values) {
            // if default format record is not found, add it to the repository
            if ($this->metadataRepository->findOneByIndexName($indexName) === null) {
                $newRecord = GeneralUtility::makeInstance(Metadata::class);
                $newRecord->setLabel($this->getLanguageService()->getLL('metadata.' . $indexName));
                $newRecord->setIndexName($indexName);
                $newRecord->setDefaultValue($values['default_value']);
                $newRecord->setWrap($values['wrap']);
                $newRecord->setIndexTokenized($values['index_tokenized']);
                $newRecord->setIndexStored((int) $values['index_stored']);
                $newRecord->setIndexIndexed((int) $values['index_indexed']);
                $newRecord->setIndexBoost((float) $values['index_boost']);
                $newRecord->setIsSortable((int) $values['is_sortable']);
                $newRecord->setIsFacet((int) $values['is_facet']);
                $newRecord->setIsListed((int) $values['is_listed']);
                $newRecord->setIndexAutocomplete((int) $values['index_autocomplete']);

                if (is_array($values['format'])) {
                    foreach ($values['format'] as $format) {
                        $formatRecord = $this->formatRepository->findOneByRoot($format['format_root']);
                        // If formatRecord is null, we cannot create a MetadataFormat record.
                        if ($formatRecord !== null) {
                            $newMetadataFormat = GeneralUtility::makeInstance(MetadataFormat::class);
                            $newMetadataFormat->setEncoded($formatRecord->getUid());
                            $newMetadataFormat->setXpath($format['xpath']);
                            $newMetadataFormat->setXpathSorting($format['xpath_sorting']);
                            $newRecord->addFormat($newMetadataFormat);
                        }
                    }
                }
                $this->metadataRepository->add($newRecord);

                $doPersist = true;
            }
        }

        // We must persist here, if we changed anything.
        if ($doPersist === true) {
            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            $persistenceManager->persistAll();
        }

        $this->forward('index');
    }

    /**
     * Action adding Solr core records
     */
    public function addSolrCoreAction()
    {
        // Build data array.
        $data['tx_dlf_solrcores'][uniqid('NEW')] = [
            'pid' => intval($this->pid),
            'label' => $this->getLanguageService()->getLL('flexform.solrcore') . ' (PID ' . $this->pid . ')',
            'index_name' => '',
        ];
        $_ids = Helper::processDBasAdmin($data);
        // Check for failed inserts.
        if (count($_ids) == 1) {
            // Fine.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.solrcoreAddedMsg'),
                $this->getLanguageService()->getLL('flash.solrcoreAdded'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.solrcoreNotAddedMsg'),
                $this->getLanguageService()->getLL('flash.solrcoreNotAdded'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }

        $this->forward('index');
    }

    /**
     * Action adding structure records
     */
    public function addStructureAction()
    {
        // Include structure definition file.
        $structureDefaults = include(ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/StructureDefaults.php');

        $doPersist = false;

        foreach ($structureDefaults as $indexName => $values) {
            // if default format record is not found, add it to the repository
            if ($this->structureRepository->findOneByIndexName($indexName) === null) {
                $newRecord = GeneralUtility::makeInstance(Structure::class);
                $newRecord->setLabel($this->getLanguageService()->getLL('structure.' . $indexName));
                $newRecord->setIndexName($indexName);
                $newRecord->setToplevel($values['toplevel']);
                $newRecord->setOaiName($values['oai_name']);
                $this->structureRepository->add($newRecord);

                $doPersist = true;
            }
        }

        // We must persist here, if we changed anything.
        if ($doPersist === true) {
            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            $persistenceManager->persistAll();
        }

        $this->forward('index');
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        if ($this->actionMethodName == 'indexAction') {
            $this->pageInfo = BackendUtility::readPageAccess($this->pid, $GLOBALS['BE_USER']->getPagePermsClause(1));
            $view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
        if ($view instanceof BackendTemplateView) {
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        }
    }

    /**
     * Main function of the module
     *
     * @access public
     *
     */
    public function indexAction()
    {
        $recordInfos = [];

        if ($this->pageInfo['doktype'] != 254) {
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.wrongPageTypeMsg'),
                $this->getLanguageService()->getLL('flash.wrongPageType'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            return;
        }

        $formatsDefaults = include(ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/FormatDefaults.php');
        $recordInfos['formats']['numCurrent'] = $this->formatRepository->countAll();
        $recordInfos['formats']['numDefault'] = count($formatsDefaults);

        $structuresDefaults = include(ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/StructureDefaults.php');
        $recordInfos['structures']['numCurrent'] = $this->structureRepository->countByPid($this->pid);
        $recordInfos['structures']['numDefault'] = count($structuresDefaults);

        $metadataDefaults = include(ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/MetadataDefaults.php');
        $recordInfos['metadata']['numCurrent'] = $this->metadataRepository->countByPid($this->pid);
        $recordInfos['metadata']['numDefault'] = count($metadataDefaults);

        $solrCore = $this->solrCoreRepository->countByPid($this->pid);

        if ($solrCore) {
            // Fine.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.solrcoreOkayMsg'),
                $this->getLanguageService()->getLL('flash.solrcoreOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Solr core missing.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.solrcoreMissingMsg'),
                $this->getLanguageService()->getLL('flash.solrcoreMissing'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
            $this->view->assign('solr', 1);
        }

        $this->view->assign('recordInfos', $recordInfos);
    }
}
