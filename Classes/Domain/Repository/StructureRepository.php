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
    //TODO: replace all static methods after real repository is implemented

    public static function findByPid($pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$structure);

        // Check for existing structure configuration.
        $result = $queryBuilder
            ->select('uid')
            ->from(Table::$structure)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($pid)),
                Helper::whereExpression(Table::$structure)
            )
            ->execute();

        return $result;
    }

    public static function findOneByPidAndIndexName($pid, $indexName) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable(Table::$structure);

        // Get UID for structure type.
        $result = $queryBuilder
            ->select(
                'uid',
                'thumbnail'
            )
            ->from(Table::$structure)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($pid)),
                $queryBuilder->expr()->eq('index_name', $queryBuilder->expr()->literal($indexName)),
                Helper::whereExpression(Table::$structure)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }
}
