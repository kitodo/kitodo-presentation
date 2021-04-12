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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Document repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class DocumentRepository extends Repository
{
    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select('uid')
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(
                    Table::$document . '.pid',
                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                )
            )
            ->orderBy(Table::$document . '.uid', 'ASC')
            ->execute();

        return $result;
    }

    public static function findByPidAndUid($pid, $uidArray) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
        ->select(
            Table::$document . '.uid AS uid',
            Table::$document . '.metadata_sorting AS metadata_sorting',
            Table::$document . '.volume_sorting AS volume_sorting',
            Table::$document . '.partof AS partof'
        )
        ->from(Table::$document)
        ->where(
            $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid)),
            $queryBuilder->expr()->in(Table::$document . '.uid', $uidArray),
            Helper::whereExpression(Table::$document)
        )
        ->execute();
            
        return $result;
    }

    public static function findByPidAndCollections($pid, $collectionIds) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select(Table::$document .'.uid')
            ->from(Table::$document)
            ->join(
                Table::$document,
                Table::$relation,
                Table::$relation . '_joins',
                $queryBuilder->expr()->eq(
                    Table::$relation . '_joins.uid_local',
                    Table::$document . '.uid'
                )
            )
            ->join(
                Table::$relation . '_joins',
                Table::$collection,
                Table::$collection . '_join',
                $queryBuilder->expr()->eq(
                    Table::$relation . '_joins.uid_foreign',
                    Table::$collection . '_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in(
                        Table::$collection . '_join.uid',
                        $queryBuilder->createNamedParameter(
                            GeneralUtility::intExplode(',', $collectionIds, true),
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        Table::$collection . '_join.pid',
                        $queryBuilder->createNamedParameter((int) $pid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        Table::$relation . '_joins.ident',
                        $queryBuilder->createNamedParameter('docs_colls')
                    )
                )
            )
            ->groupBy(Table::$document . '.uid')
            ->orderBy(Table::$document . '.uid', 'ASC')
            ->execute();

        return $result;
    }

    public static function findByPidAndUidWithCollection($cPid, $uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select(
                Table::$collection . '_join.index_name AS index_name'
            )
            ->from(Table::$document)
            ->innerJoin(
                Table::$document,
                Table::$relation,
                Table::$relation . '_joins',
                $queryBuilder->expr()->eq(
                    Table::$relation . '_joins.uid_local',
                    Table::$document . '.uid'
                )
            )
            ->innerJoin(
                Table::$relation . '_joins',
                Table::$collection,
                Table::$collection . '_join',
                    $queryBuilder->expr()->eq(
                        Table::$relation . '_joins.uid_foreign',
                        Table::$collection . '_join.uid'
                    )
                )
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($cPid)),
                $queryBuilder->expr()->eq(Table::$document . '.uid', intval($uid))
            )
            ->orderBy(Table::$collection . '_join.index_name', 'ASC')
            ->execute();

        return $result;
    }

    public static function findByUidOrPartof($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        // Get document's thumbnail and metadata from database.
        $result = $queryBuilder
            ->select(
                Table::$document . '.uid AS uid',
                Table::$document . '.thumbnail AS thumbnail',
                Table::$document . '.metadata AS metadata'
            )
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(Table::$document . '.uid', intval($uid)),
                    $queryBuilder->expr()->eq(Table::$document . '.partof', intval($uid))
                ),
                Helper::whereExpression(Table::$document)
            )
            ->execute();
    }

    public static function findByStructureAndPartof($structure, $uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        // Get all children of year anchor.
        $result = $queryBuilder
            ->select(
                Table::$document . '.uid AS uid',
                Table::$document . '.title AS title',
                Table::$document . '.year AS year',
                Table::$document . '.mets_label AS label',
                Table::$document . '.mets_orderlabel AS orderlabel'
            )
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.structure', $structure),
                $queryBuilder->expr()->eq(Table::$document . '.partof', intval($uid)),
                Helper::whereExpression(Table::$document)
            )
            ->orderBy(Table::$document . '.mets_orderlabel')
            ->execute();

        return $result;
    }

    public static function findOneByWhereClause($whereClause) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        $queryBuilder
            ->select(
                Table::$document . '.uid AS uid',
                Table::$document . '.pid AS pid',
                Table::$document . '.record_id AS record_id',
                Table::$document . '.partof AS partof',
                Table::$document . '.thumbnail AS thumbnail',
                Table::$document . '.location AS location'
            )
            ->from(Table::$document)
            ->where($whereClause)
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findOneByUid($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select(
                Table::$document . '.title AS title',
                Table::$document . '.partof AS partof',
                Table::$document . '.location AS location',
                Table::$document . '.document_format AS document_format',
                Table::$document . '.record_id AS record_id'
            )
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.uid', intval($uid)),
                Helper::whereExpression(Table::$document)
            )
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select(Table::$document . '.tstamp AS tstamp')
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid))
            )
            ->orderBy(Table::$document . '.tstamp')
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByUidAndPid($uid, $pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select(
                Table::$document . '.location AS location',
                Table::$document . '.document_format AS document_format'
            )
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.uid', intval($uid)),
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid)),
                Helper::whereExpression(Table::$document)
            )
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByPidAndRecordId($pid, $recordId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select(Table::$document . '.*')
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(Table::$document .'.record_id', $queryBuilder->expr()->literal($recordId))
            )
            ->orderBy(Table::$document . '.tstamp')
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByRecordId($recordId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$document);

        // Get UID of document with given record identifier.
        $result = $queryBuilder
            ->select(Table::$document . '.uid AS uid')
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.record_id', $queryBuilder->expr()->literal($recordId)),
                Helper::whereExpression(Table::$document)
            )
            ->setMaxResults(1)
            ->execute();
        
        return $result;
    }

    public static function findForFeeds($pid, $additionalWhere, $limit) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        $result = $queryBuilder
            ->select(
                Table::$document . '.uid AS uid',
                Table::$document . '.partof AS partof',
                Table::$document . '.title AS title',
                Table::$document . '.volume AS volume',
                Table::$document . '.author AS author',
                Table::$document . '.record_id AS guid',
                Table::$document . '.tstamp AS tstamp',
                Table::$document . '.crdate AS crdate'
            )
            ->from(Table::$document)
            ->join(
                Table::$document,
                Table::$relation,
                //TODO: what is the correct name for this table?
                'tx_dlf_documents_collections_mm',
                $queryBuilder->expr()->eq(Table::$document . '.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_local'))
            )
            ->join(
                'tx_dlf_documents_collections_mm',
                //TODO: check out why 2 times the same table
                Table::$collection,
                Table::$collection,
                $queryBuilder->expr()->eq(Table::$collection . '.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', $queryBuilder->createNamedParameter((int)$pid)),
                $queryBuilder->expr()->eq('tx_dlf_documents_collections_mm.ident', $queryBuilder->createNamedParameter('docs_colls')),
                $queryBuilder->expr()->eq(Table::$collection . '.pid', $queryBuilder->createNamedParameter((int)$pid)),
                $additionalWhere,
                Helper::whereExpression(Table::$document),
                Helper::whereExpression(Table::$collection),
            )
            ->groupBy(Table::$document . '.uid')
            ->orderBy(Table::$document . '.tstamp', 'DESC')
            ->setMaxResults((int)$limit)
            ->execute();

        return $result;
    }

    public static function findByValues($values) {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Table::$document);

        $sql = 'SELECT `'. Table::$document .'`.*, GROUP_CONCAT(DISTINCT `'. Table::$collection .'`.`oai_name` ORDER BY `'. Table::$collection .'`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `'. Table::$document .'` ' .
            'INNER JOIN `tx_dlf_domain_model_relation` ON `tx_dlf_domain_model_relation`.`uid_local` = `'. Table::$document .'`.`uid` ' .
            'INNER JOIN `'. Table::$collection .'` ON `'. Table::$collection .'`.`uid` = `tx_dlf_domain_model_relation`.`uid_foreign` ' .
            'WHERE `'. Table::$document .'`.`uid` IN ( ? ) ' .
            'AND `'. Table::$document .'`.`pid` = ? ' .
            'AND `'. Table::$collection .'`.`pid` = ? ' .
            'AND `tx_dlf_domain_model_relation`.`ident`="docs_colls" ' .
            'AND ' . Helper::whereExpression(Table::$collection) . ' ' .
            'GROUP BY `'. Table::$document .'`.`uid` ' .
            'LIMIT ?';

        $types = [
            Connection::PARAM_INT_ARRAY,
            Connection::PARAM_INT,
            Connection::PARAM_INT,
            Connection::PARAM_INT
        ];
        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        return $connection->executeQuery($sql, $values, $types);
    }

    public static function findByValuesAndAdditionalWhere($values, $where) {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Table::$document);

        $sql = 'SELECT `'. Table::$document .'`.*, GROUP_CONCAT(DISTINCT `' . Table::$collection . '`.`oai_name` ORDER BY `' . Table::$collection . 'tions`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `'. Table::$document .'` ' .
            'INNER JOIN `tx_dlf_domain_model_relation` ON `tx_dlf_domain_model_relation`.`uid_local` = `'. Table::$document .'`.`uid` ' .
            'INNER JOIN `' . Table::$collection . '` ON `' . Table::$collection . '`.`uid` = `tx_dlf_domain_model_relation`.`uid_foreign` ' .
            'WHERE `'. Table::$document .'`.`record_id` = ? ' .
            'AND `'. Table::$document .'`.`pid` = ? ' .
            'AND `' . Table::$collection . '`.`pid` = ? ' .
            'AND `tx_dlf_domain_model_relation`.`ident`="docs_colls" ' .
            $where .
            'AND ' . Helper::whereExpression(Table::$collection);

        $types = [
            Connection::PARAM_STR,
            Connection::PARAM_INT,
            Connection::PARAM_INT
        ];
        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        return $connection->executeQuery($sql, $values, $types);
    }

    public static function countTitlesWithSelectedCollections($pid, $collections) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);
        
        $countTitles = $queryBuilder
            ->count(Table::$document . '.uid')
            ->from(Table::$document)
            ->innerJoin(
                Table::$document,
                Table::$relation,
                Table::$relation . '_joins',
                $queryBuilder->expr()->eq(
                    Table::$relation . '_joins.uid_local',
                    Table::$document . '.uid'
                )
            )
            ->innerJoin(
                Table::$relation . '_joins',
                Table::$collection,
                Table::$collection . '_join',
                $queryBuilder->expr()->eq(
                    Table::$relation . '_joins.uid_foreign',
                    Table::$collection . '_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(Table::$collection . '_join.pid', intval($pid)),
                $queryBuilder->expr()->eq(Table::$document . '.partof', 0),
                $queryBuilder->expr()->in(Table::$collection . '_join.uid', $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $collections), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('tx_dlf_domain_model_relation_joins.ident', $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);

        return $countTitles;
    }

    public static function countVolumesWithSelectedCollections($pid, $collections) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        $subQuery = $subQueryBuilder
            ->select(Table::$document . '.partof')
            ->from(Table::$document)
            ->where(
                $subQueryBuilder->expr()->neq(Table::$document . '.partof', 0)
            )
            ->groupBy(Table::$document . '.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count(Table::$document . '.uid')
            ->from(Table::$document)
            ->innerJoin(
                Table::$document,
                Table::$relation,
                Table::$relation . '_joins',
                $queryBuilder->expr()->eq(
                    Table::$relation . '_joins.uid_local',
                    Table::$document . '.uid'
                )
            )
            ->innerJoin(
                Table::$collection . '_joins',
                Table::$collection,
                Table::$collection . '_join',
                $queryBuilder->expr()->eq(
                    Table::$relation . '_joins.uid_foreign',
                    Table::$collection . '_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(Table::$collection . '_join.pid', intval($pid)),
                $queryBuilder->expr()->notIn(Table::$document . '.uid', $subQuery),
                $queryBuilder->expr()->in(Table::$collection . '_join.uid', $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $collections), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq(Table::$relation . '_joins.ident', $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);
            
        return $countVolumes;
    }

    public static function countTitles($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);
        
        $countTitles = $queryBuilder
            ->count(Table::$document . '.uid')
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(Table::$document . '.partof', 0),
                Helper::whereExpression(Table::$document)
            )
            ->execute()
            ->fetchColumn(0);

        return $countTitles;
    }

    public static function countVolumes($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$document);

        $subQuery = $subQueryBuilder
            ->select(Table::$document . '.partof')
            ->from(Table::$document)
            ->where(
                $subQueryBuilder->expr()->neq(Table::$document . '.partof', 0)
            )
            ->groupBy(Table::$document . '.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count(Table::$document . '.uid')
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.pid', intval($pid)),
                $queryBuilder->expr()->notIn(Table::$document . '.uid', $subQuery)
            )
            ->execute()
            ->fetchColumn(0);
            
        return $countVolumes;
    }

    public static function findForTableOfContents($uid, $pid, $excludeOtherWhere) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Table::$document);

        // Check if there are any metadata to suggest.
        $result = $queryBuilder
            ->select(
                Table::$document . '.uid AS uid',
                Table::$document . '.title AS title',
                Table::$document . '.volume AS volume',
                Table::$document . '.mets_label AS mets_label',
                Table::$document . '.mets_orderlabel AS mets_orderlabel',
                Table::$structure . '_join.index_name AS type'
            )
            ->innerJoin(
                Table::$document,
                Table::$structure,
                Table::$structure . '_join',
                $queryBuilder->expr()->eq(
                    Table::$structure . '_join.uid',
                    Table::$document . '.structure'
                )
            )
            ->from(Table::$document)
            ->where(
                $queryBuilder->expr()->eq(Table::$document . '.partof', intval($uid)),
                $queryBuilder->expr()->eq(Table::$structure . '_join.pid', intval($pid)),
                $excludeOtherWhere
            )
            ->addOrderBy(Table::$document . '.volume_sorting')
            ->addOrderBy(Table::$document . '.mets_orderlabel')
            ->execute();

        return $result;
    }
}
