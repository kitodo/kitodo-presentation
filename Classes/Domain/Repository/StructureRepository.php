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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

class StructureRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Finds structure element to get thumbnail from.
     *
     * @param int $cPid
     * @param string $type
     *
     * @return array
     */
    public function findThumbnail($cPid, $type)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_structures');

        $query = $queryBuilder
            ->select('tx_dlf_structures.thumbnail AS thumbnail')
            ->from('tx_dlf_structures')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_structures.pid', $cPid),
                $queryBuilder->expr()->eq(
                    'tx_dlf_structures.index_name',
                    $queryBuilder->expr()->literal($type)
                ),
                Helper::whereExpression('tx_dlf_structures')
            )
            ->setMaxResults(1);

        return $query->execute()->fetchAllAssociative();
    }
}
