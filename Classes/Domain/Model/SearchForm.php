<?php

namespace Kitodo\Dlf\Domain\Model;

class SearchForm extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $extQuery = [];

    /**
     * @var array
     */
    protected $extOperator = [];

    /**
     * @var array
     */
    protected $extField = [];

    /**
     * @var int
     */
    protected $fulltext;

    /**
     * @var string
     */
    protected $logicalPage = '';

    /**
     * @var int
     */
    protected $collection;

    /**
     * @var int
     */
    protected $documentId;

    /**
     * @var string
     */
    protected $encryptedCoreName;

    /**
     * @var array
     */
    protected $filterQuery = [];

    /**
     * @var string
     */
    protected $order = '';

    /**
     * @var int
     */
    protected $asc;

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    /**
     * @return int
     */
    public function getFulltext(): int
    {
        return $this->fulltext;
    }

    /**
     * @param int $fulltext
     */
    public function setFulltext(int $fulltext): void
    {
        $this->fulltext = $fulltext;
    }

    /**
     * @return array
     */
    public function getExtQuery(): array
    {
        return $this->extQuery;
    }

    /**
     * @param array $extQuery
     */
    public function setExtQuery(array $extQuery): void
    {
        $this->extQuery = $extQuery;
    }

    /**
     * @return array
     */
    public function getExtOperator(): array
    {
        return $this->extOperator;
    }

    /**
     * @param array $extOperator
     */
    public function setExtOperator(array $extOperator): void
    {
        $this->extOperator = $extOperator;
    }

    /**
     * @return array
     */
    public function getExtField(): array
    {
        return $this->extField;
    }

    /**
     * @param array $extField
     */
    public function setExtField(array $extField): void
    {
        $this->extField = $extField;
    }

    /**
     * @return string
     */
    public function getLogicalPage(): string
    {
        return $this->logicalPage;
    }

    /**
     * @param string $logicalPage
     */
    public function setLogicalPage(string $logicalPage): void
    {
        $this->logicalPage = $logicalPage;
    }

    /**
     * @return int
     */
    public function getCollection(): int
    {
        return $this->collection;
    }

    /**
     * @param int $collection
     */
    public function setCollection(int $collection): void
    {
        $this->collection = $collection;
    }

    /**
     * @return int
     */
    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId(int $documentId): void
    {
        $this->documentId = $documentId;
    }

    /**
     * @return string
     */
    public function getEncryptedCoreName(): string
    {
        return $this->encryptedCoreName;
    }

    /**
     * @param string $encryptedCoreName
     */
    public function setEncryptedCoreName(string $encryptedCoreName): void
    {
        $this->encryptedCoreName = $encryptedCoreName;
    }

    /**
     * @return array
     */
    public function getFilterQuery(): array
    {
        return $this->filterQuery;
    }

    /**
     * @param array $filterQuery
     */
    public function setFilterQuery(array $filterQuery): void
    {
        $this->filterQuery = $filterQuery;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    /**
     * @return int
     */
    public function getAsc(): int
    {
        return $this->asc;
    }

    /**
     * @param int $asc
     */
    public function setAsc(int $asc): void
    {
        $this->asc = $asc;
    }


}