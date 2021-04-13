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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Collection repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class CollectionRepository extends Repository
{
    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$collection);

        // Get UIDs for collections.
        $result = $queryBuilder
                ->select('index_name')
                ->from(Table::$collection)
                ->where(
                    $queryBuilder->expr()->eq('pid', $pid)
                )
                ->execute();

        return $result;
    }
    
    public static function findByPidAndLanguage($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$collection);

        // Get UIDs for collections.
        $result = $queryBuilder
            ->select(
                'index_name',
                'uid'
            )
            ->from(Table::$collection)
            ->where(
                $queryBuilder->expr()->eq('pid', $pid),
                $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                Helper::whereExpression(Table::$collection)
            )
            ->execute();

        return $result;
    }

    public static function findByPidAndUser($pid, $withoutUser) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$collection);

        $where = '';
        if ($withoutUser) {
            $where = $queryBuilder->expr()->eq('fe_cruser_id', 0);
        }

        $result = $queryBuilder
            ->select(
                'oai_name',
                'label'
            )
            ->from(Table::$collection)
            ->where(
                $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                $queryBuilder->expr()->eq('pid', $pid),
                $queryBuilder->expr()->neq('oai_name', $queryBuilder->createNamedParameter('')),
                $where,
                Helper::whereExpression(Table::$collection)
            )
            ->orderBy('oai_name')
            ->execute();

        return $result;
    }

    public static function findByPidAndOaiNAmeAndUser($pid, $oaiName, $withoutUser) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$collection);

        $where = '';
        if ($withoutUser) {
            $where = $queryBuilder->expr()->eq('fe_cruser_id', 0);
        }

        $result = $queryBuilder
            ->select(
                'index_name',
                'uid',
                'index_search AS index_query'
            )
            ->from(Table::$collection)
            ->where(
                $queryBuilder->expr()->eq('pid', $pid),
                $queryBuilder->expr()->eq('oai_name', $queryBuilder->expr()->literal($oaiName)),
                $where,
                Helper::whereExpression(Table::$collection)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findByUidArray($uidArray) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$collection);

        $result = $queryBuilder
            ->select('index_name')
            ->from(Table::$collection)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $uidArray, Connection::PARAM_INT_ARRAY))
                )
            )
            ->execute();

        return $result;
    }

    public static function findByPidAndLanguageAndAdditionalWhereClause($pid, $selectedCollections, $showUserDefinedCollections) {
        // Get collections.
        $result = getQueryBuilderWithPidAndWhereClauses()->execute($pid, $selectedCollections, $showUserDefinedCollections);

        return $result;
    }

    public static function countByPidAndLanguageAndAdditionalWhereClause($pid, $selectedCollections, $showUserDefinedCollections) {
        // Get collections.
        $result = getQueryBuilderWithPidAndWhereClauses()->count('uid')->execute($pid, $selectedCollections, $showUserDefinedCollections)->fetchColumn(0);

        return $result;
    }

    public static function getQueryBuilderWithPidAndWhereClauses($pid, $selectedCollections, $showUserDefinedCollections) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$collection);

        return $queryBuilder
            ->select(
                'uid', // required by getRecordOverlay()
                'pid', // required by getRecordOverlay()
                'sys_language_uid', // required by getRecordOverlay()
                'index_name',
                // TODO: check if this is correct
                'index_search AS index_query',
                'label',
                'thumbnail',
                'description',
                'priority'
            )
            ->from(Table::$collection)
            ->where(
                $selectedCollections,
                $showUserDefinedCollections,
                $queryBuilder->expr()->eq('pid', intval($pid)),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('sys_language_uid', $GLOBALS['TSFE']->sys_language_uid)
                    ),
                    $queryBuilder->expr()->eq('l18n_parent', 0)
                )
            )
            ->orderBy('label');
    }

    public static function findOneByPidAndUidAndLanguageWithAddtionalWhereClause($pid, $uid, $additionalWhere) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$collection);

        $result = $queryBuilder
            ->select(
                'uid', // required by getRecordOverlay()
                'pid', // required by getRecordOverlay()
                'sys_language_uid', // required by getRecordOverlay()
                'index_name',
                'index_search',
                'label',
                'description',
                'thumbnail',
                'fe_cruser_id'
            )
            ->from(Table::$collection)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($pid)),
                $queryBuilder->expr()->eq('uid', intval($uid)),
                $additionalWhere,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('sys_language_uid', $GLOBALS['TSFE']->sys_language_uid)
                    ),
                    $queryBuilder->expr()->eq('l18n_parent', 0)
                ),
                Helper::whereExpression(Table::$collection)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }
}
