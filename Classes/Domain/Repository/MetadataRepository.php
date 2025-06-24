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

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Metadata repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @method int countByPid(int $uid) Count amount of metadata for given PID
 * @method QueryResult findByIsListed(bool $isListed) Get a metadata which is listed or not listed
 * @method QueryResult findByIndexIndexed(bool $indexIndexed) Get a metadata which is indexed or not indexed
 * @method QueryResult findByIsSortable(bool $isSortable) Get a metadata which is sortable or not sortable
 */
class MetadataRepository extends Repository
{
    /**
     * Finds all collection for the given settings
     *
     * @access public
     *
     * @param array $settings
     *
     * @return QueryResultInterface
     */
    public function findBySettings(array $settings = []): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = [];

        if (isset($settings['is_listed']) && $settings['is_listed'] == true) {
            $constraints[] = $query->equals('is_listed', 1);
        }

        if (isset($settings['is_sortable']) && $settings['is_sortable'] == true) {
            $constraints[] = $query->equals('is_sortable', 1);
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd(...$constraints)
            );
        }

        // order by oai_name
        $query->setOrderings(
            array('sorting' => QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

}
