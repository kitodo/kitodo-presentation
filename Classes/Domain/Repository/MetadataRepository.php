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

namespace Kitodo\Dlf\Domain\Repository;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Domain\Repository\FormatRepository;
use Kitodo\Dlf\Domain\Repository\MetadataFormatRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Metadata repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class MetadataRepository extends Repository
{
    const TABLE = 'tx_dlf_domain_model_metadata';

    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Check for existing metadata configuration.
        $result = $queryBuilder
            ->select(self::TABLE . '.uid AS uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                Helper::whereExpression(self::TABLE)
            )
            ->execute();
        
        return $result;
    }

    public static function findOneAutocompleteByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Check if there are any metadata to suggest.
        $result = $queryBuilder
            ->select(self::TABLE . '.*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.index_autocomplete', 1),
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findListedOrSortedByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Get metadata for lists and sorting.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.wrap AS wrap',
                self::TABLE . '.is_listed AS is_listed',
                self::TABLE . '.is_sortable AS is_sortable'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(self::TABLE . '.is_listed', 1),
                    $queryBuilder->expr()->eq(self::TABLE . '.is_sortable', 1)
                ),
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                Helper::whereExpression(self::TABLE)
            )
            ->orderBy(self::TABLE . '.sorting')
            ->execute();

        return $result;
    }

    public static function findListedIndexConfigurationByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Load index configuration.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.index_tokenized AS index_tokenized',
                self::TABLE . '.index_indexed AS index_indexed'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.is_listed', 1),
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                Helper::whereExpression(self::TABLE)
            )
            ->orderBy(self::TABLE . '.sorting', 'ASC')
            ->execute();
        
        return $result;
    }

    public static function findIndexConfigurationByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Get the metadata indexing options.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.index_tokenized AS index_tokenized',
                self::TABLE . '.index_stored AS index_stored',
                self::TABLE . '.index_indexed AS index_indexed',
                self::TABLE . '.is_sortable AS is_sortable',
                self::TABLE . '.is_facet AS is_facet',
                self::TABLE . '.is_listed AS is_listed',
                self::TABLE . '.index_autocomplete AS index_autocomplete',
                self::TABLE . '.index_boost AS index_boost'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                Helper::whereExpression(self::TABLE)
            )
            ->execute();
        
        return $result;
    }

    public static function findByPidAndFormatType($pid, $iiifVersion) {
        /*
         *  FIXME This will not consistently work because we can not be sure to have the pid at hand. It may miss
         *  if the plugin that actually loads the manifest allows content from other pages.
         *  Up until now the cPid is only set after the document has been initialized. We need it before to
         *  check the configuration.
         *  TODO Saving / indexing should still work - check!
         */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $result = $queryBuilder
            ->select(MetadataFormatRepository::TABLE . '.xpath AS querypath')
            ->from(self::TABLE)
            ->from(MetadataFormatRepository::TABLE)
            ->from(FormatRepository::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(MetadataFormatRepository::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(self::TABLE . '.uid', MetadataFormatRepository::TABLE . '.parent_id'),
                        $queryBuilder->expr()->eq(MetadataFormatRepository::TABLE . '.encoded', FormatRepository::TABLE . '.uid'),
                        $queryBuilder->expr()->eq(self::TABLE . '.index_name', $queryBuilder->createNamedParameter('record_id')),
                        $queryBuilder->expr()->eq(FormatRepository::TABLE . '.type', $queryBuilder->createNamedParameter($iiifVersion)),
                    $queryBuilder->expr()->eq(self::TABLE . '.format', 0)
                    )
                )
            )
            ->execute();

        return $result;
    }

    public static function findByPidAndFormatType2($pid, $iiifVersion) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                MetadataFormatRepository::TABLE . '.xpath AS xpath',
                MetadataFormatRepository::TABLE . '.xpath_sorting AS xpath_sorting',
                self::TABLE . '.is_sortable AS is_sortable',
                self::TABLE . '.default_value AS default_value',
                self::TABLE . '.format AS format'
            )
            ->from(self::TABLE)
            ->from(MetadataFormatRepository::TABLE)
            ->from(FormatRepository::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(MetadataFormatRepository::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(self::TABLE . '.uid', MetadataFormatRepository::TABLE . '.parent_id'),
                        $queryBuilder->expr()->eq(MetadataFormatRepository::TABLE . '.encoded', FormatRepository::TABLE . '.uid'),
                        $queryBuilder->expr()->eq(FormatRepository::TABLE . '.type', $queryBuilder->createNamedParameter($iiifVersion))
                    ),
                    $queryBuilder->expr()->eq(self::TABLE . '.format', 0)
                )
            )
            ->execute();
        
        return $result;
    }

    public static function findByPidWithConfiguredXpathAndFormat($pid, $type) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        // Get all metadata with configured xpath and applicable format first.
        $resultWithFormat = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                MetadataFormatRepository::TABLE . '_joins.xpath AS xpath',
                MetadataFormatRepository::TABLE . '_joins.xpath_sorting AS xpath_sorting',
                self::TABLE . '.is_sortable AS is_sortable',
                self::TABLE . '.default_value AS default_value',
                self::TABLE . '.format AS format'
            )
            ->from(self::TABLE)
            ->innerJoin(
                self::TABLE,
                MetadataFormatRepository::TABLE,
                MetadataFormatRepository::TABLE . '_joins',
                $queryBuilder->expr()->eq(
                    MetadataFormatRepository::TABLE . '_joins.parent_id',
                    self::TABLE . '.uid'
                )
            )
            ->innerJoin(
                MetadataFormatRepository::TABLE . '_joins',
                FormatRepository::TABLE,
                FormatRepository::TABLE . '_joins',
                $queryBuilder->expr()->eq(
                    FormatRepository::TABLE . '_joins.uid',
                    MetadataFormatRepository::TABLE . '_joins.encoded'
                )
            )
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($cPid)),
                $queryBuilder->expr()->eq(self::TABLE . '.l18n_parent', 0),
                $queryBuilder->expr()->eq(MetadataFormatRepository::TABLE . '_joins.pid', intval($cPid)),
                $queryBuilder->expr()->eq(FormatRepository::TABLE . '_joins.type', $queryBuilder->createNamedParameter($type))
            )
            ->execute();

        return $result;
    }

    public static function findByPidWithConfiguredXpathAndDefaultValue($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $resultWithoutFormat = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.is_sortable AS is_sortable',
                self::TABLE . '.default_value AS default_value',
                self::TABLE . '.format AS format'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($cPid)),
                $queryBuilder->expr()->eq(self::TABLE . '.l18n_parent', 0),
                $queryBuilder->expr()->eq(self::TABLE . '.format', 0),
                $queryBuilder->expr()->neq(self::TABLE . '.default_value', $queryBuilder->createNamedParameter(''))
            )
            ->execute();

        return $result;
    }

    public static function findAllIndexedFieldsByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Get all indexed fields.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.index_tokenized AS index_tokenized',
                self::TABLE . '.index_stored AS index_stored'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.index_indexed', 1),
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->in(self::TABLE . '.sys_language_uid', [-1, 0]),
                    $queryBuilder->expr()->eq(self::TABLE . '.l18n_parent', 0)
                ),
                Helper::whereExpression(self::TABLE)
            )
            ->execute();

        return $result;
    }

    public static function findByPidAndLanguage($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.is_listed AS is_listed',
                self::TABLE . '.wrap AS wrap',
                self::TABLE . '.sys_language_uid AS sys_language_uid'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in(self::TABLE . '.sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq(self::TABLE . '.sys_language_uid', $GLOBALS['TSFE']->sys_language_uid)
                    ),
                    $queryBuilder->expr()->eq(self::TABLE . '.l18n_parent', 0)
                ),
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid))
            )
            ->orderBy(self::TABLE . '.sorting')
            ->execute();

        return $result;
    }
}
