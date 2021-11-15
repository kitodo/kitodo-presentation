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
use Kitodo\Dlf\Common\Helper;

class CollectionRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function getCollectionList(array $uids, $showUserDefined = 0)
    {
        $query = $this->createQuery();

        if (!empty($uids)) {
            $constraints = [];
            // selected collections
            foreach ($uids as $uid) {
                $constraints[] = $query->contains('uid', $uid);
            }
            $query->matching($query->logicalOr($constraints));
        }

        $query->matching($query->equals('fe_cruser_id', $showUserDefined));

        $query->setOrderings([
            'label' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
        ]);

    }

    public function getCollections($settings, $uid, $sysLangUid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        $selectedCollections = $queryBuilder->expr()->neq('tx_dlf_collections.uid', 0);
        $orderBy = 'tx_dlf_collections.label';
        $showUserDefinedColls = '';
        // Handle collections set by configuration.
        if ($settings['collections']) {
            if (
                count(explode(',', $settings['collections'])) == 1
                && empty($settings['dont_show_single'])
            ) {
                $this->showSingleCollection(intval(trim($settings['collections'], ' ,')));
            }
            $selectedCollections = $queryBuilder->expr()->in('tx_dlf_collections.uid', implode(',', GeneralUtility::intExplode(',', $settings['collections'])));
        }

        // Should user-defined collections be shown?
        if (empty($settings['show_userdefined'])) {
            $showUserDefinedColls = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        } elseif ($settings['show_userdefined'] > 0) {
            if (!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
                $showUserDefinedColls = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', intval($uid));
            } else {
                $showUserDefinedColls = $queryBuilder->expr()->neq('tx_dlf_collections.fe_cruser_id', 0);
            }
        }

        // Get collections.
        $queryBuilder
            ->select(
                'tx_dlf_collections.uid AS uid', // required by getRecordOverlay()
                'tx_dlf_collections.pid AS pid', // required by getRecordOverlay()
                'tx_dlf_collections.sys_language_uid AS sys_language_uid', // required by getRecordOverlay()
                'tx_dlf_collections.index_name AS index_name',
                'tx_dlf_collections.index_search as index_query',
                'tx_dlf_collections.label AS label',
                'tx_dlf_collections.thumbnail AS thumbnail',
                'tx_dlf_collections.description AS description',
                'tx_dlf_collections.priority AS priority'
            )
            ->from('tx_dlf_collections')
            ->where(
                $selectedCollections,
                $showUserDefinedColls,
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($settings['pages'])),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('tx_dlf_collections.sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('tx_dlf_collections.sys_language_uid', $sysLangUid)
                    ),
                    $queryBuilder->expr()->eq('tx_dlf_collections.l18n_parent', 0)
                )
            )
            ->orderBy($orderBy);

        $result = $queryBuilder->execute();
        $count = $queryBuilder->count('uid')->execute()->fetchColumn(0);

        return ['result' => $result, 'count' => $count];
    }

    public function getSingleCollection($settings, $id, $sysLangUid) {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_dlf_collections');

        $additionalWhere = '';
        // Should user-defined collections be shown?
        if (empty($settings['show_userdefined'])) {
            $additionalWhere = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        } elseif ($settings['show_userdefined'] > 0) {
            $additionalWhere = $queryBuilder->expr()->neq('tx_dlf_collections.fe_cruser_id', 0);
        }

        // Get collection information from DB
        $collection = $queryBuilder
            ->select(
                'tx_dlf_collections.uid AS uid', // required by getRecordOverlay()
                'tx_dlf_collections.pid AS pid', // required by getRecordOverlay()
                'tx_dlf_collections.sys_language_uid AS sys_language_uid', // required by getRecordOverlay()
                'tx_dlf_collections.index_name AS index_name',
                'tx_dlf_collections.index_search as index_search',
                'tx_dlf_collections.label AS label',
                'tx_dlf_collections.description AS description',
                'tx_dlf_collections.thumbnail AS thumbnail',
                'tx_dlf_collections.fe_cruser_id'
            )
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($settings['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_collections.uid', intval($id)),
                $additionalWhere,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->in('tx_dlf_collections.sys_language_uid', [-1, 0]),
                        $queryBuilder->expr()->eq('tx_dlf_collections.sys_language_uid', $sysLangUid)
                    ),
                    $queryBuilder->expr()->eq('tx_dlf_collections.l18n_parent', 0)
                ),
                Helper::whereExpression('tx_dlf_collections')
            )
            ->setMaxResults(1)
            ->execute();

        return $collection;
    }

    public function getCollectionForMetadata($pages) {
        // Get list of collections to show.
        $query = $this->createQuery();

        $query->matching($query->equals('pid', $pages));

        return $query->execute();
    }

    public function getFacetCollections($facetCollections) {
        $query = $this->createQuery();

        $query->matching($query->in('uid', GeneralUtility::intExplode(',', $facetCollections)));

        return $query->execute();
    }

    public function getOai1($settings) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        $where = '';
        if (!$settings['show_userdefined']) {
            $where = $queryBuilder->expr()->eq('tx_dlf_collections.fe_cruser_id', 0);
        }

        $result = $queryBuilder
            ->select(
                'tx_dlf_collections.oai_name AS oai_name',
                'tx_dlf_collections.label AS label'
            )
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()->in('tx_dlf_collections.sys_language_uid', [-1, 0]),
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($settings['pages'])),
                $queryBuilder->expr()->neq('tx_dlf_collections.oai_name', $queryBuilder->createNamedParameter('')),
                $where,
                Helper::whereExpression('tx_dlf_collections')
            )
            ->orderBy('tx_dlf_collections.oai_name')
            ->execute();

        $allResults = $result->fetchAll();

        return $allResults;
    }

    public function getIndexNameForSolr($settings, $set) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        $solr_query = '';
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
                $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($settings['pages'])),
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