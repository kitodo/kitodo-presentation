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

use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Additional fields for reindex documents task.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class OptimizeAdditionalFieldProvider extends BaseAdditionalFieldProvider
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
            $taskInfo['solr'] = $task->getSolr();
            $taskInfo['commit'] = $task->isCommit();
            $taskInfo['optimize'] = $task->isOptimize();
        } else {
            $taskInfo['solr'] = - 1;
            $taskInfo['commit'] = false;
            $taskInfo['optimize'] = false;
        }

        $additionalFields = [];

        // DropDown for Solr core
        $additionalFields['solr'] = $this->getSolrField($taskInfo['solr']);

        // Checkbox for commit
        $fieldName = 'commit';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="checkbox" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="1"' . ($taskInfo['commit'] ? ' checked="checked"' : '') . '>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.commit',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // Checkbox for optimize
        $fieldName = 'optimize';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="checkbox" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="1"' . ($taskInfo['optimize'] ? ' checked="checked"' : '') . '>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.optimize',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        return $additionalFields;
    }
}
