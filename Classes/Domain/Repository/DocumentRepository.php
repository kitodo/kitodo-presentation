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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class DocumentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function findByUidAndPartOf($uid, $partOf)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('uid', $uid));
        $query->matching($query->equals('partof', $partOf));

        return $query->execute();
    }

    /**
     * Find the oldest document
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findOldestDocument()
    {
        $query = $this->createQuery();

        $query->setOrderings(['tstamp' => QueryInterface::ORDER_ASCENDING]);
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @param $structure
     * @param $partOf
     * @param $indexName
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function getChildrenOfYearAnchor($structure, $partOf, $indexName)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('structure', Helper::getUidFromIndexName($indexName, 'tx_dlf_structures', $structure)));
        $query->matching($query->equals('partof', $partOf));

        $query->setOrderings([
            'mets_orderlabel' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
        ]);

        return $query->execute();
    }

    public function getDocumentsFromDocumentset($documentSet, $pages) {
        $query = $this->createQuery();

        $query->matching($query->equals('pid', $pages));
        $query->matching($query->in('uid', $documentSet));

        return $query->execute();
    }

    public function getDocumentsForFeeds($settings, $collectionUid) {
        $additionalWhere = '';
        // Check for pre-selected collections.
        if (!empty($collectionUid)) {
            $additionalWhere = 'tx_dlf_collections.uid=' . intval($collectionUid);
        } elseif (!empty($settings['collections'])) {
            $additionalWhere = 'tx_dlf_collections.uid IN (' . implode(',', GeneralUtility::intExplode(',', $settings['collections'])) . ')';
        }

        // get documents
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $result = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.partof AS partof',
                'tx_dlf_documents.title AS title',
                'tx_dlf_documents.volume AS volume',
                'tx_dlf_documents.author AS author',
                'tx_dlf_documents.record_id AS record_id',
                'tx_dlf_documents.tstamp AS tstamp',
                'tx_dlf_documents.crdate AS crdate'
            )
            ->from('tx_dlf_documents')
            ->join(
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_documents_collections_mm',
                $queryBuilder->expr()->eq('tx_dlf_documents.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_local'))
            )
            ->join(
                'tx_dlf_documents_collections_mm',
                'tx_dlf_collections',
                'tx_dlf_collections',
                $queryBuilder->expr()->eq('tx_dlf_collections.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', $queryBuilder->createNamedParameter((int) $settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_documents_collections_mm.ident', $queryBuilder->createNamedParameter('docs_colls')),
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', $queryBuilder->createNamedParameter((int) $settings['pages'])),
                $additionalWhere
            )
            ->groupBy('tx_dlf_documents.uid')
            ->orderBy('tx_dlf_documents.tstamp', 'DESC')
            ->setMaxResults((int) $settings['limit'])
            ->execute();

        return $result;

    }

    public function getStatisticsForCollection($settings) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        // Include all collections.
        $countTitles = $queryBuilder
            ->count('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', 0),
                Helper::whereExpression('tx_dlf_documents')
            )
            ->execute()
            ->fetchColumn(0);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $subQuery = $subQueryBuilder
            ->select('tx_dlf_documents.partof')
            ->from('tx_dlf_documents')
            ->where(
                $subQueryBuilder->expr()->neq('tx_dlf_documents.partof', 0)
            )
            ->groupBy('tx_dlf_documents.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($settings['pages'])),
                $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery)
            )
            ->execute()
            ->fetchColumn(0);

        return ['titles' => $countTitles, 'volumes' => $countVolumes];
    }

    public function getStatisticsForSelectedCollection($settings) {
        // Include only selected collections.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $countTitles = $queryBuilder
            ->count('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->innerJoin(
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_relations_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_local',
                    'tx_dlf_documents.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_relations_joins',
                'tx_dlf_collections',
                'tx_dlf_collections_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_foreign',
                    'tx_dlf_collections_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', 0),
                $queryBuilder->expr()->in('tx_dlf_collections_join.uid',
                    $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',',
                        $settings['collections']), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident',
                    $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');
        $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $subQuery = $subQueryBuilder
            ->select('tx_dlf_documents.partof')
            ->from('tx_dlf_documents')
            ->where(
                $subQueryBuilder->expr()->neq('tx_dlf_documents.partof', 0)
            )
            ->groupBy('tx_dlf_documents.partof')
            ->getSQL();

        $countVolumes = $queryBuilder
            ->count('tx_dlf_documents.uid')
            ->from('tx_dlf_documents')
            ->innerJoin(
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_relations_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_local',
                    'tx_dlf_documents.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_relations_joins',
                'tx_dlf_collections',
                'tx_dlf_collections_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_relations_joins.uid_foreign',
                    'tx_dlf_collections_join.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($settings['pages'])),
                $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery),
                $queryBuilder->expr()->in('tx_dlf_collections_join.uid',
                    $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',',
                        $settings['collections']), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident',
                    $queryBuilder->createNamedParameter('docs_colls'))
            )
            ->execute()
            ->fetchColumn(0);

        return ['titles' => $countTitles, 'volumes' => $countVolumes];
    }

    public function getTableOfContentsFromDb($uid, $pid, $settings) {
        // Build table of contents from database.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        $excludeOtherWhere = '';
        if ($settings['excludeOther']) {
            $excludeOtherWhere = 'tx_dlf_documents.pid=' . intval($settings['pages']);
        }
        // Check if there are any metadata to suggest.
        $result = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.title AS title',
                'tx_dlf_documents.volume AS volume',
                'tx_dlf_documents.mets_label AS mets_label',
                'tx_dlf_documents.mets_orderlabel AS mets_orderlabel',
                'tx_dlf_structures_join.index_name AS type'
            )
            ->innerJoin(
                'tx_dlf_documents',
                'tx_dlf_structures',
                'tx_dlf_structures_join',
                $queryBuilder->expr()->eq(
                    'tx_dlf_structures_join.uid',
                    'tx_dlf_documents.structure'
                )
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($uid)),
                $queryBuilder->expr()->eq('tx_dlf_structures_join.pid', intval($pid)),
                $excludeOtherWhere
            )
            ->addOrderBy('tx_dlf_documents.volume_sorting')
            ->addOrderBy('tx_dlf_documents.mets_orderlabel')
            ->execute();
        return $result;
    }

    /**
     * Find one document by given settings and identifier
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return array The found document object
     */
    public function getOaiRecord($settings, $parameters)
    {
        $where = '';

        if (!$settings['show_userdefined']) {
            $where .= 'AND tx_dlf_collections.fe_cruser_id=0 ';
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_documents');

        $sql = 'SELECT `tx_dlf_documents`.*, GROUP_CONCAT(DISTINCT `tx_dlf_collections`.`oai_name` ORDER BY `tx_dlf_collections`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `tx_dlf_documents` ' .
            'INNER JOIN `tx_dlf_relations` ON `tx_dlf_relations`.`uid_local` = `tx_dlf_documents`.`uid` ' .
            'INNER JOIN `tx_dlf_collections` ON `tx_dlf_collections`.`uid` = `tx_dlf_relations`.`uid_foreign` ' .
            'WHERE `tx_dlf_documents`.`record_id` = ? ' .
            'AND `tx_dlf_relations`.`ident`="docs_colls" ' .
            $where;

        $values = [
            $parameters['identifier']
        ];

        $types = [
            Connection::PARAM_STR
        ];

        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        $statement = $connection->executeQuery($sql, $values, $types);

        return $statement->fetch();
    }

    /**
     * Finds all documents for the given settings
     *
     * @param array $settings
     * @param array $documentsToProcess
     *
     * @return array The found document objects
     */
    public function getOaiDocumentList($settings, $documentsToProcess)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_documents');

        $sql = 'SELECT `tx_dlf_documents`.*, GROUP_CONCAT(DISTINCT `tx_dlf_collections`.`oai_name` ORDER BY `tx_dlf_collections`.`oai_name` SEPARATOR " ") AS `collections` ' .
            'FROM `tx_dlf_documents` ' .
            'INNER JOIN `tx_dlf_relations` ON `tx_dlf_relations`.`uid_local` = `tx_dlf_documents`.`uid` ' .
            'INNER JOIN `tx_dlf_collections` ON `tx_dlf_collections`.`uid` = `tx_dlf_relations`.`uid_foreign` ' .
            'WHERE `tx_dlf_documents`.`uid` IN ( ? ) ' .
            'AND `tx_dlf_relations`.`ident`="docs_colls" ' .
            'AND ' . Helper::whereExpression('tx_dlf_collections') . ' ' .
            'GROUP BY `tx_dlf_documents`.`uid` ';

        $values = [
            $documentsToProcess,
        ];

        $types = [
            Connection::PARAM_INT_ARRAY,
        ];

        // Create a prepared statement for the passed SQL query, bind the given params with their binding types and execute the query
        $documents = $connection->executeQuery($sql, $values, $types);

        return $documents;
    }

}
