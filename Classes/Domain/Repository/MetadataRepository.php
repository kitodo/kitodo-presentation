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
use Kitodo\Dlf\Domain\Table;
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
    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);

        // Check for existing metadata configuration.
        $result = $queryBuilder
            ->select('uid')
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($pid)),
                Helper::whereExpression(Table::$metadata)
            )
            ->execute();
        
        return $result;
    }

    public static function findOneAutocompleteByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);

        // Check if there are any metadata to suggest.
        $result = $queryBuilder
            ->select('*')
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->eq('index_autocomplete', 1),
                $queryBuilder->expr()->eq('pid', intval($pid)),
                Helper::whereExpression(Table::$metadata)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findListedOrSortedByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);

        // Get metadata for lists and sorting.
        $result = $queryBuilder
            ->select(
                'index_name',
                'wrap',
                'is_listed',
                'is_sortable'
            )
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('is_listed', 1),
                    $queryBuilder->expr()->eq('is_sortable', 1)
                ),
                $queryBuilder->expr()->eq('pid', intval($pid)),
                Helper::whereExpression(Table::$metadata)
            )
            ->orderBy('sorting')
            ->execute();

        return $result;
    }

    public static function findListedIndexConfigurationByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);

        // Load index configuration.
        $result = $queryBuilder
            ->select(
                'index_name',
                'index_tokenized',
                'index_indexed'
            )
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->eq('is_listed', 1),
                $queryBuilder->expr()->eq('pid', intval($pid)),
                Helper::whereExpression(Table::$metadata)
            )
            ->orderBy('sorting', 'ASC')
            ->execute();
        
        return $result;
    }

    public static function findIndexConfigurationByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);

        // Get the metadata indexing options.
        $result = $queryBuilder
            ->select(
                'index_name',
                'index_tokenized',
                'index_stored',
                'index_indexed',
                'is_sortable',
                'is_facet',
                'is_listed',
                'index_autocomplete',
                'index_boost'
            )
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($pid)),
                Helper::whereExpression(Table::$metadata)
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
            ->getQueryBuilderForTable(Table::$metadata);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $result = $queryBuilder
            ->select(Table::$metadataFormat . '.xpath AS querypath')
            ->from(Table::$metadata)
            ->from(Table::$metadataFormat)
            ->from(Table::$format)
            ->where(
                $queryBuilder->expr()->eq(Table::$metadata . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(Table::$metadataFormat . '.pid', intval($pid)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(Table::$metadata . '.uid', Table::$metadataFormat . '.parent_id'),
                        $queryBuilder->expr()->eq(Table::$metadataFormat . '.encoded', Table::$formatRepository . '.uid'),
                        $queryBuilder->expr()->eq(Table::$metadata . '.index_name', $queryBuilder->createNamedParameter('record_id')),
                        $queryBuilder->expr()->eq(Table::$format . '.type', $queryBuilder->createNamedParameter($iiifVersion)),
                    $queryBuilder->expr()->eq(Table::$metadata . '.format', 0)
                    )
                )
            )
            ->execute();

        return $result;
    }

    public static function findByPidAndFormatType2($pid, $iiifVersion) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $result = $queryBuilder
            ->select(
                Table::$metadata . '.index_name AS index_name',
                Table::$metadataFormat . '.xpath AS xpath',
                Table::$metadataFormat . '.xpath_sorting AS xpath_sorting',
                Table::$metadata . '.is_sortable AS is_sortable',
                Table::$metadata . '.default_value AS default_value',
                Table::$metadata . '.format AS format'
            )
            ->from(Table::$metadata)
            ->from(Table::$metadataFormat)
            ->from(Table::$format)
            ->where(
                $queryBuilder->expr()->eq(Table::$metadata . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(Table::$metadataFormat . '.pid', intval($pid)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(Table::$metadata . '.uid', Table::$metadataFormat . '.parent_id'),
                        $queryBuilder->expr()->eq(Table::$metadataFormat . '.encoded', Table::$format . '.uid'),
                        $queryBuilder->expr()->eq(Table::$format . '.type', $queryBuilder->createNamedParameter($iiifVersion))
                    ),
                    $queryBuilder->expr()->eq(Table::$metadata . '.format', 0)
                )
            )
            ->execute();
        
        return $result;
    }

    public static function findByPidWithConfiguredXpathAndFormat($pid, $type) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        // Get all metadata with configured xpath and applicable format first.
        $resultWithFormat = $queryBuilder
            ->select(
                Table::$metadata . '.index_name AS index_name',
                Table::$metadataFormat . '_joins.xpath AS xpath',
                Table::$metadataFormat . '_joins.xpath_sorting AS xpath_sorting',
                Table::$metadata . '.is_sortable AS is_sortable',
                Table::$metadata . '.default_value AS default_value',
                Table::$metadata . '.format AS format'
            )
            ->from(Table::$metadata)
            ->innerJoin(
                Table::$metadata,
                Table::$metadataFormat,
                Table::$metadataFormat . '_joins',
                $queryBuilder->expr()->eq(
                    Table::$metadataFormat . '_joins.parent_id',
                    Table::$metadata . '.uid'
                )
            )
            ->innerJoin(
                Table::$metadataFormat . '_joins',
                Table::$format,
                Table::$format . '_joins',
                $queryBuilder->expr()->eq(
                    Table::$format . '_joins.uid',
                    Table::$metadataFormat . '_joins.encoded'
                )
            )
            ->where(
                $queryBuilder->expr()->eq(Table::$metadata . '.pid', intval($cPid)),
                $queryBuilder->expr()->eq(Table::$metadata . '.l18n_parent', 0),
                $queryBuilder->expr()->eq(Table::$metadataFormat . '_joins.pid', intval($cPid)),
                $queryBuilder->expr()->eq(Table::$format . '_joins.type', $queryBuilder->createNamedParameter($type))
            )
            ->execute();

        return $result;
    }

    public static function findByPidWithConfiguredXpathAndDefaultValue($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);
        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $resultWithoutFormat = $queryBuilder
            ->select(
                'index_name',
                'is_sortable',
                'default_value',
                'format'
            )
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($cPid)),
                $queryBuilder->expr()->eq('l18n_parent', 0),
                $queryBuilder->expr()->eq('format', 0),
                $queryBuilder->expr()->neq('default_value', $queryBuilder->createNamedParameter(''))
            )
            ->execute();

        return $result;
    }

    public static function findAllIndexedFieldsByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);

        // Get all indexed fields.
        $result = $queryBuilder
            ->select(
                'index_name',
                'index_tokenized',
                'index_stored'
            )
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->eq('index_indexed', 1),
                $queryBuilder->expr()->eq('pid', intval($pid)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                    $queryBuilder->expr()->eq('l18n_parent', 0)
                ),
                Helper::whereExpression(Table::$metadata)
            )
            ->execute();

        return $result;
    }

    public static function findByPidAndLanguage($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$metadata);

        $result = $queryBuilder
            ->select(
                'index_name',
                'is_listed',
                'wrap',
                'sys_language_uid'
            )
            ->from(Table::$metadata)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('sys_language_uid', $GLOBALS['TSFE']->sys_language_uid)
                    ),
                    $queryBuilder->expr()->eq('l18n_parent', 0)
                ),
                $queryBuilder->expr()->eq('pid', intval($pid))
            )
            ->orderBy('sorting')
            ->execute();

        return $result;
    }
}
