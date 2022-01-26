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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Upgrade wizard to add default metadata formats.
 */
class AddDefaultFormats implements UpgradeWizardInterface, ChattyInterface, LoggerAwareInterface, RepeatableInterface
{
    use LoggerAwareTrait;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @var array[]
     */
    protected $defaultFormats;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->defaultFormats = require ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Data/FormatDefaults.php';
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

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
        return 'Create default metadata formats.';
    }

    /**
     * Get description
     *
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Declare namespaces of default metadata formats such as MODS and ALTO.';
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
        return $this->countMissingFormats() !== 0;
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
        $insertRows = [];

        foreach ($this->getMissingFormats() as $type => $data) {
            $insertRows[] = [
                'type' => $type,
                'root' => $data['root'],
                'namespace' => $data['namespace'],
                'class' => $data['class'],
            ];
        }

        try {
            $this->connectionPool
                ->getConnectionForTable('tx_dlf_formats')
                ->bulkInsert('tx_dlf_formats', $insertRows, ['type', 'root', 'namespace', 'class']);

            return true;
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());

            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }

            return false;
        }
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @return array
     */
    protected function getMissingFormats(): array
    {
        $missingFormats = $this->defaultFormats;

        $availableFormats = $this->queryAvailableFormats()
            ->select('type')
            ->execute();

        while ($resArray = $availableFormats->fetch()) {
            unset($missingFormats[$resArray['type']]);
        }

        return $missingFormats;
    }

    /**
     * @return int
     */
    protected function countMissingFormats(): int
    {
        $numTotalDefaultFormats = count($this->defaultFormats);

        $numExistingDefaultFormats = $this->queryAvailableFormats()
            ->count('uid')
            ->execute()
            ->fetchColumn(0);

        return $numTotalDefaultFormats - $numExistingDefaultFormats;
    }

    /**
     * @return QueryBuilder
     */
    protected function queryAvailableFormats(bool $count = false): QueryBuilder
    {
        $defaultFormatTypes = array_keys($this->defaultFormats);

        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('tx_dlf_formats');

        return $queryBuilder
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->in('type', $queryBuilder->createNamedParameter($defaultFormatTypes, Connection::PARAM_STR_ARRAY)),
                // Apparently, TCA is not loaded in this context, so we need to manually check if the field is marked as deleted
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );
    }
}
