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

use Kitodo\Dlf\Domain\Model\Collection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 *
 * @extends AbstractRepository<Collection>
 */
class CollectionRepository extends AbstractRepository
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
     * Finds all collections for given uids
     *
     * @access public
     *
     * @param int[] $uids
     *
     * @return QueryResultInterface<int, Collection>
     */
    public function findAllByUids(array $uids): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->in('uid', $uids));
        return $query->execute();
    }

    /**
     * Finds all collection for the given settings
     *
     * @access public
     *
     * @param mixed[] $settings
     *
     * @return QueryResultInterface<int, Collection>
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
        if (!($settings['showUserDefined'] ?? false)) {
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

        $this->debugQuery($query);

        return $query->execute();
    }

    /**
     * Gets index name for SOLR
     *
     * @access public
     *
     * @param mixed[] $settings
     * @param mixed $set
     *
     * @return array<string,string> The found row as associative array with keys
     *                              'index_name' and 'index_query' or an empty
     *                              array when not found.
     */
    public function getIndexNameForSolr(array $settings, mixed $set): array
    {
        $query = $this->createQuery();

        $constraints = [];
        // Match by oai_name
        $constraints[] = $query->equals('oaiName', (string) $set);

        // Exclude user defined collections when requested
        if (!($settings['showUserDefined'] ?? false)) {
            $constraints[] = $query->equals('feCruserId', 0);
        }

        if (count($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        $query->setLimit(1);

        $this->debugQuery($query);

        $collection = $query->execute()->getFirst();
        if ($collection === null) {
            return [];
        }

        return [
            'index_name' => $collection->getIndexName(),
            'index_query' => $collection->getIndexSearch()
        ];
    }

}
