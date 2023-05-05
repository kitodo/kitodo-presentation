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
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class MetadataRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Finds all collection for the given settings
     *
     * @param array $settings
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findBySettings($settings = [])
    {
        $query = $this->createQuery();

        $constraints = [];

        if ($settings['is_listed']) {
            $constraints[] = $query->equals('is_listed', 1);
        }

        if ($settings['is_sortable']) {
            $constraints[] = $query->equals('is_sortable', 1);
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd($constraints)
            );
        }

        // order by oai_name
        $query->setOrderings(
            array('sorting' => QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    /**
     * Finds all metadata with configured xpath and applicable format.
     *
     * @param int $pid
     * @param string $type
     *
     * @return array
     */
    public function findWithFormat($pid, $type)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');

        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);

        $query = $queryBuilder
            ->select(
                'tx_dlf_metadata.index_name AS index_name',
                'tx_dlf_metadataformat_joins.xpath AS xpath',
                'tx_dlf_metadataformat_joins.xpath_sorting AS xpath_sorting',
                'tx_dlf_metadata.is_sortable AS is_sortable',
                'tx_dlf_metadata.default_value AS default_value',
                'tx_dlf_metadata.format AS format'
            )
            ->from('tx_dlf_metadata')
            ->innerJoin(
                'tx_dlf_metadata',
                'tx_dlf_metadataformat',
                'tx_dlf_metadataformat_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_metadataformat_joins.parent_id',
                    'tx_dlf_metadata.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_metadataformat_joins',
                'tx_dlf_formats',
                'tx_dlf_formats_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_formats_joins.uid',
                    'tx_dlf_metadataformat_joins.encoded'
                )
            )
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_metadata.l18n_parent', 0),
                $queryBuilder->expr()->eq('tx_dlf_metadataformat_joins.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_formats_joins.type', $queryBuilder->createNamedParameter($type))
            );
        return $query->execute()->fetchAll();
    }

    /**
     * Finds all metadata without a format, but with a default value.
     *
     * @param int $pid
     *
     * @return array
     */
    public function findWithoutFormat($pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');

        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);

        $query = $queryBuilder
            ->select(
                'tx_dlf_metadata.index_name AS index_name',
                'tx_dlf_metadata.is_sortable AS is_sortable',
                'tx_dlf_metadata.default_value AS default_value',
                'tx_dlf_metadata.format AS format'
            )
            ->from('tx_dlf_metadata')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_metadata.l18n_parent', 0),
                $queryBuilder->expr()->eq('tx_dlf_metadata.format', 0),
                $queryBuilder->expr()->neq('tx_dlf_metadata.default_value', $queryBuilder->createNamedParameter(''))
            );

        return $query->execute()->fetchAll();
    }

    /**
     * Finds query path for IIIF.
     *
     * @param int $pid
     * @param string $iiifVersion
     *
     * @return array
     */
    public function findQueryPath($pid, $iiifVersion)
    {
        /*
         *  FIXME This will not consistently work because we can not be sure to have the pid at hand. It may miss
         *  if the plugin that actually loads the manifest allows content from other pages.
         *  Up until now the cPid is only set after the document has been initialized. We need it before to
         *  check the configuration.
         *  TODO Saving / indexing should still work - check!
         */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');

        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);

        $query = $queryBuilder
            ->select('tx_dlf_metadataformat.xpath AS querypath')
            ->from('tx_dlf_metadata')
            ->from('tx_dlf_metadataformat')
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_metadataformat.pid', $pid),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('tx_dlf_metadata.uid', 'tx_dlf_metadataformat.parent_id'),
                        $queryBuilder->expr()->eq('tx_dlf_metadataformat.encoded', 'tx_dlf_formats.uid'),
                        $queryBuilder->expr()->eq('tx_dlf_metadata.index_name', $queryBuilder->createNamedParameter('record_id')),
                        $queryBuilder->expr()->eq('tx_dlf_formats.type', $queryBuilder->createNamedParameter($iiifVersion))
                    ),
                    $queryBuilder->expr()->eq('tx_dlf_metadata.format', 0)
                )
            );

        return $query->execute()->fetchAll();
    }

    /**
     * Finds all metadata for IIIF.
     *
     * @param int $pid
     * @param string $iiifVersion
     *
     * @return array
     */
    public function findForIiif($pid, $iiifVersion)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');

        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);

        $query = $queryBuilder
            ->select(
                'tx_dlf_metadata.index_name AS index_name',
                'tx_dlf_metadataformat.xpath AS xpath',
                'tx_dlf_metadataformat.xpath_sorting AS xpath_sorting',
                'tx_dlf_metadata.is_sortable AS is_sortable',
                'tx_dlf_metadata.default_value AS default_value',
                'tx_dlf_metadata.format AS format'
            )
            ->from('tx_dlf_metadata')
            ->from('tx_dlf_metadataformat')
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_metadataformat.pid', $pid),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('tx_dlf_metadata.uid', 'tx_dlf_metadataformat.parent_id'),
                        $queryBuilder->expr()->eq('tx_dlf_metadataformat.encoded', 'tx_dlf_formats.uid'),
                        $queryBuilder->expr()->eq('tx_dlf_formats.type', $queryBuilder->createNamedParameter($iiifVersion))
                    ),
                    $queryBuilder->expr()->eq('tx_dlf_metadata.format', 0)
                )
            );
        
        return $query->execute()->fetchAll();
    }
}
