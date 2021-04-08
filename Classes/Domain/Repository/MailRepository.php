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
 * Mail repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class MailRepository extends Repository
{
    const TABLE = 'tx_dlf_domain_model_mail';

    //TODO: replace all static methods after real repository is implemented

    public static function findAll() {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // get mail addresses
        $resultMail = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                '1=1',
                Helper::whereExpression(self::TABLE)
            )
            ->orderBy(self::TABLE . '.sorting')
            ->execute();

        return $resultMail;
    }

    public static function findOneByUid($uid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // get id from db and send selected doc download link
        $resultMail = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.uid', intval($uid)),
                Helper::whereExpression(self::TABLE)
            )
            ->setMaxResults(1)
            ->execute();
        
        return $resultMail;
    }
}
