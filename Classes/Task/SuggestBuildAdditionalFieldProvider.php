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
 * Additional fields for building the suggestion dictionary task.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SuggestBuildAdditionalFieldProvider extends BaseAdditionalFieldProvider
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
        } else {
            $taskInfo['solr'] = - 1;
        }

        $additionalFields = [];

        // DropDown for Solr core
        $additionalFields['solr'] = $this->getSolrField($taskInfo['solr']);

        return $additionalFields;
    }
}
