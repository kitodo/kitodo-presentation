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
 * Library repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class LibraryRepository extends Repository
{
    const TABLE = 'tx_dlf_domain_model_library';

    //TODO: replace all static methods after real repository is implemented

    public static function findOneByPidAndIndexName($pid, $owner) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(self::TABLE . '.uid AS uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(self::TABLE . '.index_name', $queryBuilder->expr()->literal($owner)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findOneByPidAndUid($pid, $uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        $result = $queryBuilder
            ->select(
                self::TABLE . '.label AS label',
                self::TABLE . '.oai_label AS oai_label',
                self::TABLE . '.contact AS contact'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(self::TABLE . '.uid', intval($uid)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();
        
        return $result;
    }
}
