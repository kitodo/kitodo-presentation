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
namespace Kitodo\Dlf\Task;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Base class for additional fields classes of scheduler tasks.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class BaseAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being edited. Null when adding a task!
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        return [];
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $fieldsValid = true;

        Helper::getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_tasks.xlf');

        $messageTitle = Helper::getLanguageService()->getLL('additionalFields.error');
        $messageSeverity = FlashMessage::ERROR;

        if (isset($submittedData['doc']) && empty($submittedData['doc'])) {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('additionalFields.doc') . ' ' . Helper::getLanguageService()->getLL('additionalFields.valid'),
                $messageTitle,
                $messageSeverity,
                true,
                'core.template.flashMessages'
            );
            $fieldsValid = false;
        }

        if ((isset($submittedData['pid']) && (int) $submittedData['pid'] <= 0) || !isset($submittedData['pid'])) {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('additionalFields.pid') . ' ' . Helper::getLanguageService()->getLL('additionalFields.valid'),
                $messageTitle,
                $messageSeverity,
                true,
                'core.template.flashMessages'
            );
            $fieldsValid = false;
        }

        if (!$submittedData['uid']) {
            $messageTitle = Helper::getLanguageService()->getLL('additionalFields.warning');
            $messageSeverity = FlashMessage::WARNING;
        }

        if ((isset($submittedData['lib']) && (int) $submittedData['lib'] <= 0)) {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('additionalFields.lib') . ' ' . Helper::getLanguageService()->getLL('additionalFields.valid'),
                $messageTitle,
                $messageSeverity,
                true,
                'core.template.flashMessages'
            );
            $fieldsValid = false;
        }

        if ((isset($submittedData['solr']) && (int) $submittedData['solr'] <= 0) || !isset($submittedData['solr'])) {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('additionalFields.solr') . ' ' . Helper::getLanguageService()->getLL('additionalFields.valid'),
                $messageTitle,
                $messageSeverity,
                true,
                'core.template.flashMessages'
            );
            $fieldsValid = false;
        }

        if (((isset($submittedData['coll']) && isset($submittedData['all'])) || (!isset($submittedData['coll']) && !isset($submittedData['all'])))
            && !isset($submittedData['doc']) && !isset($submittedData['lib'])) {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('additionalFields.collOrAll'),
                $messageTitle,
                $messageSeverity,
                true,
                'core.template.flashMessages'
            );
            $fieldsValid = false;
        }
        return $fieldsValid;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param BaseTask $task Reference to the scheduler backend module
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        /** @var BaseTask $task */
        $task->setDryRun(!empty($submittedData['dryRun']));
        if (isset($submittedData['doc'])) {
            $task->setDoc(htmlspecialchars($submittedData['doc']));
        }
        if (isset($submittedData['lib'])) {
            $task->setLib((int) $submittedData['lib']);
        }
        if (isset($submittedData['coll']) && is_array($submittedData['coll'])) {
            $task->setColl($submittedData['coll']);
        } else {
            $task->setColl([]);
        }
        if (isset($submittedData['pid'])) {
            $task->setPid((int) $submittedData['pid']);
        }
        if (isset($submittedData['solr'])) {
            $task->setSolr((int) $submittedData['solr']);
        }
        if (isset($submittedData['owner'])) {
            $task->setOwner(htmlspecialchars($submittedData['owner']));
        }
        $task->setAll(!empty($submittedData['all']));
        if (isset($submittedData['from'])) {
            $task->setFrom(htmlspecialchars($submittedData['from']));
        }
        if (isset($submittedData['until'])) {
            $task->setUntil(htmlspecialchars($submittedData['until']));
        }
        if (isset($submittedData['set'])) {
            $task->setSet(htmlspecialchars($submittedData['set']));
        }
    }

    /**
     * Return HTML for dry run checkbox
     *
     * @access protected
     *
     * @param bool $dryRun
     *
     * @return array additional field dry run checkbox
     */
    protected function getDryRunField(bool $dryRun): array
    {
        $fieldName = 'dryRun';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="checkbox" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="1"' . ($dryRun ? ' checked="checked"' : '') . '>';
        return [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.dryRun',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
    }

    /**
     * Return HTML for solr dropdown
     *
     * @access protected
     *
     * @param int $solr UID of the selected Solr core
     * @param int $pid UID of the selected storage page
     *
     * @return array additional field solr dropdown
     */
    protected function getSolrField(int $solr, int $pid): array
    {
        $fieldName = 'solr';
        $fieldId = 'task_' . $fieldName;

        $allSolrCores = $this->getSolrCores($pid);
        $options = [];
        $options[] = '<option value="-1"></option>';
        foreach ($allSolrCores as $label => $uid) {
            $options[] = '<option value="' . $uid . '" ' . ($solr == $uid ? 'selected' : '') . ' >' . $label . '</option>';
        };
        $fieldHtml = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';
        return [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.solr',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
    }

    /**
     * Return html for page dropdown
     *
     * @access protected
     *
     * @param int $pid UID of the selected storage page
     *
     * @return array additional field storage page dropdown
     */
    protected function getPidField(int $pid): array
    {
        $fieldName = 'pid';
        $fieldId = 'task_' . $fieldName;

        $pageRepository = GeneralUtility::makeInstance(PageTreeRepository::class);
        $pages = $pageRepository->getTree(0);

        $options = [];
        foreach ($pages['_children'] as $page) {
            if ($page['doktype'] == 254) {
                $options[] = '<option value="' . $page['uid'] . '" ' . ($pid == $page['uid'] ? 'selected' : '') . ' >' . $page['title'] . '</option>';
            }
        }

        $fieldHtml = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';
        return [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.pid',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
    }

    /**
     * Return HTML for owner text field
     *
     * @access protected
     *
     * @param string $owner registered owner
     *
     * @return array additional field owner text field
     */
    protected function getOwnerField(string $owner): array
    {
        $fieldName = 'owner';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="text" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="' . $owner . '" >';
        return [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.owner',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
    }

    /**
     * Fetches all Solr cores on given page.
     *
     * @access protected
     *
     * @param int $pid UID of storage page
     *
     * @return array Array of valid Solr cores
     */
    private function getSolrCores(int $pid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_solrcores');

        $solrCores = [];
        $result = $queryBuilder->select('uid', 'label')
            ->from('tx_dlf_solrcores')
            ->where(
                $queryBuilder->expr()
                    ->eq('pid', $queryBuilder->createNamedParameter((int) $pid, Connection::PARAM_INT))
            )
            ->execute();

        while ($record = $result->fetchAssociative()) {
            $solrCores[$record['label']] = $record['uid'];
        }

        return $solrCores;
    }
}
