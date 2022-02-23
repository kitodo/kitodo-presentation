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
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class CollectionRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Set the default ordering. This is applied to findAll(), too.
     *
     * @var array
     */
    protected $defaultOrderings = [
        'label' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Finds all collections
     *
     * @param string $uids separated by comma
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllByUids($uids)
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->in('uid', $uids);

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();
    }

    public function getCollectionForMetadata($pages)
    {
        // Get list of collections to show.
        $query = $this->createQuery();

        $query->matching($query->equals('pid', $pages));

        return $query->execute();
    }

    /**
     * Finds all collection for the given settings
     *
     * @param array $settings
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findCollectionsBySettings($settings = [])
    {
        $query = $this->createQuery();

        $constraints = [];

        if ($settings['collections']) {
            $constraints[] = $query->in('uid', GeneralUtility::intExplode(',', $settings['collections']));
        }

        if ($settings['index_name']) {
            $constraints[] = $query->in('index_name', $settings['index_name']);
        }

        // do not find user created collections (used by oai-pmh plugin)
        if (!$settings['show_userdefined']) {
            $constraints[] = $query->equals('fe_cruser_id', 0);
        }

        // do not find collections without oai_name set (used by oai-pmh plugin)
        if ($settings['hideEmptyOaiNames']) {
            $constraints[] = $query->logicalNot($query->equals('oai_name', ''));
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd($constraints)
            );
        }

        // order by oai_name
        $query->setOrderings(
            array('oai_name' => QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    public function getIndexNameForSolr($settings, $set)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        $where = '';
        if (!$settings['show_userdefined']) {
            $where = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        }
        // For SOLR we need the index_name of the collection,
        // For DB Query we need the UID of the collection
        $result = $queryBuilder
            ->select(
                'tx_dlf_collections.index_name AS index_name',
                'tx_dlf_collections.uid AS uid',
                'tx_dlf_collections.index_search as index_query'
            )
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($settings['storagePid'])),
                $queryBuilder->expr()->eq('tx_dlf_collections.oai_name',
                    $queryBuilder->expr()->literal($set)),
                $where,
                Helper::whereExpression('tx_dlf_collections')
            )
            ->setMaxResults(1)
            ->execute();

        return $result;
    }

}
