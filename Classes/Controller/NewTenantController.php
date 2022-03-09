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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;

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
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Backend\View\BackendTemplateView::class;

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
    }

    /**
     * Action adding metadata records
     */
    public function addMetadataAction()
    {
        // Include metadata definition file.
        $metadataDefaults = include (ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/MetadataDefaults.php');
        $i = 0;
        // Build data array.
        $this->pid = (int) GeneralUtility::_GP('id');

        foreach ($metadataDefaults as $index_name => $values) {
            $formatIds = [];
            foreach ($values['format'] as $format) {
                $formatIds[] = uniqid('NEW');
                $data['tx_dlf_metadataformat'][end($formatIds)] = $format;
                $data['tx_dlf_metadataformat'][end($formatIds)]['pid'] = intval($this->pid);
                $i++;
            }
            $data['tx_dlf_metadata'][uniqid('NEW')] = [
                'pid' => intval($this->pid),
                'label' => $this->getLanguageService()->getLL('metadata.' . $index_name),
                'index_name' => $index_name,
                'format' => implode(',', $formatIds),
                'default_value' => $values['default_value'],
                'wrap' => (!empty($values['wrap']) ? $values['wrap'] : $GLOBALS['TCA']['tx_dlf_metadata']['columns']['wrap']['config']['default']),
                'index_tokenized' => $values['index_tokenized'],
                'index_stored' => $values['index_stored'],
                'index_indexed' => $values['index_indexed'],
                'index_boost' => $values['index_boost'],
                'is_sortable' => $values['is_sortable'],
                'is_facet' => $values['is_facet'],
                'is_listed' => $values['is_listed'],
                'index_autocomplete' => $values['index_autocomplete'],
            ];
            $i++;
        }
        $_ids = Helper::processDBasAdmin($data, [], true);
        // Check for failed inserts.
        if (count($_ids) == $i) {
            // Fine.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.metadataAddedMsg'),
                $this->getLanguageService()->getLL('flash.metadataAdded'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.metadataNotAddedMsg'),
                $this->getLanguageService()->getLL('flash.metadataNotAdded'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }

        $this->forward('index');
    }

    /**
     * Action adding Solr core records
     */
    public function addSolrCoreAction()
    {
        $this->pid = (int) GeneralUtility::_GP('id');
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
        $this->pid = (int) GeneralUtility::_GP('id');
        // Include structure definition file.
        $structureDefaults = include (ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/StructureDefaults.php');
        // Build data array.
        foreach ($structureDefaults as $index_name => $values) {
            $data['tx_dlf_structures'][uniqid('NEW')] = [
                'pid' => intval($this->pid),
                'toplevel' => $values['toplevel'],
                'label' => $this->getLanguageService()->getLL('structure.' . $index_name),
                'index_name' => $index_name,
                'oai_name' => $values['oai_name'],
                'thumbnail' => 0,
            ];
        }
        $_ids = Helper::processDBasAdmin($data, [], true);
        // Check for failed inserts.
        if (count($_ids) == count($structureDefaults)) {
            // Fine.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.structureAddedMsg'),
                $this->getLanguageService()->getLL('flash.structureAdded'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.structureNotAddedMsg'),
                $this->getLanguageService()->getLL('flash.structureNotAdded'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
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
        if ($this->actionMethodName == 'indexAction'
            || $this->actionMethodName == 'onlineAction'
            || $this->actionMethodName == 'compareAction') {
            $this->pid = (int) GeneralUtility::_GP('id');
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
        $this->pid = (int) GeneralUtility::_GP('id');

        if ($this->pageInfo['doktype'] != 254) {
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.wrongPageTypeMsg'),
                $this->getLanguageService()->getLL('flash.wrongPageType'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            return;
        }

        $structures = $this->structureRepository->countByPid($this->pid);

        if ($structures) {
            // Fine.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.structureOkayMsg'),
                $this->getLanguageService()->getLL('flash.structureOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Configuration missing.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.structureNotOkayMsg'),
                $this->getLanguageService()->getLL('flash.structureNotOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $this->view->assign('structure', 1);
        }

        $metadata = $this->metadataRepository->countByPid($this->pid);

        if ($metadata) {
            // Fine.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.metadataOkayMsg'),
                $this->getLanguageService()->getLL('flash.metadataOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Configuration missing.
            $this->addFlashMessage(
                $this->getLanguageService()->getLL('flash.metadataNotOkayMsg'),
                $this->getLanguageService()->getLL('flash.metadataNotOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $this->view->assign('metadata', 1);
        }

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
    }
}
