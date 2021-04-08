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
 * Structure repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class StructureRepository extends Repository
{
    const TABLE = 'tx_dlf_domain_model_structure';

    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Check for existing structure configuration.
        $result = $queryBuilder
            ->select(self::TABLE . '.uid AS uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                Helper::whereExpression(self::TABLE)
            )
            ->execute();

        return $result;
    }

    public static function findOneByPidAndIndexName($pid, $indexName) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable(self::TABLE);

        // Get UID for structure type.
        $result = $queryBuilder
            ->select(
                self::TABLE . '.uid AS uid',
                self::TABLE . '.thumbnail AS thumbnail'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', intval($pid)),
                $queryBuilder->expr()->eq(self::TABLE . '.index_name', $queryBuilder->expr()->literal($indexName)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }
}
