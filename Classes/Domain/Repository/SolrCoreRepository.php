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
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
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
    const TABLE = 'tx_dlf_domain_model_solrcore';

    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                'uid',
                'index_name'
            )
            ->from(self::TABLE)
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
            ->getQueryBuilderForTable(self::TABLE);

        // Check for existing Solr core.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS uid',
                self::TABLE . '.pid AS pid'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->in(self::TABLE . '.pid', [intval($pid), 0]),
                Helper::whereExpression(self::TABLE)
            )
            ->execute();

        return $result;
    }

    public static function findOneByDocumentId($documentId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS core',
                self::TABLE . '.index_name',
                DocumentRepository::TABLE . '_join.hidden AS hidden'
            )
            ->innerJoin(
                self::TABLE,
                DocumentRepository::TABLE,
                DocumentRepository::TABLE . '_join',
                $queryBuilder->expr()->eq(
                    DocumentRepository::TABLE . '_join.solrcore',
                    self::TABLE . '.uid'
                )
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    DocumentRepository::TABLE . '_join.uid',
                    intval($documentId)
                )
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findOneDeletedByDocumentId($documentId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        // Record in "tx_dlf_documents" is already deleted at this point.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(DeletedRestriction::class);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS core'
            )
            ->innerJoin(
                self::TABLE,
                DocumentRepository::TABLE,
                DocumentRepository::TABLE . '_join',
                $queryBuilder->expr()->eq(
                    DocumentRepository::TABLE . '_join.solrcore',
                    self::TABLE . '.uid'
                )
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    DocumentRepository::TABLE . '_join.uid',
                    intval($documentId)
                )
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findOneDeletedByUid($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        // Record in "tx_dlf_solrcores" is already deleted at this point.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(DeletedRestriction::class);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS core'
            )
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq(self::TABLE . '.uid', intval($uid)))
            ->setMaxResults(1)
            ->execute();

        return $result;
    }
}
