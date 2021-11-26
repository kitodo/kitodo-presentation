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
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;

class NewTenantController extends AbstractController
{
    protected $pid;

    protected $pageInfo;

    protected $extKey = 'dlf';

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Backend\View\BackendTemplateView::class;

    protected $structureRepository;

    /**
     * @param StructureRepository $structureRepository
     */
    public function injectStructureRepository(StructureRepository $structureRepository)
    {
        $this->structureRepository = $structureRepository;
    }

    protected $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    protected $solrCoreRepository;

    /**
     * @param SolrCoreRepository $solrCoreRepository
     */
    public function injectSolrCoreRepository(SolrCoreRepository $solrCoreRepository)
    {
        $this->solrCoreRepository = $solrCoreRepository;
    }


    public function addMetadataAction() {
        // Include metadata definition file.
        $metadataDefaults = include (ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Private/Data/MetadataDefaults.php');
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
                'label' => $GLOBALS['LANG']->sL('LLL:EXT:dlf/Resources/Private/Language/NewTenant.xml:metadata.' . $index_name),
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
                LocalizationUtility::translate('flash.metadataAddedMsg', 'dlf'),
                LocalizationUtility::translate('flash.metadataAdded', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.metadataNotAddedMsg', 'dlf'),
                LocalizationUtility::translate('flash.metadataNotAdded', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }

        $this->forward('index');
    }

    public function addSolrCoreAction() {
        $this->pid = (int) GeneralUtility::_GP('id');
        // Build data array.
        $data['tx_dlf_solrcores'][uniqid('NEW')] = [
            'pid' => intval($this->pid),
            'label' => $GLOBALS['LANG']->sL('LLL:EXT:dlf/Resources/Private/Language/NewTenant.xml:solrcore') . ' (PID ' . $this->pid . ')',
            'index_name' => '',
        ];
        $_ids = Helper::processDBasAdmin($data);
        // Check for failed inserts.
        if (count($_ids) == 1) {
            // Fine.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.solrcoreAddedMsg', 'dlf'),
                LocalizationUtility::translate('flash.solrcoreAdded', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.solrcoreNotAddedMsg', 'dlf'),
                LocalizationUtility::translate('flash.solrcoreNotAdded', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }

        $this->forward('index');
    }

    public function addStructureAction() {
        $this->pid = (int) GeneralUtility::_GP('id');
        // Include structure definition file.
        $structureDefaults = include (ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Private/Data/StructureDefaults.php');
        // Build data array.
        foreach ($structureDefaults as $index_name => $values) {
            $data['tx_dlf_structures'][uniqid('NEW')] = [
                'pid' => intval($this->pid),
                'toplevel' => $values['toplevel'],
                'label' => $GLOBALS['LANG']->sL('LLL:EXT:dlf/Resources/Private/Language/NewTenant.xml:structure.' . $index_name),
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
                LocalizationUtility::translate('flash.structureAddedMsg', 'dlf'),
                LocalizationUtility::translate('flash.structureAdded', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.structureNotAddedMsg', 'dlf'),
                LocalizationUtility::translate('flash.structureNotAdded', 'dlf'),
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
                LocalizationUtility::translate('flash.wrongPageTypeMsg', 'dlf'),
                LocalizationUtility::translate('flash.wrongPageType', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            return;
        }

        $structures = $this->structureRepository->findByPid($this->pid);

        if (count($structures) > 0) {
            // Fine.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.structureOkayMsg', 'dlf'),
                LocalizationUtility::translate('flash.structureOkay', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Configuration missing.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.structureNotOkayMsg', 'dlf'),
                LocalizationUtility::translate('flash.structureNotOkay', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $this->view->assign('structure', 1);
        }

        $metadata = $this->metadataRepository->findByPid($this->pid);

        if (count($metadata) > 0) {
            // Fine.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.metadataOkayMsg', 'dlf'),
                LocalizationUtility::translate('flash.metadataOkay', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Configuration missing.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.metadataNotOkayMsg', 'dlf'),
                LocalizationUtility::translate('flash.metadataNotOkay', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            $this->view->assign('metadata', 1);
        }

        $solrCore = $this->solrCoreRepository->findByPid($this->pid);
        $solrCore2 = $this->solrCoreRepository->findByPid(0);

        if (count($solrCore) > 0 OR count($solrCore2) > 0) {
            if (count($solrCore) > 0) {
                // Fine.
                $this->addFlashMessage(
                    LocalizationUtility::translate('flash.solrcoreOkayMsg', 'dlf'),
                    LocalizationUtility::translate('flash.solrcoreOkay', 'dlf'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                );
            } else {
                // Default core available, but this is deprecated.
                $this->addFlashMessage(
                    LocalizationUtility::translate('flash.solrcoreDeprecatedMsg', 'dlf'),
                    LocalizationUtility::translate('flash.solrcoreDeprecatedOkay', 'dlf'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE
                );
                $this->view->assign('solr', 1);
            }
        } else {
            // Solr core missing.
            $this->addFlashMessage(
                LocalizationUtility::translate('flash.solrcoreMissingMsg', 'dlf'),
                LocalizationUtility::translate('flash.solrcoreMissing', 'dlf'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
            $this->view->assign('solr', 1);
        }
    }
}