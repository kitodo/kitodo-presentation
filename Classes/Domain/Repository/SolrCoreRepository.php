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
 * SOLR Core repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class SolrCoreRepository extends Repository
{
    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$solrCore);

        $result = $queryBuilder
            ->select(
                'uid',
                'index_name'
            )
            ->from(Table::$solrCore)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter((int) $pid, Connection::PARAM_INT)
                )
            )
            ->execute();

        return $result;
    }

    public static function findByPidForNewTenant($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$solrCore);

        // Check for existing Solr core.
        $result = $queryBuilder
            ->select(
                Table::$solrCore . '.uid AS uid',
                Table::$solrCore . '.pid AS pid'
            )
            ->from(Table::$solrCore)
            ->where(
                $queryBuilder->expr()->in(Table::$solrCore . '.pid', [intval($pid), 0]),
                Helper::whereExpression(Table::$solrCore)
            )
            ->execute();

        return $result;
    }

    public static function findOneByDocumentId($documentId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$solrCore);

        $result = $queryBuilder
            ->select(
                Table::$solrCore . '.uid AS core',
                Table::$solrCore . '.index_name',
                Table::$document . '_join.hidden AS hidden'
            )
            ->innerJoin(
                Table::$solrCore,
                Table::$document,
                Table::$document . '_join',
                $queryBuilder->expr()->eq(
                    Table::$document . '_join.solrcore',
                    Table::$solrCore . '.uid'
                )
            )
            ->from(Table::$solrCore)
            ->where(
                $queryBuilder->expr()->eq(
                    Table::$document . '_join.uid',
                    intval($documentId)
                )
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findOneDeletedByDocumentId($documentId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$solrCore);
        // Record in "tx_dlf_documents" is already deleted at this point.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(DeletedRestriction::class);

        $result = $queryBuilder
            ->select(
                Table::$solrCore . '.uid AS core'
            )
            ->innerJoin(
                Table::$solrCore,
                Table::$document,
                Table::$document . '_join',
                $queryBuilder->expr()->eq(
                    Table::$document . '_join.solrcore',
                    Table::$solrCore . '.uid'
                )
            )
            ->from(Table::$solrCore)
            ->where(
                $queryBuilder->expr()->eq(
                    Table::$document . '_join.uid',
                    intval($documentId)
                )
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findOneDeletedByUid($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$solrCore);
        // Record in "tx_dlf_solrcores" is already deleted at this point.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(DeletedRestriction::class);

        $result = $queryBuilder
            ->select(
                Table::$solrCore . '.index_name AS core'
            )
            ->from(Table::$solrCore)
            ->where($queryBuilder->expr()->eq(Table::$solrCore . '.uid', intval($uid)))
            ->setMaxResults(1)
            ->execute();

        return $result;
    }
}
