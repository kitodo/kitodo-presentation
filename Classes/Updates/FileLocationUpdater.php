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
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Updates\ChattyInterface;

/**
 * Migrate reference of thumbnail image in collections record.
 */
class FileLocationUpdater implements UpgradeWizardInterface, ChattyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ResourceStorage
     */
    protected $storage;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Array with table and fields to migrate
     *
     * @var array
     */
    protected $fieldsToMigrate = [
        'tx_dlf_collections' => 'thumbnail'
    ];

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return self::class;
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Migrate Kitodo.Presentation file references';
    }

    /**
     * Get description
     *
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Convert file reference of thumbnail images in collection records.';
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $numRecords = $this->getRecordsFromTable(true);
        if ($numRecords > 0) {
            return true;
        }
        return false;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
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
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $result = true;
        try {
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
     * Get records from table where the field to migrate is not empty (NOT NULL and != '')
     * and also not numeric (which means that it is migrated)
     *
     * Work based on BackendLayoutIconUpdateWizard::class
     *
     * @return array|int
     * @throws \RuntimeException
     */
    protected function getRecordsFromTable($countOnly = false)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $allResults = [];
        $numResults = 0;
        foreach (array_keys($this->fieldsToMigrate) as $table) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            try {
                $result = $queryBuilder
                    ->select('uid', 'pid', $this->fieldsToMigrate[$table])
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->isNotNull($this->fieldsToMigrate[$table]),
                        $queryBuilder->expr()->neq(
                            $this->fieldsToMigrate[$table],
                            $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->comparison(
                            'CAST(CAST(' . $queryBuilder->quoteIdentifier($this->fieldsToMigrate[$table]) . ' AS DECIMAL) AS CHAR)',
                            ExpressionBuilder::NEQ,
                            'CAST(' . $queryBuilder->quoteIdentifier($this->fieldsToMigrate[$table]) . ' AS CHAR)'
                        )
                    )
                    ->orderBy('uid')
                    ->execute()
                    ->fetchAllAssociative();
                if ($countOnly === true) {
                    $numResults += count($result);
                } else {
                    $allResults[$table] = $result;
                }
            } catch (Exception $e) {
                throw new \RuntimeException(
                    'Database query failed. Error was: ' . $e->getPrevious()->getMessage(),
                    1511950673
                );
            }
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
     * @return bool TRUE on success, FALSE on error
     */
    protected function performUpdate(): bool
    {
        $result = true;

        try {
            $storages = GeneralUtility::makeInstance(StorageRepository::class)->findAll();
            $this->storage = $storages[0];

            $records = $this->getRecordsFromTable();
            foreach ($records as $table => $recordsInTable) {
                foreach ($recordsInTable as $record) {
                    $this->migrateField($table, $record);
                }
            }
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Migrates a single field.
     *
     * @param string $table
     * @param array $row
     * @throws \Exception
     */
    protected function migrateField($table, $row)
    {
        $fieldItem = trim($row[$this->fieldsToMigrate[$table]]);

        if (empty($fieldItem) || is_numeric($fieldItem)) {
            return;
        }

        $storageUid = (int) $this->storage->getUid();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $fileUid = null;
        $sourcePath = Environment::getPublicPath() . '/' . $fieldItem;

        // maybe the file was already moved, so check if the original file still exists
        if (file_exists($sourcePath)) {

            // see if the file already exists in the storage
            $fileSha1 = sha1_file($sourcePath);

            $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file');
            $existingFileRecord = $queryBuilder->select('uid')->from('sys_file')->where(
                $queryBuilder->expr()->eq(
                    'missing',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sha1',
                    $queryBuilder->createNamedParameter($fileSha1, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($storageUid, \PDO::PARAM_INT)
                )
            )->execute()->fetchAssociative();

            // the file exists
            if (is_array($existingFileRecord)) {
                $fileUid = $existingFileRecord['uid'];
            }
        }

        if ($fileUid > 0) {
            $fields = [
                'fieldname' => $this->fieldsToMigrate[$table],
                'table_local' => 'sys_file',
                'pid' => ($table === 'pages' ? $row['uid'] : $row['pid']),
                'uid_foreign' => $row['uid'],
                'uid_local' => $fileUid,
                'tablenames' => $table,
                'crdate' => time(),
                'tstamp' => time(),
            ];

            $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');

            $result = $queryBuilder
                ->insert('sys_file_reference')
                ->values($fields)
                ->execute();

            // Update referencing table's original field to now contain the count of references,
            // which is "1" in our case.
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->update($table)->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)
                )
            )->set($this->fieldsToMigrate[$table], 1)->execute();
        }
    }
}
