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

namespace Kitodo\Dlf\Service;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Configuration\YamlConfigurationProvider;
use Kitodo\Dlf\Domain\Model\Format;
use Kitodo\Dlf\Domain\Model\Metadata;
use Kitodo\Dlf\Domain\Model\SolrCore;
use Kitodo\Dlf\Domain\Model\Structure;
use Kitodo\Dlf\Domain\Repository\FormatRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the default tenant records (formats, structures, metadata, Solr core) in a storage folder.
 *
 * Used by the backend module 'New Tenant' and the demo bootstrap.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class TenantService
{
    public function __construct(
        protected readonly FormatRepository $formatRepository,
        protected readonly MetadataRepository $metadataRepository,
        protected readonly StructureRepository $structureRepository,
        protected readonly SolrCoreRepository $solrCoreRepository,
        protected readonly LocalizationFactory $languageFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly SiteFinder $siteFinder
    ) {
    }

    /**
     * Adds all default tenant records to the given storage folder.
     *
     * @access public
     *
     * @param non-negative-int $pid The storage folder's page id
     *
     * @return void
     */
    public function addAll(int $pid): void
    {
        // Formats must exist before metadata, since metadata records reference them.
        $this->addFormats($pid);
        $this->addStructures($pid);
        $this->addMetadata($pid);
        $this->addSolrCore($pid);
    }

    /**
     * Adds missing format records.
     *
     * @access public
     *
     * @param non-negative-int $pid
     *
     * @return void
     */
    public function addFormats(int $pid): void
    {
        $this->formatRepository->useStoragePid($pid);

        $doPersist = false;

        foreach ($this->getRecords('format') as $type => $values) {
            // if default format record is not found, add it to the repository
            if ($this->formatRepository->findOneBy(['type' => $type]) === null) {
                $newRecord = GeneralUtility::makeInstance(Format::class);
                $newRecord->setPid($pid);
                $newRecord->setType($type);
                $newRecord->setRoot($values['root']);
                $newRecord->setNamespace($values['namespace']);
                $newRecord->setClass($values['class']);
                $this->formatRepository->add($newRecord);

                $doPersist = true;
            }
        }

        // We must persist here, if we changed anything.
        if ($doPersist === true) {
            $this->formatRepository->persistAll();
        }
    }

    /**
     * Adds metadata records including their translations.
     *
     * @access public
     *
     * @param non-negative-int $pid
     *
     * @return void
     */
    public function addMetadata(int $pid): void
    {
        $this->formatRepository->useStoragePid($pid);
        $this->metadataRepository->useStoragePid($pid);
        $siteLanguages = $this->getSiteLanguages($pid);
        $defaultLanguageCode = $siteLanguages[0]->getLocale()->getLanguageCode();

        // load language file in own array
        $metadataLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_metadata.xlf', $defaultLanguageCode);

        $availableFormats = [];
        /** @var Format $insertedFormat */
        foreach ($this->formatRepository->findAll() as $insertedFormat) {
            $availableFormats[$insertedFormat->getRoot()] = $insertedFormat->getUid();
        }

        $defaultWrap = $this->tcaSchemaFactory->get('tx_dlf_metadata')->getField('wrap')->getConfiguration()['default'];

        $data = [];
        foreach ($this->getRecords('metadata') as $indexName => $values) {
            $formatIds = [];

            foreach ($values['format'] as $format) {
                $format['encoded'] = $availableFormats[$format['format_root']];
                unset($format['format_root']);
                $formatIds[] = uniqid('NEW');
                $data['tx_dlf_metadataformat'][end($formatIds)] = $format;
                $data['tx_dlf_metadataformat'][end($formatIds)]['pid'] = $pid;
            }

            $data['tx_dlf_metadata'][uniqid('NEW')] = [
                'pid' => $pid,
                'label' => $this->getLLL('metadata.' . $indexName, $defaultLanguageCode, $metadataLabels),
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

        $metadataIds = Helper::processDatabaseAsAdmin($data, [], true);

        $insertedMetadata = [];
        foreach ($metadataIds as $id => $uid) {
            /** @var Metadata $metadata */
            $metadata = $this->metadataRepository->findByUid($uid);
            // id array contains also ids of formats
            if ($metadata != null) {
                $insertedMetadata[$uid] = $metadata->getIndexName();
            }
        }

        foreach ($siteLanguages as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                // skip default language
                continue;
            }

            $translateData = [];
            foreach ($insertedMetadata as $id => $indexName) {
                $translateData['tx_dlf_metadata'][uniqid('NEW')] = [
                    'pid' => $pid,
                    'sys_language_uid' => $siteLanguage->getLanguageId(),
                    'l18n_parent' => $id,
                    'label' => $this->getLLL('metadata.' . $indexName, $siteLanguage->getLocale()->getLanguageCode(), $metadataLabels),
                ];
            }

            Helper::processDatabaseAsAdmin($translateData);
        }
    }

    /**
     * Adds a Solr core record (and creates the core) if none exists yet.
     *
     * @access public
     *
     * @param non-negative-int $pid
     *
     * @return void
     */
    public function addSolrCore(int $pid): void
    {
        $this->solrCoreRepository->useStoragePid($pid);
        $languageCode = $this->getSiteLanguages($pid)[0]->getLocale()->getLanguageCode();

        // load language file in own array
        $beLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_be.xlf', $languageCode);

        if ($this->solrCoreRepository->findAll()->getFirst() === null) {
            $newRecord = GeneralUtility::makeInstance(SolrCore::class);
            $newRecord->setPid($pid);
            $newRecord->setLabel($this->getLLL('flexform.solrcore', $languageCode, $beLabels) . ' (PID ' . $pid . ')');
            $indexName = Solr::createCore('');
            if (!empty($indexName)) {
                $newRecord->setIndexName($indexName);
                $this->solrCoreRepository->add($newRecord);
                // We must persist here, since we changed something.
                $this->solrCoreRepository->persistAll();
            }
        }
    }

    /**
     * Adds structure records including their translations.
     *
     * @access public
     *
     * @param non-negative-int $pid
     *
     * @return void
     */
    public function addStructures(int $pid): void
    {
        $this->structureRepository->useStoragePid($pid);
        $siteLanguages = $this->getSiteLanguages($pid);
        $defaultLanguageCode = $siteLanguages[0]->getLocale()->getLanguageCode();

        // load language file in own array
        $structureLabels = $this->languageFactory->getParsedData('EXT:dlf/Resources/Private/Language/locallang_structure.xlf', $defaultLanguageCode);

        $data = [];
        foreach ($this->getRecords('structure') as $indexName => $values) {
            $data['tx_dlf_structures'][uniqid('NEW')] = [
                'pid' => $pid,
                'toplevel' => $values['toplevel'],
                'label' => $this->getLLL('structure.' . $indexName, $defaultLanguageCode, $structureLabels),
                'index_name' => $indexName,
                'oai_name' => $values['oai_name'],
                'thumbnail' => 0,
            ];
        }
        $structureIds = Helper::processDatabaseAsAdmin($data, [], true);

        $insertedStructures = [];
        foreach ($structureIds as $id => $uid) {
            /** @var Structure $structure */
            $structure = $this->structureRepository->findByUid($uid);
            $insertedStructures[$uid] = $structure->getIndexName();
        }

        foreach ($siteLanguages as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                // skip default language
                continue;
            }

            $translateData = [];
            foreach ($insertedStructures as $id => $indexName) {
                $translateData['tx_dlf_structures'][uniqid('NEW')] = [
                    'pid' => $pid,
                    'sys_language_uid' => $siteLanguage->getLanguageId(),
                    'l18n_parent' => $id,
                    'label' => $this->getLLL('structure.' . $indexName, $siteLanguage->getLocale()->getLanguageCode(), $structureLabels),
                ];
            }

            Helper::processDatabaseAsAdmin($translateData);
        }
    }

    /**
     * Get records from file for given record type.
     *
     * @access public
     *
     * @param string $recordType
     *
     * @return mixed[]
     */
    public function getRecords(string $recordType): array
    {
        return GeneralUtility::makeInstance(YamlConfigurationProvider::class)->getTenantConfiguration($recordType);
    }

    /**
     * Get all site languages of the site the given page belongs to.
     *
     * @access protected
     *
     * @param non-negative-int $pid
     *
     * @return SiteLanguage[]
     */
    protected function getSiteLanguages(int $pid): array
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($pid);
        } catch (SiteNotFoundException $e) {
            $site = new NullSite();
        }
        return $site->getLanguages();
    }

    /**
     * Get language label for given key and language.
     *
     * @access protected
     *
     * @param string $index
     * @param string $lang
     * @param mixed[] $langArray
     *
     * @return string
     */
    protected function getLLL(string $index, string $lang, array $langArray): string
    {
        if (isset($langArray[$index]) && is_string($langArray[$index])) {
            // Typo3 v14 langArray is structured as [$index] => "translation"
            return $langArray[$index];
        } elseif (isset($langArray[$lang][$index][0]['target'])) {
            // $langArray[$lang] seems to be empty in Typo3 v13
            return $langArray[$lang][$index][0]['target'];
        } elseif (isset($langArray['default'][$index][0]['target'])) {
            // Typo3 v13 langArray is structured as ["default"] => [$index] => [0] => ["target"] => "translation
            return $langArray['default'][$index][0]['target'];
        } else {
            return 'Missing translation for ' . $index;
        }
    }
}
