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

use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Controller\AbstractController;
use Kitodo\Dlf\Domain\Model\Format;
use Kitodo\Dlf\Domain\Model\Metadata;
use Kitodo\Dlf\Domain\Model\MetadataFormat;
use Kitodo\Dlf\Domain\Model\SolrCore;
use Kitodo\Dlf\Domain\Model\Structure;
use Kitodo\Dlf\Domain\Repository\FormatRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Controller class for the backend module 'New Tenant'.
 *
 * @author Christopher Timm <timm@effective-webwork.de>
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
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
     * All configured site languages
     *
     * @var array
     */
    protected $siteLanguages;

    /**
     * LanguageFactory to get language key/values by our own.
     *
     * @var \TYPO3\CMS\Core\Localization\LocalizationFactory
     */
    protected $languageFactory;

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
        $this->pid = (int) GeneralUtility::_GP('id');

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

        // load language file in own array
        $metadataLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_metadata.xlf', $this->siteLanguages[0]->getTypo3Language());

        foreach ($metadataDefaults as $indexName => $values) {
            // if default format record is not found, add it to the repository
            if ($this->metadataRepository->findOneByIndexName($indexName) === null) {

                $newRecord = GeneralUtility::makeInstance(Metadata::class);
                $newRecord->setLabel($this->getLLL('metadata.' . $indexName, $this->siteLanguages[0]->getTypo3Language(), $metadataLabels));
                $newRecord->setIndexName($indexName);
                $newRecord->setDefaultValue($values['default_value']);
                $newRecord->setWrap($values['wrap'] ? : $GLOBALS['TCA']['tx_dlf_metadata']['columns']['wrap']['config']['default']);
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

                foreach ($this->siteLanguages as $siteLanguage) {
                    if ($siteLanguage->getLanguageId() === 0) {
                        // skip default language
                        continue;
                    }
                    $translatedRecord = GeneralUtility::makeInstance(Metadata::class);
                    $translatedRecord->setL18nParent($newRecord);
                    $translatedRecord->_setProperty('_languageUid', $siteLanguage->getLanguageId());
                    $translatedRecord->setLabel($this->getLLL('metadata.' . $indexName, $siteLanguage->getTypo3Language(), $metadataLabels));
                    $translatedRecord->setIndexName($indexName);
                    $translatedRecord->setWrap($newRecord->getWrap());

                    $this->metadataRepository->add($translatedRecord);
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
        $doPersist = false;

        // load language file in own array
        $beLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_be.xlf', $this->siteLanguages[0]->getTypo3Language());

        if ($this->solrCoreRepository->findOneByPid($this->pid) === null) {
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

        // load language file in own array
        $structLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_structure.xlf', $this->siteLanguages[0]->getTypo3Language());

        foreach ($structureDefaults as $indexName => $values) {
            // if default format record is not found, add it to the repository
            if ($this->structureRepository->findOneByIndexName($indexName) === null) {
                $newRecord = GeneralUtility::makeInstance(Structure::class);
                $newRecord->setLabel($this->getLLL('structure.' . $indexName, $this->siteLanguages[0]->getTypo3Language(), $structLabels));
                $newRecord->setIndexName($indexName);
                $newRecord->setToplevel($values['toplevel']);
                $newRecord->setOaiName($values['oai_name']);
                $this->structureRepository->add($newRecord);

                foreach ($this->siteLanguages as $siteLanguage) {
                    if ($siteLanguage->getLanguageId() === 0) {
                        // skip default language
                        continue;
                    }
                    $translatedRecord = GeneralUtility::makeInstance(Structure::class);
                    $translatedRecord->setL18nParent($newRecord);
                    $translatedRecord->_setProperty('_languageUid', $siteLanguage->getLanguageId());
                    $translatedRecord->setLabel($this->getLLL('structure.' . $indexName, $siteLanguage->getTypo3Language(), $structLabels));
                    $translatedRecord->setIndexName($indexName);

                    $this->structureRepository->add($translatedRecord);
                }

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
            $this->forward('error');
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

        $recordInfos['solrcore']['numCurrent'] = $this->solrCoreRepository->countByPid($this->pid);

        $this->view->assign('recordInfos', $recordInfos);
    }

    /**
     * Error function - there is nothing to do at the moment.
     *
     * @access public
     *
     */
    public function errorAction()
    {
    }

    /**
     * Get language label for given key and language.
     *
     * @param string $index
     * @param string $lang
     * @param array $langArray
     *
     * @access public
     */
    protected function getLLL($index, $lang, $langArray)
    {
        if (isset($langArray[$lang][$index][0]['target'])) {
            return $langArray[$lang][$index][0]['target'];
        } elseif (isset($langArray['default'][$index][0]['target'])) {
            return $langArray['default'][$index][0]['target'];
        } else {
            return 'Missing translation for ' . $index;
        }
    }
}
