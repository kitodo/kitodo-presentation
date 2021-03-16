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
    const TABLE = 'tx_dlf_domain_model_collection';

    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        // Get UIDs for collections.
        $result = $queryBuilder
                ->select('tx_dlf_collections.index_name AS index_name')
                ->from('tx_dlf_collections')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_collections.pid', $pid)
                )
                ->execute();

        return $result;
    }
    
    public static function findByPidAndLanguage($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        // Get UIDs for collections.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.uid AS uid'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->in(self::TABLE .'.sys_language_uid', [-1, 0]),
                Helper::whereExpression(self::TABLE)
            )
            ->execute();

        return $result;
    }

    public static function findByPidAndUser($pid, $withoutUser) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        $where = '';
        if ($withoutUser) {
            $where = $queryBuilder->expr()->eq(self::TABLE . '.fe_cruser_id', 0);
        }

        $result = $queryBuilder
            ->select(
                self::TABLE . '.oai_name AS oai_name',
                self::TABLE . '.label AS label'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->in(self::TABLE . '.sys_language_uid', [-1, 0]),
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->neq(self::TABLE . '.oai_name', $queryBuilder->createNamedParameter('')),
                $where,
                Helper::whereExpression(self::TABLE)
            )
            ->orderBy(self::TABLE . '.oai_name')
            ->execute();

        return $result;
    }

    public static function findByPidAndOaiNAmeAndUser($pid, $oaiName, $withoutUser) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $where = '';
        if ($withoutUser) {
            $where = $queryBuilder->expr()->eq(self::TABLE . '.fe_cruser_id', 0);
        }

        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.uid AS uid',
                self::TABLE . '.index_search as index_query'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->eq(self::TABLE . '.oai_name', $queryBuilder->expr()->literal($oaiName)),
                $where,
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findByUidArray($uidArray) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(self::TABLE . '.index_name AS index_name')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->in(
                    self::TABLE . '.uid',
                    $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $uidArray, Connection::PARAM_INT_ARRAY))
                )
            )
            ->execute();

        return $result;
    }
}
