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
use Kitodo\Dlf\Domain\Model\Metadata;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * @extends Repository<Metadata>
 */
class MetadataRepository extends Repository
{
    const TABLE = 'tx_dlf_metadata';

    /**
     * Finds all collection for the given settings
     *
     * @access public
     *
     * @param array<string, mixed> $settings
     *
     * @return QueryResultInterface<int, Metadata>
     */
    public function findBySettings(array $settings = []): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = [];

        if (isset($settings['is_listed']) && $settings['is_listed']) {
            $constraints[] = $query->equals('is_listed', 1);
        }

        if (isset($settings['is_sortable']) && $settings['is_sortable']) {
            $constraints[] = $query->equals('is_sortable', 1);
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd(...$constraints)
            );
        }

        // order by oai_name
        $query->setOrderings(
            ['sorting' => QueryInterface::ORDER_ASCENDING]
        );

        return $query->execute();
    }

    /**
     * Find metadata subentries.
     *
     * @access public
     *
     * @param int $pid
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function findSubentries(int $pid): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $query = $queryBuilder
            ->select(
                'tx_dlf_subentries_joins.index_name AS index_name',
                'tx_dlf_metadata.index_name AS parent_index_name',
                'tx_dlf_subentries_joins.xpath AS xpath',
                'tx_dlf_subentries_joins.default_value AS default_value'
            )
            ->from(self::TABLE)
            ->innerJoin(
                self::TABLE,
                'tx_dlf_metadataformat',
                'tx_dlf_metadataformat_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_metadataformat_joins.parent_id',
                    self::TABLE . '.uid'
                )
            )
            ->innerJoin(
                'tx_dlf_metadataformat_joins',
                'tx_dlf_metadatasubentries',
                'tx_dlf_subentries_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_subentries_joins.parent_id',
                    'tx_dlf_metadataformat_joins.uid'
                )
            )
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->gt('tx_dlf_metadataformat_joins.subentries', 0),
                $queryBuilder->expr()->eq('tx_dlf_subentries_joins.l18n_parent', 0),
                $queryBuilder->expr()->eq('tx_dlf_subentries_joins.pid', $pid)
            )
            ->orderBy('tx_dlf_subentries_joins.sorting');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Finds all metadata with configured xpath and applicable format.
     *
     * @param int $pid
     * @param string $type
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function findWithFormat(int $pid, string $type): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $query = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                'tx_dlf_metadataformat_joins.xpath AS xpath',
                'tx_dlf_metadataformat_joins.xpath_sorting AS xpath_sorting',
                self::TABLE . '.is_sortable AS is_sortable',
                self::TABLE . '.default_value AS default_value',
                self::TABLE . '.format AS format'
            )
            ->from(self::TABLE)
            ->innerJoin(
                self::TABLE,
                'tx_dlf_metadataformat',
                'tx_dlf_metadataformat_joins',
                $queryBuilder->expr()->eq(
                    'tx_dlf_metadataformat_joins.parent_id',
                    self::TABLE . '.uid'
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
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->eq(self::TABLE . '.l18n_parent', 0),
                $queryBuilder->expr()->eq('tx_dlf_metadataformat_joins.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_formats_joins.type', $queryBuilder->createNamedParameter($type))
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Finds all metadata without a format, but with a default value.
     *
     * @param int $pid
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function findWithoutFormat(int $pid): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $query = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.is_sortable AS is_sortable',
                self::TABLE . '.default_value AS default_value',
                self::TABLE . '.format AS format'
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->eq(self::TABLE . '.l18n_parent', 0),
                $queryBuilder->expr()->eq(self::TABLE . '.format', 0),
                $queryBuilder->expr()->neq(self::TABLE . '.default_value', $queryBuilder->createNamedParameter(''))
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Finds query path for IIIF.
     *
     * @param int $pid
     * @param string $iiifVersion
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function findQueryPath(int $pid, string $iiifVersion): array
    {
        // TODO: Saving / indexing should still work - check!
        $queryBuilder = $this->getQueryBuilder();
        $query = $queryBuilder
            ->select('tx_dlf_metadataformat.xpath AS querypath')
            ->from(self::TABLE)
            ->from('tx_dlf_metadataformat')
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_metadataformat.pid', $pid),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(self::TABLE . '.uid', 'tx_dlf_metadataformat.parent_id'),
                        $queryBuilder->expr()->eq('tx_dlf_metadataformat.encoded', 'tx_dlf_formats.uid'),
                        $queryBuilder->expr()->eq(self::TABLE . '.index_name', $queryBuilder->createNamedParameter('record_id')),
                        $queryBuilder->expr()->eq('tx_dlf_formats.type', $queryBuilder->createNamedParameter($iiifVersion))
                    ),
                    $queryBuilder->expr()->eq(self::TABLE . '.format', 0)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Finds all metadata for IIIF.
     *
     * @param int $pid
     * @param string $iiifVersion
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function findForIiif(int $pid, string $iiifVersion): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $query = $queryBuilder
            ->select(
                self::TABLE . '.index_name AS index_name',
                self::TABLE . '.xpath AS xpath',
                'tx_dlf_metadataformat.xpath_sorting AS xpath_sorting',
                self::TABLE . '.is_sortable AS is_sortable',
                self::TABLE . '.default_value AS default_value',
                self::TABLE . '.format AS format'
            )
            ->from(self::TABLE)
            ->from('tx_dlf_metadataformat')
            ->from('tx_dlf_formats')
            ->where(
                $queryBuilder->expr()->eq(self::TABLE . '.pid', $pid),
                $queryBuilder->expr()->eq('tx_dlf_metadataformat.pid', $pid),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(self::TABLE . '.uid', 'tx_dlf_metadataformat.parent_id'),
                        $queryBuilder->expr()->eq('tx_dlf_metadataformat.encoded', 'tx_dlf_formats.uid'),
                        $queryBuilder->expr()->eq('tx_dlf_formats.type', $queryBuilder->createNamedParameter($iiifVersion))
                    ),
                    $queryBuilder->expr()->eq(self::TABLE . '.format', 0)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get query builder for tx_dlf_metadata table.
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder() : QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);

        // Get hidden records, too.
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);

        return $queryBuilder;
    }
}
