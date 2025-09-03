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
 * Additional fields for reindex documents task.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ReindexAdditionalFieldProvider extends BaseAdditionalFieldProvider
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
            $taskInfo['coll'] = $task->getColl();
            $taskInfo['pid'] = $task->getPid();
            $taskInfo['solr'] = $task->getSolr();
            $taskInfo['owner'] = $task->getOwner();
            $taskInfo['all'] = $task->isAll();
            $taskInfo['softCommit'] = $task->isSoftCommit();
        } else {
            $taskInfo['dryRun'] = false;
            $taskInfo['coll'] = [];
            $taskInfo['pid'] = - 1;
            $taskInfo['solr'] = - 1;
            $taskInfo['owner'] = '';
            $taskInfo['all'] = false;
            $taskInfo['softCommit'] = false;
        }

        $additionalFields = [];

        // Checkbox for dry-run
        $additionalFields['dryRun'] = $this->getDryRunField($taskInfo['dryRun']);

        // Select for collection(s)
        $fieldName = 'coll';
        $fieldId = 'task_' . $fieldName;
        $options = $this->getCollOptions($taskInfo['coll'], $taskInfo['pid']);
        ;
        $fieldHtml = '<select name="tx_scheduler[' . $fieldName . '][]" id="' . $fieldId . '" size="10" multiple="multiple">' . implode("\n", $options) . '</select>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.coll',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // DropDown for storage page
        $additionalFields['pid'] = $this->getPidField($taskInfo['pid']);

        // DropDown for Solr core
        $additionalFields['solr'] = $this->getSolrField($taskInfo['solr'], $taskInfo['pid']);

        // Text field for owner
        $additionalFields['owner'] = $this->getOwnerField($taskInfo['owner']);

        // Checkbox for all
        $fieldName = 'all';
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<input type="checkbox" name="tx_scheduler[' . $fieldName . ']" id="' . $fieldId . '" value="1"' .
            ($taskInfo['all'] ? ' checked="checked"' : '') . '>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_tasks.xlf:additionalFields.all',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];

        // Checkbox for soft commit
        $additionalFields['softCommit'] = $this->getSoftCommitField($taskInfo['softCommit']);

        return $additionalFields;
    }

    /**
     * Generates HTML options for collections
     *
     * @param array $coll Selected collections
     * @param int $pid UID of storage page
     *
     * @return array HTML of selectbox options
     */
    private function getCollOptions(array $coll, int $pid): array
    {
        $options = [];
        $collections = $this->getCollections($pid);
        foreach ($collections as $label => $uid) {
            $options[] = '<option value="' . $uid . '" ' . (in_array($uid, $coll) ? 'selected' : '') . ' >' . $label . '</option>';
        }
        return $options;
    }

    /**
     * Fetches all collections on given storage page.
     *
     * @access protected
     *
     * @param int $pid The UID of the storage page
     *
     * @return array Array of collections
     */
    private function getCollections(int $pid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_collections');

        $collections = [];
        $result = $queryBuilder->select('uid', 'label')
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()
                    ->eq('pid', $queryBuilder->createNamedParameter((int) $pid, Connection::PARAM_INT))
            )
            ->executeQuery();

        while ($record = $result->fetchAssociative()) {
            $collections[$record['label']] = $record['uid'];
        }

        return $collections;
    }
}
