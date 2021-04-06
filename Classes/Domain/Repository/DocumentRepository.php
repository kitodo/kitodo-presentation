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
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
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
    const TABLE = 'tx_dlf_domain_model_document';

    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    self::TABLE . '.pid',
                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                )
            )
            ->orderBy(self::TABLE . '.uid', 'ASC')
            ->execute();

        return $result;
    }

    public static function findByPidAndUid($pid, $uidArray) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
        ->select(
            self::TABLE . '.uid AS uid',
            self::TABLE . '.metadata_sorting AS metadata_sorting',
            self::TABLE . '.volume_sorting AS volume_sorting',
            self::TABLE . '.partof AS partof'
        )
        ->from(self::TABLE)
        ->where(
            $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
            $queryBuilder->expr()->in(self::TABLE . '.uid', $uidArray),
            Helper::whereExpression(self::TABLE)
        )
        ->execute();
            
        return $result;
    }

    public static function findByPidAndCollections($pid, $collectionIds) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(self::TABLE .'.uid')
            ->from(self::TABLE)
            ->join(
                self::TABLE,
                'tx_dlf_domain_model_relation',
                'tx_dlf_domain_model_relation_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_domain_model_relation_joins.uid_local',
                    self::TABLE . '.uid'
                )
            )
            ->join(
                'tx_dlf_domain_model_relation_joins',
                CollectionRepository::TABLE,
                CollectionRepository::TABLE . '_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_domain_model_relation_joins.uid_foreign',
                    CollectionRepository::TABLE . '_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in(
                        CollectionRepository::TABLE . '_join.uid',
                        $queryBuilder->createNamedParameter(
                            GeneralUtility::intExplode(',', $collectionIds, true),
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        CollectionRepository::TABLE . '_join.pid',
                        $queryBuilder->createNamedParameter((int) $pid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tx_dlf_domain_model_relation_joins.ident',
                        $queryBuilder->createNamedParameter('docs_colls')
                    )
                )
            )
            ->groupBy(self::TABLE . '.uid')
            ->orderBy(self::TABLE . '.uid', 'ASC')
            ->execute();

        return $result;
    }

    public static function findByPidAndUidWithCollection($cPid, $uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                CollectionRepository::TABLE . '_join.index_name AS index_name'
            )
            ->from(self::TABLE)
            ->innerJoin(
                self::TABLE,
                'tx_dlf_domain_model_relation',
                'tx_dlf_domain_model_relation_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_domain_model_relation_joins.uid_local',
                    self::TABLE . '.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_domain_model_relation_joins',
                CollectionRepository::TABLE,
                CollectionRepository::TABLE . '_join',
                    $queryBuilder->expr()->eq(
                        'tx_dlf_domain_model_relation_joins.uid_foreign',
                        CollectionRepository::TABLE . '_join.uid'
                    )
                )
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($cPid)),
                $queryBuilder->expr()->eq(self::TABLE . '.uid', intval($uid))
            )
            ->orderBy(CollectionRepository::TABLE . '_join.index_name', 'ASC')
            ->execute();

        return $result;
    }

    public static function findByUidOrPartof($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Get document's thumbnail and metadata from database.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS uid',
                self::TABLE . '.thumbnail AS thumbnail',
                self::TABLE . '.metadata AS metadata'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(self::TABLE . '.uid', intval($uid)),
                    $queryBuilder->expr()->eq(self::TABLE . '.partof', intval($uid))
                ),
                Helper::whereExpression(self::TABLE)
            )
            ->execute();
    }

    public static function findByStructureAndPartof($structure, $uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Get all children of year anchor.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS uid',
                self::TABLE . '.title AS title',
                self::TABLE . '.year AS year',
                self::TABLE . '.mets_label AS label',
                self::TABLE . '.mets_orderlabel AS orderlabel'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.structure', $structure),
                $queryBuilder->expr()->eq(self::TABLE . '.partof', intval($uid)),
                Helper::whereExpression(self::TABLE)
            )
            ->orderBy(self::TABLE . '.mets_orderlabel')
            ->execute();

        return $result;
    }

    public static function findOneByUid($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.title AS title',
                self::TABLE . '.partof AS partof',
                self::TABLE . '.location AS location',
                self::TABLE . '.document_format AS document_format',
                self::TABLE . '.record_id AS record_id'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.uid', intval($uid)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(self::TABLE . '.tstamp AS tstamp')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid))
            )
            ->orderBy(self::TABLE . '.tstamp')
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByUidAndPid($uid, $pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.location AS location',
                self::TABLE . '.document_format AS document_format'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.uid', intval($uid)),
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByPidAndRecordId($pid, $recordId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(self::TABLE . '.*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(self::TABLE .'.record_id', $queryBuilder->expr()->literal($recordId))
            )
            ->orderBy(self::TABLE . '.tstamp')
            ->setMaxResults(1)
            ->execute();
            
        return $result;
    }

    public static function findOneByRecordId($recordId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        // Get UID of document with given record identifier.
        $result = $queryBuilder
            ->select(self::TABLE . '.uid AS uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq( self::TABLE . '.record_id', $queryBuilder->expr()->literal($recordId)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();
        
        return $result;
    }

    public static function findForFeeds($pid, $additionalWhere, $limit) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS uid',
                self::TABLE . '.partof AS partof',
                self::TABLE . '.title AS title',
                self::TABLE . '.volume AS volume',
                self::TABLE . '.author AS author',
                self::TABLE . '.record_id AS guid',
                self::TABLE . '.tstamp AS tstamp',
                self::TABLE . '.crdate AS crdate'
            )
            ->from(self::TABLE)
            ->join(
                self::TABLE,
                'tx_dlf_domain_model_relation',
                //TODO: what is the correct name for this table?
                'tx_dlf_documents_collections_mm',
                $queryBuilder->expr()->eq(self::TABLE . '.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_local'))
            )
            ->join(
                'tx_dlf_documents_collections_mm',
                CollectionRepository::TABLE,
                CollectionRepository::TABLE,
                $queryBuilder->expr()->eq(CollectionRepository::TABLE . '.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $queryBuilder->createNamedParameter((int)$pid)),
                $queryBuilder->expr()->eq('tx_dlf_documents_collections_mm.ident', $queryBuilder->createNamedParameter('docs_colls')),
                $queryBuilder->expr()->eq(CollectionRepository::TABLE . '.pid', $queryBuilder->createNamedParameter((int)$pid)),
                $additionalWhere,
                Helper::whereExpression(self::TABLE),
                Helper::whereExpression(CollectionRepository::TABLE),
            )
            ->groupBy(self::TABLE . '.uid')
            ->orderBy(self::TABLE . '.tstamp', 'DESC')
            ->setMaxResults((int)$limit)
            ->execute();

        return $result;
    }

    public static function findByValues($values) {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        $sql = 'SELECT `'. self::TABLE .'`.*, GROUP_CONCAT(DISTINCT `'. CollectionRepository::TABLE .'`.`oai_name` ORDER BY `'. CollectionRepository::TABLE .'`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `'. self::TABLE .'` ' .
            'INNER JOIN `tx_dlf_domain_model_relation` ON `tx_dlf_domain_model_relation`.`uid_local` = `'. self::TABLE .'`.`uid` ' .
            'INNER JOIN `'. CollectionRepository::TABLE .'` ON `'. CollectionRepository::TABLE .'`.`uid` = `tx_dlf_domain_model_relation`.`uid_foreign` ' .
            'WHERE `'. self::TABLE .'`.`uid` IN ( ? ) ' .
            'AND `'. self::TABLE .'`.`pid` = ? ' .
            'AND `'. CollectionRepository::TABLE .'`.`pid` = ? ' .
            'AND `tx_dlf_domain_model_relation`.`ident`="docs_colls" ' .
            'AND ' . Helper::whereExpression(CollectionRepository::TABLE) . ' ' .
            'GROUP BY `'. self::TABLE .'`.`uid` ' .
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

    public static function countTitlesWithSelectedCollections($pid, $collections) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        
        $countTitles = $queryBuilder
            ->count(self::TABLE . '.uid')
            ->from(self::TABLE)
            ->innerJoin(
                self::TABLE,
                'tx_dlf_domain_model_relation',
                'tx_dlf_domain_model_relation_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_domain_model_relation_joins.uid_local',
                    self::TABLE . '.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_domain_model_relation_joins',
                CollectionRepository::TABLE,
                CollectionRepository::TABLE . '_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_domain_model_relation_joins.uid_foreign',
                    CollectionRepository::TABLE . '_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(CollectionRepository::TABLE . '_join.pid', intval($pid)),
                $queryBuilder->expr()->eq(self::TABLE . '.partof', 0),
                $queryBuilder->expr()->in(CollectionRepository::TABLE . '_join.uid', $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $collections), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('tx_dlf_domain_model_relation_joins.ident', $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);

        return $countTitles;
    }

    public static function countVolumesWithSelectedCollections($pid, $collections) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $subQuery = $subQueryBuilder
            ->select(self::TABLE . '.partof')
            ->from(self::TABLE)
            ->where(
                $subQueryBuilder->expr()->neq(self::TABLE . '.partof', 0)
            )
            ->groupBy(self::TABLE . '.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count(self::TABLE . '.uid')
            ->from(self::TABLE)
            ->innerJoin(
                self::TABLE,
                'tx_dlf_domain_model_relation',
                'tx_dlf_domain_model_relation_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_domain_model_relation_joins.uid_local',
                    self::TABLE . '.uid'
                )
            )
            ->innerJoin(
                CollectionRepository::TABLE . '_joins',
                CollectionRepository::TABLE,
                CollectionRepository::TABLE . '_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_domain_model_relation_joins.uid_foreign',
                    CollectionRepository::TABLE . '_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(CollectionRepository::TABLE . '_join.pid', intval($pid)),
                $queryBuilder->expr()->notIn(self::TABLE . '.uid', $subQuery),
                $queryBuilder->expr()->in(CollectionRepository::TABLE . '_join.uid', $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $collections), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('tx_dlf_domain_model_relation_joins.ident', $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);
            
        return $countVolumes;
    }

    public static function countTitles($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        
        $countTitles = $queryBuilder
            ->count(self::TABLE . '.uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(self::TABLE . '.partof', 0),
                Helper::whereExpression(self::TABLE)
            )
            ->execute()
            ->fetchColumn(0);

        return $countTitles;
    }

    public static function countVolumes($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $subQuery = $subQueryBuilder
            ->select(self::TABLE . '.partof')
            ->from(self::TABLE)
            ->where(
                $subQueryBuilder->expr()->neq(self::TABLE . '.partof', 0)
            )
            ->groupBy(self::TABLE . '.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count(self::TABLE . '.uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->notIn(self::TABLE . '.uid', $subQuery)
            )
            ->execute()
            ->fetchColumn(0);
            
        return $countVolumes;
    }

    public static function findForTableOfContents($uid, $pid, $excludeOtherWhere) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

        // Check if there are any metadata to suggest.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS uid',
                self::TABLE . '.title AS title',
                self::TABLE . '.volume AS volume',
                self::TABLE . '.mets_label AS mets_label',
                self::TABLE . '.mets_orderlabel AS mets_orderlabel',
                StructureRepository::TABLE . '_join.index_name AS type'
            )
            ->innerJoin(
                self::TABLE,
                StructureRepository::TABLE,
                StructureRepository::TABLE . '_join',
                $queryBuilder->expr()->eq(
                    StructureRepository::TABLE . '_join.uid',
                    self::TABLE . '.structure'
                )
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.partof', intval($uid)),
                $queryBuilder->expr()->eq(StructureRepository::TABLE . '_join.pid', intval($pid)),
                $excludeOtherWhere
            )
            ->addOrderBy(self::TABLE . '.volume_sorting')
            ->addOrderBy(self::TABLE . '.mets_orderlabel')
            ->execute();

        return $result;
    }
}
