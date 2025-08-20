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

use Doctrine\DBAL\Result;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Domain\Model\Collection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Collection repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @method Collection|null findOneBy(array $criteria) Get a collection by criteria
 */
class CollectionRepository extends Repository
{
    /**
     * @access protected
     * @var array Set the default ordering. This is applied to findAll(), too.
     */
    // TODO: PHPDoc type array of property CollectionRepository::$defaultOrderings is not covariant with 'ASC'|'DESC'> of overridden property TYPO3\CMS\Extbase\Persistence\Repository::$defaultOrderings.
    // @phpstan-ignore-next-line
    protected $defaultOrderings = [
        'label' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Finds all collections
     *
     * @access public
     *
     * @param array $uids
     *
     * @return QueryResultInterface
     */
    public function findAllByUids(array $uids): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->in('uid', $uids);

        if (count($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        return $query->execute();
    }

    /**
     * Finds all collection for the given settings
     *
     * @access public
     *
     * @param array $settings
     *
     * @return QueryResultInterface
     */
    public function findCollectionsBySettings(array $settings = []): QueryResultInterface
    {
        $query = $this->createQuery();

        $constraints = [];

        if ($settings['collections'] ?? false) {
            $constraints[] = $query->in('uid', GeneralUtility::intExplode(',', $settings['collections']));
        }

        if ($settings['index_name'] ?? false) {
            $constraints[] = $query->in('index_name', $settings['index_name']);
        }

        // do not find user created collections (used by oai-pmh plugin)
        if (!($settings['show_userdefined'] ?? false)) {
            $constraints[] = $query->equals('fe_cruser_id', 0);
        }

        // do not find collections without oai_name set (used by oai-pmh plugin)
        if ($settings['hideEmptyOaiNames'] ?? false) {
            $constraints[] = $query->logicalNot($query->equals('oai_name', ''));
        }

        if (count($constraints)) {
            $query->matching(
                $query->logicalAnd(...$constraints)
            );
        }

        // order by oai_name
        $query->setOrderings(
            array('oai_name' => QueryInterface::ORDER_ASCENDING)
        );

        return $query->execute();
    }

    /**
     * Gets index name for SOLR
     *
     * @access public
     *
     * @param array $settings
     * @param mixed $set
     *
     * @return Result
     */
    public function getIndexNameForSolr(array $settings, $set): Result
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_collections');

        $where = '';
        if (!$settings['show_userdefined']) {
            $where = $queryBuilder->expr()->eq('fe_cruser_id', 0);
        }
        // For SOLR we need the index_name of the collection,
        // For DB Query we need the UID of the collection
        $result = $queryBuilder
            ->select(
                'index_name',
                'uid',
                'index_search as index_query'
            )
            ->from('tx_dlf_collections')
            ->where(
                $queryBuilder->expr()->eq('pid', intval($settings['storagePid'])),
                $queryBuilder->expr()->eq('oai_name',
                    $queryBuilder->expr()->literal($set)),
                $where,
                Helper::whereExpression('tx_dlf_collections')
            )
            ->setMaxResults(1);

        return $result->executeQuery();
    }

}
