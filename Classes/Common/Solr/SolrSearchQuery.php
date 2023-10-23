<?php

namespace Kitodo\Dlf\Common\Solr;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Targeted towards being used in ``PaginateController`` (``<f:widget.paginate>``).
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @property int $limit
 * @property int $offset
 */
class SolrSearchQuery implements QueryInterface
{
    /**
     * @access private
     * @var SolrSearch
     */
    private SolrSearch $solrSearch;

    /**
     * @access private
     * @var int
     */
    private int $limit;

    /**
     * @access private
     * @var int
     */
    private int $offset;

     /**
     * Constructs SolrSearchQuery instance.
     *
     * @access public
     *
     * @param SolrSearch $solrSearch
     *
     * @return void
     */
    public function __construct($solrSearch)
    {
        $this->solrSearch = $solrSearch;

        $this->offset = 0;
        $this->limit = count($solrSearch);
    }

    // this class contains a lot of methods which are inherited but not implemented
    // @phpstan-ignore-next-line
    public function getSource() {}

    /**
     * Executes SOLR search query.
     *
     * @access public
     *
     * @param bool $returnRawQueryResult
     *
     * @return array
     */
    // TODO: Return type (array) of method SolrSearchQuery::execute() should be compatible with return type (iterable<object>&TYPO3\CMS\Extbase\Persistence\QueryResultInterface) of method TYPO3\CMS\Extbase\Persistence\QueryInterface::execute()
    // @phpstan-ignore-next-line
    public function execute($returnRawQueryResult = false)
    {
        $this->solrSearch->submit($this->offset, $this->limit);

        // solrSearch now only contains the results in range, indexed in [0, n)
        $result = [];
        $limit = min($this->limit, $this->solrSearch->getNumLoadedDocuments());
        for ($i = 0; $i < $limit; $i++) {
            $result[] = $this->solrSearch[$i];
        }
        return $result;
    }

    // @phpstan-ignore-next-line
    public function setOrderings(array $orderings) {}

    /**
     * Sets limit for SOLR search query.
     *
     * @access public
     *
     * @param int $limit
     *
     * @return SolrSearchQuery
     */
    public function setLimit($limit): SolrSearchQuery
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets offset for SOLR search query.
     *
     * @access public
     *
     * @param int $offset
     *
     * @return SolrSearchQuery
     */
    public function setOffset($offset): SolrSearchQuery
    {
        $this->offset = $offset;
        return $this;
    }

    // @phpstan-ignore-next-line
    public function matching($constraint) {}
    // @phpstan-ignore-next-line
    public function logicalAnd($constraint1) {}
    // @phpstan-ignore-next-line
    public function logicalOr($constraint1) {}
    // @phpstan-ignore-next-line
    public function logicalNot(ConstraintInterface $constraint) {}
    // @phpstan-ignore-next-line
    public function equals($propertyName, $operand, $caseSensitive = true) {}
    // @phpstan-ignore-next-line
    public function like($propertyName, $operand) {}
    // @phpstan-ignore-next-line
    public function contains($propertyName, $operand) {}
    // @phpstan-ignore-next-line
    public function in($propertyName, $operand) {}
    // @phpstan-ignore-next-line
    public function lessThan($propertyName, $operand) {}
    // @phpstan-ignore-next-line
    public function lessThanOrEqual($propertyName, $operand) {}
    // @phpstan-ignore-next-line
    public function greaterThan($propertyName, $operand) {}
    // @phpstan-ignore-next-line
    public function greaterThanOrEqual($propertyName, $operand) {}
    // @phpstan-ignore-next-line
    public function getType() {}
    public function setQuerySettings(QuerySettingsInterface $querySettings) {}
    // @phpstan-ignore-next-line
    public function getQuerySettings() {}

    public function count()
    {// @phpstan-ignore-next-line
        // TODO?
    }

    // @phpstan-ignore-next-line
    public function getOrderings() {}

    /**
     * Gets limit for SOLR search query.
     *
     * @access public
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Gets offset for SOLR search query.
     *
     * @access public
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    // @phpstan-ignore-next-line
    public function getConstraint() {}
    public function isEmpty($propertyName) {}
    public function setSource(SourceInterface $source) {}
    // @phpstan-ignore-next-line
    public function getStatement() {}
}
