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

namespace Kitodo\Dlf\Updates;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Update PID for Format table.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
#[UpgradeWizard('formatUpdater')]
class FormatUpdater implements UpgradeWizardInterface, ChattyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @access private
     * @var string
     */
    private string $table = 'tx_dlf_formats';

    /**
     * @access protected
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * Return the speaking name of this wizard
     *
     * @access public
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Migrate Kitodo.Presentation Format PIDs';
    }

    /**
     * Get description
     *
     * @access public
     *
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Convert Format PIDs from root level to DLF storage directory.';
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @access public
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        /** @var int $numRecords */
        $numRecords = $this->getRecordsFromTable(true);
        if ($numRecords > 0) {
            return true;
        }
        return false;
    }

    /**
     * @access public
     *
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * @access public
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @access public
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $result = true;
        try {
            /** @var int $numRecords */
            $numRecords = $this->getRecordsFromTable(true);
            if ($numRecords > 0) {
                $this->performUpdate();
            }
        } catch (\Exception $e) {
            // If something goes wrong, migrateField() logs an error
            $result = false;
        }
        return $result;
    }

    /**
     * Get records from table where the field to migrate is equal 0
     *
     * @access public
     *
     * @return array|int
     *
     * @throws RuntimeException
     */
    protected function getRecordsFromTable(bool $countOnly = false): array|int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $allResults = [];
        $numResults = 0;

        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()->removeAll();
        try {
            $result = $queryBuilder
                ->select('uid', 'pid')
                ->from($this->table)
                ->where(
                    $queryBuilder->expr()->eq('pid', 0)
                )
                ->orderBy('uid')
                ->executeQuery()
                ->fetchAllAssociative();
            if ($countOnly === true) {
                $numResults += count($result);
            } else {
                $allResults[] = $result;
            }
        } catch (Exception $e) {
            throw new RuntimeException(
                'Database query failed. Error was: ' . $e->getPrevious()->getMessage(),
                1511950674
            );
        }

        if ($countOnly === true) {
            return $numResults;
        } else {
            return $allResults;
        }
    }


    /**
     * Performs the database update.
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE on error
     */
    protected function performUpdate(): bool
    {
        try {
            $records = $this->getRecordsFromTable();
            foreach ($records as $record) {
                $this->migrateField($record);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Migrates a single field.
     *
     * @access public
     *
     * @param array $row
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function migrateField(array $row): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $solrQueryBuilder = $connectionPool->getQueryBuilderForTable($this->table);
        $result = $solrQueryBuilder
            ->select('pid')
            ->from($this->table)
            ->where(
                $solrQueryBuilder->expr()->eq('uid', 1)
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAssociative();

        if ($result !== false) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($this->table);
            $queryBuilder->update($this->table)->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($row['uid'], Connection::PARAM_INT)
                )
            )->set('pid', $result['pid'])->executeStatement();
        }
    }
}
