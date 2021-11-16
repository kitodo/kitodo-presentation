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

class LibraryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function getLibraryByUidAndPid($uid, $pid) {
        // Get repository name and administrative contact.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_libraries');

        $result = $queryBuilder
            ->select(
                'tx_dlf_libraries.oai_label AS oai_label',
                'tx_dlf_libraries.contact AS contact'
            )
            ->from('tx_dlf_libraries')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_libraries.pid', intval($pid)),
                $queryBuilder->expr()->eq('tx_dlf_libraries.uid', intval($uid))
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

}
