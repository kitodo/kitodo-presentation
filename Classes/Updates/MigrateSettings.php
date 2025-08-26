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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Class MigrateSettings
 * 
 * @package TYPO3
 * @subpackage dlf
 *
 * @internal
 */
#[UpgradeWizard('migrateSettings')]
class MigrateSettings implements UpgradeWizardInterface
{
    /**
     * Return the speaking name of this wizard
     *
     * @access public
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Migrate Kitodo.Presentation plugins to use Extbase settings naming scheme';
    }

    /**
     * Return the description for this wizard
     *
     * @access public
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This wizard migrates existing front end plugins of the extension Kitodo.Presentation (dlf) to' .
            ' make use of the Extbase naming scheme. Therefore it updates the field values' .
            ' "pi_flexform" within the tt_content table';
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
        // Get all tt_content data of Kitodo.Presentation and update their flexforms settings
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder->select('uid')
            ->addSelect('pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->like('list_type', $queryBuilder->createNamedParameter('dlf_%'))
            )
            ->executeQuery();

        // Update the found record sets
        while ($record = $statement->fetchAssociative()) {
            $queryBuilder = $connection->createQueryBuilder();
            $updateResult = $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], Connection::PARAM_INT)
                    )
                )
                ->set('pi_flexform', $this->migrateFlexFormSettings($record['pi_flexform']))
                ->executeStatement();

            // exit if at least one update statement is not successful
            if (!((bool) $updateResult)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is an update necessary?
     *
     * Looks for fe plugins in tt_content table to be migrated
     *
     * @access public
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $oldSettingsFound = false;

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder->select('uid')
            ->addSelect('pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->like('list_type', $queryBuilder->createNamedParameter('dlf_%'))
            )
            ->executeQuery();

        // Update the found record sets
        while ($record = $statement->fetchAssociative()) {
            $oldSettingsFound = $this->checkForOldSettings($record['pi_flexform']);
            if ($oldSettingsFound === true) {
                // We found at least one field to be updated --> break here
                break;
            }
        }

        return $oldSettingsFound;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @access public
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }


    /**
     * @access protected
     *
     * @param string $oldValue
     *
     * @return string
     */
    protected function migrateFlexFormSettings(string $oldValue): string
    {
        $xml = simplexml_load_string($oldValue);

        // get all field elements
        $fields = $xml->xpath("//field");

        foreach ($fields as $field) {
            // change the index attribute if it doesn't start with 'settings.' yet
            if (!str_contains($field['index'], 'settings.')) {
                $field['index'] = 'settings.' . $field['index'];
            }
        }

        return $xml->asXML();

    }

    /**
     * @access protected
     *
     * @param string $flexFormXml
     *
     * @return bool
     */
    protected function checkForOldSettings(string $flexFormXml): bool
    {
        $xml = simplexml_load_string($flexFormXml);

        // get all field elements with value of attribute index not containing "settings."
        $fields = $xml->xpath("//field[not(starts-with(@index, 'settings.'))]");

        return (bool) $fields;
    }

}
