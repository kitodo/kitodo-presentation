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
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Domain\Model\Structure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Structure repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @method Structure|null findOneBy(array $criteria) Get a structure by criteria
 *
 * @extends AbstractRepository<Structure>
 */
class StructureRepository extends AbstractRepository
{
    /**
     * Finds structure element to get thumbnail from.
     *
     * @param int $pid
     * @param string $type
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function findThumbnail(int $pid, string $type): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_structures');

        $query = $queryBuilder
            ->select('tx_dlf_structures.thumbnail AS thumbnail')
            ->from('tx_dlf_structures')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_structures.pid', $pid),
                $queryBuilder->expr()->eq(
                    'tx_dlf_structures.index_name',
                    $queryBuilder->expr()->literal($type)
                ),
                Helper::whereExpression('tx_dlf_structures')
            )
            ->setMaxResults(1);

        return $query->executeQuery()->fetchAllAssociative();
    }
}
