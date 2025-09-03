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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Additional fields for harvest documents task.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class HarvestAdditionalFieldProvider extends BaseAdditionalFieldProvider
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param BaseTask $task The task object being edited. Null when adding a task!
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        /** @var BaseTask $task */
        if ($this->isEditAction($schedulerModule)) {
            $taskInfo['dryRun'] = $task->isDryRun();
            $taskInfo['lib'] = $task->getLib();
            $taskInfo['pid'] = $task->getPid();
            $taskInfo['solr'] = $task->getSolr();
            $taskInfo['from'] = $task->getFrom();
            $taskInfo['until'] = $task->getUntil();
            $taskInfo['set'] = $task->getSet();
            $taskInfo['softCommit'] = $task->isSoftCommit();
        } else {
            $taskInfo['dryRun'] = false;
            $taskInfo['lib'] = - 1;
            $taskInfo['pid'] = - 1;
            $taskInfo['solr'] = - 1;
            $taskInfo['from'] = '';
            $taskInfo['until'] = '';
            $taskInfo['set'] = '';
            $taskInfo['softCommit'] = false;
        }

        $additionalFields = [];

        // Checkbox for dry-run
        $additionalFields['dryRun'] = $this->getDryRunField($taskInfo['dryRun']);

        // Text field for library
        $fieldName = 'lib';
        $fieldId = 'task_' . $fieldName;

        $allLibraries = $this->getLibraries($taskInfo['pid']);
        $options = [];
        $options[] = '<option value="-1"></option>';
        foreach ($allLibraries as $label => $uid) {
            $options[] = '<option value="' . $uid . '" ' . ($taskInfo['lib'] == $uid ? 'selected' : '') . ' >' . $label . '</option>';
        }

        $fieldHtml = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.lib',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // DropDown for Pid
        $additionalFields['pid'] = $this->getPidField($taskInfo['pid']);

        // DropDown for Solr core
        $additionalFields['solr'] = $this->getSolrField($taskInfo['solr'], $taskInfo['pid']);

        // Text field for from
        $fieldName = 'from';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="date" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="' . $taskInfo[$fieldName] . '" >';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.from',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // Text field for until
        $fieldName = 'until';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="date" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="' . $taskInfo[$fieldName] . '" >';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.until',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // Text field for set
        $fieldName = 'set';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="text" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="' . $taskInfo[$fieldName] . '" >';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.set',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // Checkbox for soft commit
        $additionalFields['softCommit'] = $this->getSoftCommitField($taskInfo['softCommit']);

        return $additionalFields;
    }

    /**
     * Fetches all libraries from given page.
     *
     * @access private
     *
     * @param int $pid The UID of the storage page
     *
     * @return array Array of libraries
     */
    private function getLibraries(int $pid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_libraries');

        $libraries = [];
        $result = $queryBuilder->select('uid', 'label')
            ->from('tx_dlf_libraries')
            ->where(
                $queryBuilder->expr()
                    ->eq('pid', $queryBuilder->createNamedParameter((int) $pid, Connection::PARAM_INT))
            )
            ->executeQuery();

        while ($record = $result->fetchAssociative()) {
            $libraries[$record['label']] = $record['uid'];
        }

        return $libraries;
    }
}
