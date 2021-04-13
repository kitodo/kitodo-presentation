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
    //TODO: replace all static methods after real repository is implemented

    public static function findOneByPidAndIndexName($pid, $owner) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$library);

        $result = $queryBuilder
            ->select('uid')
            ->from(Table::$library)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($pid)),
                $queryBuilder->expr()->eq('index_name', $queryBuilder->expr()->literal($owner)),
                Helper::whereExpression(Table::$library)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

    public static function findOneByPidAndUid($pid, $uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$library);

        $result = $queryBuilder
            ->select(
                'label',
                'oai_label',
                'contact'
            )
            ->from(Table::$library)
            ->where(
                $queryBuilder->expr()->eq('pid', intval($pid)),
                $queryBuilder->expr()->eq('uid', intval($uid)),
                Helper::whereExpression(Table::$library)
            )
            ->setMaxResults(1)
            ->execute();
        
        return $result;
    }
}
