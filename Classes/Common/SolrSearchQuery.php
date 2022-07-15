<?php

namespace Kitodo\Dlf\Common;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Targeted towards being used in ``PaginateController`` (``<f:widget.paginate>``).
 */
class SolrSearchQuery implements QueryInterface
{
    public function __construct($solrSearch)
    {
        $this->solrSearch = $solrSearch;

        $this->offset = 0;
        $this->limit = count($solrSearch);
    }

    public function getSource() {}

    public function execute($returnRawQueryResult = false)
    {
        $result = [];
        $end = min($this->offset + $this->limit, count($this->solrSearch));
        for ($i = $this->offset; $i < $end; $i++) {
            $result[] = $this->solrSearch[$i];
        }
        return $result;
    }

    public function setOrderings(array $orderings) {}

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function matching($constraint) {}
    public function logicalAnd($constraint1) {}
    public function logicalOr($constraint1) {}
    public function logicalNot(ConstraintInterface $constraint) {}
    public function equals($propertyName, $operand, $caseSensitive = true) {}
    public function like($propertyName, $operand) {}
    public function contains($propertyName, $operand) {}
    public function in($propertyName, $operand) {}
    public function lessThan($propertyName, $operand) {}
    public function lessThanOrEqual($propertyName, $operand) {}
    public function greaterThan($propertyName, $operand) {}
    public function greaterThanOrEqual($propertyName, $operand) {}
    public function getType() {}
    public function setQuerySettings(QuerySettingsInterface $querySettings) {}
    public function getQuerySettings() {}

    public function count()
    {
        // TODO?
    }

    public function getOrderings() {}

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getConstraint() {}
    public function isEmpty($propertyName) {}
    public function setSource(SourceInterface $source) {}
    public function getStatement() {}
}
