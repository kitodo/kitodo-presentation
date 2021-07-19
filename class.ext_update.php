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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update class 'ext_update' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ext_update
{
    /**
     * This holds the output ready to return
     *
     * @var string
     * @access protected
     */
    protected $content = '';

    /**
     * Triggers the update option in the extension manager
     *
     * @access public
     *
     * @return bool Should the update option be shown?
     */
    public function access(): bool
    {
        if (count($this->getMetadataConfig())) {
            return true;
        }
        if ($this->oldIndexRelatedTableNames()) {
            return true;
        }
        if ($this->solariumSolrUpdateRequired()) {
            return true;
        }
        if (count($this->oldFormatClasses())) {
            return true;
        }
        if ($this->hasNoFormatForDocument()) {
            return true;
        }
        return false;
    }

    /**
     * The main method of the class
     *
     * @access public
     *
     * @return string The content that is displayed on the website
     */
    public function main(): string
    {
        // Load localization file.
        $GLOBALS['LANG']->includeLLFile('EXT:dlf/Resources/Private/Language/FlashMessages.xml');
        // Update the metadata configuration.
        if (count($this->getMetadataConfig())) {
            $this->updateMetadataConfig();
        }
        if ($this->oldIndexRelatedTableNames()) {
            $this->renameIndexRelatedColumns();
        }
        if ($this->solariumSolrUpdateRequired()) {
            $this->doSolariumSolrUpdate();
        }
        if (count($this->oldFormatClasses())) {
            $this->updateFormatClasses();
        }
        // Set tx_dlf_documents.document_format to distinguish between METS and IIIF.
        if ($this->hasNoFormatForDocument()) {
            $this->updateDocumentAddFormat();
        }
        return $this->content;
    }

    /**
     * Get all outdated metadata configuration records
     *
     * @access protected
     *
     * @return array Array of UIDs of outdated records
     */
    protected function getMetadataConfig(): array
    {
        $uids = [];
        // check if tx_dlf_metadata.xpath exists anyhow
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_metadata');

        $result = $queryBuilder
            ->select('*')
            ->from('tx_dlf_metadata')
            ->execute();

        $rows = $result->fetchAll();

        if ((count($rows) === 0) || !array_key_exists('xpath', $rows[0])) {
            return $uids;
        }
        foreach ($rows as $row) {
            if ($row['format'] === 0 && $row['xpath']) {
                $uids[] = (int) $row['uid'];
            }
        }
        return $uids;
    }

    /**
     * Check all configured Solr cores
     *
     * @access protected
     *
     * @return bool
     */
    protected function solariumSolrUpdateRequired(): bool
    {
        // Get all Solr cores that were not deleted.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_solrcores');
        $result = $queryBuilder
            ->select('index_name')
            ->from('tx_dlf_solrcores')
            ->execute();

        while ($resArray = $result->fetch()) {
            // Instantiate search object.
            $solr = Solr::getInstance($resArray['index_name']);
            if (!$solr->ready) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check for old format classes
     *
     * @access protected
     *
     * @return array containing old format classes
     */
    protected function oldFormatClasses(): array
    {
        $oldRecords = [];
        // Get all records with outdated configuration.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_formats');

        $result = $queryBuilder
            ->select('tx_dlf_formats.uid AS uid', 'tx_dlf_formats.type AS type')
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->isNotNull('tx_dlf_formats.class'),
                $queryBuilder->expr()->neq('tx_dlf_formats.class', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->like('tx_dlf_formats.class', $queryBuilder->createNamedParameter('%tx_dlf_%'))
            )
            ->execute();
        while ($resArray = $result->fetch()) {
            $oldRecords[$resArray['uid']] = $resArray['type'];
        }

        return $oldRecords;
    }

    /**
     * Check for old index related columns
     *
     * @access protected
     *
     * @return bool true if old index related columns exist
     */
    protected function oldIndexRelatedTableNames(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('INFORMATION_SCHEMA.COLUMNS');

        $result = $queryBuilder
            ->select('column_name')
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_NAME = "tx_dlf_metadata"')
            ->execute();
        while ($resArray = $result->fetch()) {
            if (
                $resArray['column_name'] === 'tokenized'
                || $resArray['column_name'] === 'stored'
                || $resArray['column_name'] === 'indexed'
                || $resArray['column_name'] === 'boost'
                || $resArray['column_name'] === 'autocomplete'
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * check if document has format
     *
     * @access protected
     * @param bool $checkStructureOnly
     * @return bool
     */
    protected function hasNoFormatForDocument($checkStructureOnly = false): bool
    {
        // Check if column "document_format" exists.
        $database = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('INFORMATION_SCHEMA.COLUMNS');

        $result = $queryBuilder
            ->select('COLUMN_NAME')
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_NAME="tx_dlf_documents" AND TABLE_SCHEMA="' . $database . '" AND COLUMN_NAME="document_format"')
            ->execute();
        while ($resArray = $result->fetch()) {
            if ($resArray['COLUMN_NAME'] === 'document_format') {
                if ($checkStructureOnly) {
                    return false;
                }
                // Check if column has empty fields.
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('INFORMATION_SCHEMA.COLUMNS');
                $count = $queryBuilder
                    ->count('uid')
                    ->from('tx_dlf_documents')
                    ->where('document_format="" OR document_format IS NULL')
                    ->execute()
                    ->fetchColumn(0);

                if ($count === 0) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Copy the data of the old index related columns to the new columns
     *
     * @access protected
     *
     * @return void
     */
    protected function renameIndexRelatedColumns(): void
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_metadata');

        $result = $queryBuilder
            ->update('tx_dlf_metadata', 'm')
            ->set('m.index_tokenized', 'm.tokenized')
            ->set('m.index_stored', 'm.stored')
            ->set('m.index_indexed', 'm.indexed')
            ->set('m.index_boost', 'm.boost')
            ->set('m.index_autocomplete', 'm.autocomplete')
            ->execute();

        if ($result) {
            Helper::addMessage(
                htmlspecialchars($GLOBALS['LANG']->getLL('update.copyIndexRelatedColumnsOkay')),
                htmlspecialchars($GLOBALS['LANG']->getLL('update.copyIndexRelatedColumns')),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            Helper::addMessage(
                htmlspecialchars($GLOBALS['LANG']->getLL('update.copyIndexRelatedColumnsNotOkay')),
                htmlspecialchars($GLOBALS['LANG']->getLL('update.copyIndexRelatedColumns')),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
        }
        $this->content .= Helper::renderFlashMessages();
    }

    /**
     * Update all outdated format records
     *
     * @access protected
     *
     * @return void
     */
    protected function updateFormatClasses(): void
    {
        $oldRecords = $this->oldFormatClasses();
        $newValues = [
            'ALTO' => 'Kitodo\\Dlf\\Format\\Alto', // Those are effectively single backslashes
            'MODS' => 'Kitodo\\Dlf\\Format\\Mods',
            'TEIHDR' => 'Kitodo\\Dlf\\Format\\TeiHeader'
        ];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_formats');

        foreach ($oldRecords as $uid => $type) {
            $queryBuilder
                ->update('tx_dlf_formats')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid))
                )
                ->set('class', $newValues[$type])
                ->execute();
        }
        Helper::addMessage(
            htmlspecialchars($GLOBALS['LANG']->getLL('update.FormatClassesOkay')),
            htmlspecialchars($GLOBALS['LANG']->getLL('update.FormatClasses')),
            \TYPO3\CMS\Core\Messaging\FlashMessage::OK
        );
        $this->content .= Helper::renderFlashMessages();
    }

    /**
     * Update all outdated metadata configuration records
     *
     * @access protected
     *
     * @return void
     */
    protected function updateMetadataConfig(): void
    {
        $metadataUids = $this->getMetadataConfig();
        if (!empty($metadataUids)) {
            $data = [];

            // Get all old metadata configuration records.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_metadata');
            $result = $queryBuilder
                ->select('tx_dlf_metadata.uid AS uid', 'tx_dlf_metadata.pid AS pid', 'tx_dlf_metadata.cruser_id AS cruser_id', 'tx_dlf_metadata.encoded AS encoded', 'tx_dlf_metadata.xpath AS xpath', 'tx_dlf_metadata.xpath_sorting AS xpath_sorting')
                ->from('tx_dlf_metadata')
                ->where(
                    $queryBuilder->expr()->in(
                        'tx_dlf_metadata.uid',
                        $queryBuilder->createNamedParameter(
                            $metadataUids,
                            \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY
                        )
                    )
                )
                ->execute();

            while ($resArray = $result->fetch()) {
                $newId = uniqid('NEW');
                // Copy record to new table.
                $data['tx_dlf_metadataformat'][$newId] = [
                    'pid' => $resArray['pid'],
                    'cruser_id' => $resArray['cruser_id'],
                    'parent_id' => $resArray['uid'],
                    'encoded' => $resArray['encoded'],
                    'xpath' => $resArray['xpath'],
                    'xpath_sorting' => $resArray['xpath_sorting']
                ];
                // Add reference to old table.
                $data['tx_dlf_metadata'][$resArray['uid']]['format'] = $newId;
            }
            if (!empty($data)) {
                // Process datamap.
                $substUids = Helper::processDBasAdmin($data);
                unset($data);
                if (!empty($substUids)) {
                    Helper::addMessage(
                        htmlspecialchars($GLOBALS['LANG']->getLL('update.metadataConfigOkay')),
                        htmlspecialchars($GLOBALS['LANG']->getLL('update.metadataConfig')),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );
                } else {
                    Helper::addMessage(
                        htmlspecialchars($GLOBALS['LANG']->getLL('update.metadataConfigNotOkay')),
                        htmlspecialchars($GLOBALS['LANG']->getLL('update.metadataConfig')),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                    );
                }
                $this->content .= Helper::renderFlashMessages();
            }
        }
    }



    /**
     * Create all configured Solr cores
     *
     * @access protected
     *
     * @return void
     */
    protected function doSolariumSolrUpdate(): void
    {
        $error = false;
        // Get all Solr cores that were not deleted.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_solrcores');
        $result = $queryBuilder
            ->select('index_name')
            ->from('tx_dlf_solrcores')
            ->execute();

        while ($resArray = $result->fetch()) {
            // Create core if it doesn't exist.
            if (Solr::createCore($resArray['index_name']) !== $resArray['index_name']) {
                Helper::addMessage(
                    htmlspecialchars($GLOBALS['LANG']->getLL('update.solariumSolrUpdateNotOkay')),
                    htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('update.solariumSolrUpdate'), $resArray['index_name'])),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
                $this->content .= Helper::renderFlashMessages();
                $error = true;
            }
        }
        if (!$error) {
            Helper::addMessage(
                htmlspecialchars($GLOBALS['LANG']->getLL('update.solariumSolrUpdateOkay')),
                htmlspecialchars($GLOBALS['LANG']->getLL('update.solariumSolrUpdate')),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
            $this->content .= Helper::renderFlashMessages();
        }
    }

    /**
     * Add format type to outdated tx_dlf_documents rows
     *
     * @return void
     */
    protected function updateDocumentAddFormat(): void
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_documents');

        $result = $queryBuilder
            ->update('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('document_format', $queryBuilder->createNamedParameter(null)),
                    $queryBuilder->expr()->eq('document_format', $queryBuilder->createNamedParameter(''))
                )
            )
            ->set('document_format', 'METS')
            ->execute();

        if ($result) {
            Helper::addMessage(
                htmlspecialchars($GLOBALS['LANG']->getLL('update.documentSetFormatForOldEntriesOkay')),
                htmlspecialchars($GLOBALS['LANG']->getLL('update.documentSetFormatForOldEntries')),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            Helper::addMessage(
                htmlspecialchars($GLOBALS['LANG']->getLL('update.documentSetFormatForOldEntriesNotOkay')),
                htmlspecialchars($GLOBALS['LANG']->getLL('update.documentSetFormatForOldEntries')),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
        }
        $this->content .= Helper::renderFlashMessages();
    }
}
