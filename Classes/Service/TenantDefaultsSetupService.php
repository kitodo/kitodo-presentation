<?php

declare(strict_types=1);

namespace Kitodo\Dlf\Service;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr\Solr;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Shared service for applying the same tenant defaults as the backend new-tenant module.
 */
final class TenantDefaultsSetupService
{
    public function __construct(
        private readonly LocalizationFactory $languageFactory,
        private readonly SiteFinder $siteFinder,
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Runs selected tenant setup steps for the given configuration folder.
     *
     * @param array{formats:bool,structures:bool,metadata:bool,solrCore:bool} $steps
     * @return array{formats:int,structures:int,metadata:int,solrCore:?int}
     */
    public function run(int $configurationPageId, array $steps): array
    {
        $result = [
            'formats' => 0,
            'structures' => 0,
            'metadata' => 0,
            'solrCore' => null,
        ];

        if ($steps['formats']) {
            $result['formats'] = $this->addFormats($configurationPageId);
        }
        if ($steps['structures']) {
            $result['structures'] = $this->addStructures($configurationPageId);
        }
        if ($steps['metadata']) {
            $result['metadata'] = $this->addMetadata($configurationPageId);
        }
        if ($steps['solrCore']) {
            $result['solrCore'] = $this->addSolrCore($configurationPageId);
        }

        return $result;
    }

    private function addFormats(int $configurationPageId): int
    {
        $formatsDefaults = $this->getRecords('Format');
        $data = [];
        $formatTempIds = [];

        foreach ($formatsDefaults as $type => $values) {
            if ($this->recordExists('tx_dlf_formats', [
                'pid' => $configurationPageId,
                'type' => $type,
            ])) {
                continue;
            }

            $formatId = uniqid('NEW', true);
            $formatTempIds[] = $formatId;
            $data['tx_dlf_formats'][$formatId] = [
                'pid' => $configurationPageId,
                'type' => $type,
                'root' => $values['root'],
                'namespace' => $values['namespace'],
                'class' => $values['class'],
            ];
        }

        if ($formatTempIds === []) {
            return 0;
        }

        $insertedIds = Helper::processDatabaseAsAdmin($data, [], true);
        return count(array_intersect_key(array_flip($formatTempIds), $insertedIds));
    }

    private function addMetadata(int $configurationPageId): int
    {
        $metadataDefaults = $this->getRecords('Metadata');
        $siteLanguages = $this->getSiteLanguages($configurationPageId);
        $defaultLanguage = $siteLanguages[0];
        $metadataLabels = $this->languageFactory->getParsedData(
            'EXT:dlf/Resources/Private/Language/locallang_metadata.xlf',
            $defaultLanguage->getLocale()->getLanguageCode()
        );

        $availableFormats = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_dlf_formats');
        $queryBuilder->getRestrictions()->removeAll();
        foreach ($queryBuilder
            ->select('uid', 'root')
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($configurationPageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative() as $row) {
            $availableFormats[$row['root']] = (int)$row['uid'];
        }

        $defaultWrap = BackendUtility::getTcaFieldConfiguration('tx_dlf_metadata', 'wrap')['default'];
        $data = [];
        $metadataTempIds = [];

        foreach ($metadataDefaults as $indexName => $values) {
            if ($this->recordExists('tx_dlf_metadata', [
                'pid' => $configurationPageId,
                'index_name' => $indexName,
                'sys_language_uid' => 0,
            ])) {
                continue;
            }

            $formatIds = [];
            foreach ($values['format'] as $format) {
                $format['encoded'] = $availableFormats[$format['format_root']] ?? null;
                unset($format['format_root']);
                $formatId = uniqid('NEW', true);
                $formatIds[] = $formatId;
                $data['tx_dlf_metadataformat'][$formatId] = $format + ['pid' => $configurationPageId];
            }

            $metadataId = uniqid('NEW', true);
            $metadataTempIds[$metadataId] = $indexName;
            $data['tx_dlf_metadata'][$metadataId] = [
                'pid' => $configurationPageId,
                'label' => $this->getLLL('metadata.' . $indexName, $defaultLanguage->getLocale()->getLanguageCode(), $metadataLabels),
                'index_name' => $indexName,
                'format' => implode(',', $formatIds),
                'default_value' => $values['default_value'],
                'wrap' => !empty($values['wrap']) ? $values['wrap'] : $defaultWrap,
                'index_tokenized' => $values['index_tokenized'],
                'index_stored' => $values['index_stored'],
                'index_indexed' => $values['index_indexed'],
                'index_boost' => $values['index_boost'],
                'is_sortable' => $values['is_sortable'],
                'is_facet' => $values['is_facet'],
                'is_listed' => $values['is_listed'],
                'index_autocomplete' => $values['index_autocomplete'],
            ];
        }

        if ($metadataTempIds === []) {
            return 0;
        }

        $insertedIds = Helper::processDatabaseAsAdmin($data, [], true);
        $insertedMetadata = [];
        foreach ($metadataTempIds as $tempId => $indexName) {
            if (isset($insertedIds[$tempId])) {
                $insertedMetadata[(int)$insertedIds[$tempId]] = $indexName;
            }
        }

        foreach ($siteLanguages as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                continue;
            }

            $translateData = [];
            foreach ($insertedMetadata as $uid => $indexName) {
                $translateData['tx_dlf_metadata'][uniqid('NEW', true)] = [
                    'pid' => $configurationPageId,
                    'sys_language_uid' => $siteLanguage->getLanguageId(),
                    'l18n_parent' => $uid,
                    'label' => $this->getLLL('metadata.' . $indexName, $siteLanguage->getLocale()->getLanguageCode(), $metadataLabels),
                ];
            }

            if ($translateData !== []) {
                Helper::processDatabaseAsAdmin($translateData);
            }
        }

        return count($insertedMetadata);
    }

    private function addSolrCore(int $configurationPageId): ?int
    {
        $existingUid = $this->findUidByFields('tx_dlf_solrcores', ['pid' => $configurationPageId]);
        if ($existingUid !== null) {
            return $existingUid;
        }

        $siteLanguages = $this->getSiteLanguages($configurationPageId);
        $beLabels = $this->languageFactory->getParsedData(
            'EXT:dlf/Resources/Private/Language/locallang_be.xlf',
            $siteLanguages[0]->getLocale()->getLanguageCode()
        );
        $indexName = Solr::createCore('');
        if ($indexName === '') {
            return null;
        }

        $connection = $this->connectionPool->getConnectionForTable('tx_dlf_solrcores');
        $connection->insert('tx_dlf_solrcores', [
            'pid' => $configurationPageId,
            'tstamp' => time(),
            'crdate' => time(),
            'cruser_id' => 0,
            'deleted' => 0,
            'label' => $this->getLLL('flexform.solrcore', $siteLanguages[0]->getLocale()->getLanguageCode(), $beLabels) . ' (PID ' . $configurationPageId . ')',
            'index_name' => $indexName,
        ]);

        return (int)$connection->lastInsertId('tx_dlf_solrcores');
    }

    private function addStructures(int $configurationPageId): int
    {
        $structureDefaults = $this->getRecords('Structure');
        $siteLanguages = $this->getSiteLanguages($configurationPageId);
        $defaultLanguage = $siteLanguages[0];
        $structureLabels = $this->languageFactory->getParsedData(
            'EXT:dlf/Resources/Private/Language/locallang_structure.xlf',
            $defaultLanguage->getLocale()->getLanguageCode()
        );

        $data = [];
        $structureTempIds = [];
        foreach ($structureDefaults as $indexName => $values) {
            if ($this->recordExists('tx_dlf_structures', [
                'pid' => $configurationPageId,
                'index_name' => $indexName,
                'sys_language_uid' => 0,
            ])) {
                continue;
            }

            $structureId = uniqid('NEW', true);
            $structureTempIds[$structureId] = $indexName;
            $data['tx_dlf_structures'][$structureId] = [
                'pid' => $configurationPageId,
                'toplevel' => $values['toplevel'],
                'label' => $this->getLLL('structure.' . $indexName, $defaultLanguage->getLocale()->getLanguageCode(), $structureLabels),
                'index_name' => $indexName,
                'oai_name' => $values['oai_name'],
                'thumbnail' => 0,
            ];
        }

        if ($structureTempIds === []) {
            return 0;
        }

        $insertedIds = Helper::processDatabaseAsAdmin($data, [], true);
        $insertedStructures = [];
        foreach ($structureTempIds as $tempId => $indexName) {
            if (isset($insertedIds[$tempId])) {
                $insertedStructures[(int)$insertedIds[$tempId]] = $indexName;
            }
        }

        foreach ($siteLanguages as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                continue;
            }

            $translateData = [];
            foreach ($insertedStructures as $uid => $indexName) {
                $translateData['tx_dlf_structures'][uniqid('NEW', true)] = [
                    'pid' => $configurationPageId,
                    'sys_language_uid' => $siteLanguage->getLanguageId(),
                    'l18n_parent' => $uid,
                    'label' => $this->getLLL('structure.' . $indexName, $siteLanguage->getLocale()->getLanguageCode(), $structureLabels),
                ];
            }

            if ($translateData !== []) {
                Helper::processDatabaseAsAdmin($translateData);
            }
        }

        return count($insertedStructures);
    }

    /**
     * @return SiteLanguage[]
     */
    private function getSiteLanguages(int $pageId): array
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageId)->getLanguages();
        } catch (SiteNotFoundException) {
            return (new NullSite())->getLanguages();
        }
    }

    private function getLLL(string $index, string $lang, array $langArray): string
    {
        if (isset($langArray[$lang][$index][0]['target'])) {
            return $langArray[$lang][$index][0]['target'];
        }
        if (isset($langArray['default'][$index][0]['target'])) {
            return $langArray['default'][$index][0]['target'];
        }
        return 'Missing translation for ' . $index;
    }

    private function getRecords(string $recordType): array
    {
        $filePath = GeneralUtility::getFileAbsFileName('EXT:dlf/Resources/Private/Data/' . $recordType . 'Defaults.json');
        if (!file_exists($filePath)) {
            return [];
        }
        $fileContents = file_get_contents($filePath);
        if (!is_string($fileContents)) {
            return [];
        }
        $records = json_decode($fileContents, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($records) ? $records : [];
    }

    private function recordExists(string $table, array $fields): bool
    {
        return $this->findUidByFields($table, $fields) !== null;
    }

    private function findUidByFields(string $table, array $fields): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $constraints = [$queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))];
        foreach ($fields as $field => $value) {
            $type = is_int($value) ? Connection::PARAM_INT : Connection::PARAM_STR;
            $constraints[] = $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value, $type));
        }
        $row = $queryBuilder->select('uid')->from($table)->where(...$constraints)->setMaxResults(1)->executeQuery()->fetchAssociative();
        return $row !== false ? (int)$row['uid'] : null;
    }
}
