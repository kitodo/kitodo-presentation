<?php

namespace Kitodo\Dlf\Common\Solr;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;

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
class SolrSearchQuery extends Query
{
    /**
     * @access private
     * @var SolrSearch
     */
    private SolrSearch $solrSearch;

    /**
     * Constructs SolrSearchQuery instance.
     *
     * @access public
     *
     * @param SolrSearch $solrSearch
     *
     * @return void
     */
    public function __construct(SolrSearch $solrSearch)
    {
        $this->solrSearch = $solrSearch;

        $this->offset = 0;
        $this->limit = count($solrSearch);
    }

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

}
