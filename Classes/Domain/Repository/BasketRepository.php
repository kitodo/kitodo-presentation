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
 * Basket repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class BasketRepository extends Repository
{

    const TABLE = 'tx_dlf_domain_model_basket';

    //TODO: replace all static methods after real repository is implemented

    public static function findByFrontendUser($feUserId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        return $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder
                    ->expr()
                    ->eq(self::TABLE . '.fe_user_id', $feUserId),
                    Helper::whereExpression(self::TABLE)
                )
            ->setMaxResults(1)
            ->execute();
    }

    /**
     * Find basket by session id
     *
     * @static
     * @access public
     *
     * @return array ?
     */
    public static function findBySessionId($sessionId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        return $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder
                    ->expr()
                    ->eq(self::TABLE . '.session_id', $queryBuilder->createNamedParameter($sessionId)),
                    Helper::whereExpression(self::TABLE)
                )
            ->setMaxResults(1)
            ->execute();
    }

    /**
     * Insert baskets
     *
     * @static
     * @access public
     *
     * @return array
     */
    public static function insert($insertArray) {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->insert(self::TABLE, $insertArray);
    }

    /**
     * Update baskets
     *
     * @static
     * @access public
     *
     * @return array
     */
    public static function update($update, $uid) {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->update(
                self::TABLE,
                $update,
                ['uid' => $uid]
            );
    }

}
