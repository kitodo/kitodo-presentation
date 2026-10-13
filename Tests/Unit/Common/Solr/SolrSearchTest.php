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

namespace Kitodo\Dlf\Tests\Unit\Common\Solr;

use Kitodo\Dlf\Common\Solr\SolrSearch;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SolrSearchTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @var Logger|MockObject
     */
    protected Logger|MockObject $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->createMock(Logger::class);
        $logManagerMock = $this->createMock(LogManager::class);
        $logManagerMock->method('getLogger')->willReturn($this->loggerMock);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManagerMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function plainBrowsingOfRegularCollectionListsRootDocumentsOnly(): void
    {
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('reg', '', 'name_1'),
        ]);

        self::assertSame(
            '(collection_faceting:("name_1") AND toplevel:true AND partof:0)',
            $this->invokeGetCollectionFilterQuery($solrSearch, '', false)
        );
    }

    #[Test]
    public function facetClickDoesNotRestrictRegularCollectionToRootDocuments(): void
    {
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('reg', '', 'name_1'),
        ]);

        // No search term was entered, but a facet is active. The facet filter
        // must be applied instead of restricting the result to root documents.
        self::assertSame(
            '(collection_faceting:("name_1"))',
            $this->invokeGetCollectionFilterQuery($solrSearch, '*', true)
        );
    }

    #[Test]
    public function plainBrowsingOfVirtualCollectionListsRootDocumentsOnly(): void
    {
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('v', 'record_id:123'),
        ]);

        self::assertSame(
            '(((record_id:123)) AND toplevel:true AND partof:0)',
            $this->invokeGetCollectionFilterQuery($solrSearch, '', false)
        );
    }

    #[Test]
    public function facetClickDoesNotRestrictVirtualCollectionToRootDocuments(): void
    {
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('v', 'record_id:123'),
        ]);

        self::assertSame(
            '((record_id:123))',
            $this->invokeGetCollectionFilterQuery($solrSearch, '*', true)
        );
    }

    /**
     * Creates a SolrSearch with a mocked DocumentRepository and the given collections.
     */
    private function createSolrSearch(array $collections): SolrSearch
    {
        $documentRepository = $this->createMock(DocumentRepository::class);
        return new SolrSearch($documentRepository, $collections, ['storagePid' => 0]);
    }

    private function createCollection(string $label, string $indexSearch, ?string $indexName = null): Collection
    {
        $collection = new Collection();
        $collection->setLabel($label);
        $collection->setIndexName($indexName ?? '');
        $collection->setIndexSearch($indexSearch);
        return $collection;
    }

    private function invokeGetCollectionFilterQuery(SolrSearch $solrSearch, string $query, bool $lFacet): string
    {
        $method = new ReflectionMethod(SolrSearch::class, 'getCollectionFilterQuery');
        return $method->invoke($solrSearch, $query, $lFacet);
    }
}
