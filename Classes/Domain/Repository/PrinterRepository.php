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
 * Printer repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class PrinterRepository extends Repository
{
    //TODO: replace all static methods after real repository is implemented

    public static function findAll() {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$printer);

        // get mail addresses
        $resultPrinter = $queryBuilder
            ->select('*')
            ->from(Table::$printer)
            ->where(
                '1=1',
                Helper::whereExpression(Table::$printer)
            )
            ->execute();

        return $result;
    }

    public static function findOneByUid($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$printer);

        // get id from db and send selected doc download link
        $result = $queryBuilder
            ->select('*')
            ->from(Table::$printer)
            ->where(
                $queryBuilder->expr()->eq('uid', intval($uid)),
                Helper::whereExpression(Table::$printer)
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }
}
