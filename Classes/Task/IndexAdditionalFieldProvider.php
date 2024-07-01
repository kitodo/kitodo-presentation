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
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional fields for index document task.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class IndexAdditionalFieldProvider extends BaseAdditionalFieldProvider
{
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        if ($currentSchedulerModuleAction->equals(Action::EDIT)) {
            $taskInfo['dryRun'] = $task->isDryRun();
            $taskInfo['doc'] = $task->getDoc();
            $taskInfo['pid'] = $task->getPid();
            $taskInfo['solr'] = $task->getSolr();
            $taskInfo['owner'] = $task->getOwner();
        } else {
            $taskInfo['dryRun'] = false;
            $taskInfo['doc'] = 'https://';
            $taskInfo['pid'] = - 1;
            $taskInfo['solr'] = - 1;
            $taskInfo['owner'] = '';
        }

        $additionalFields = [];

        // Checkbox for dry-run
        $additionalFields['dryRun'] = $this->getDryRunField($taskInfo['dryRun']);

        // Text field for document URL
        $fieldName = 'doc';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="text" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="' . $taskInfo[$fieldName] . '" >';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.doc',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // DropDown for storage page
        $additionalFields['pid'] = $this->getPidField($taskInfo['pid']);

        // DropDown for Solr core
        $additionalFields['solr'] = $this->getSolrField($taskInfo['solr'], $taskInfo['pid']);

        // Text field for owner
        $additionalFields['owner'] = $this->getOwnerField($taskInfo['owner']);

        return $additionalFields;
    }
}
