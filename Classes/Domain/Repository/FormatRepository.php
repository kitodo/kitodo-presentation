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

use Doctrine\DBAL\Exception;
use Kitodo\Dlf\Domain\Model\Format;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Format repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @extends AbstractRepository<Format>
 */
class FormatRepository extends AbstractRepository
{

    /**
     * Find all formats by given pid.
     *
     * @access public
     *
     * @param int $pid
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function findByPid(int $pid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_formats');

        // Get available data formats from database.
        return $queryBuilder
            ->select(
                'type',
                'root',
                'namespace',
                'class'
            )
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->eq('pid', $pid)
            )
            ->executeQuery()->fetchAllAssociative();
    }
}
