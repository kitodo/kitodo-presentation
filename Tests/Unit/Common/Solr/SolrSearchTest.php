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

    #[Test]
    public function searchMergesTwoJoinedCollectionsWhenSearching(): void
    {
        $prefix = '{!join from=uid to=uid}';
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('a', $prefix . '(record_id:856399094)'),
            $this->createCollection('b', $prefix . '(record_id:776857304)'),
        ]);

        // Searching (a term is present) merges both {!join} collections into a
        // single joined clause so the fulltext of all referenced pages is reached.
        self::assertSame(
            '{!join from=uid to=uid}((record_id:856399094) OR (record_id:776857304))',
            $this->invokeGetCollectionFilterQuery($solrSearch, 'term', false)
        );
    }

    #[Test]
    public function searchKeepsSingleJoinedCollectionAsIs(): void
    {
        $prefix = '{!join from=uid to=uid}';
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('a', $prefix . '(record_id:856399094)'),
        ]);

        // A single join-based collection passes through unchanged.
        self::assertSame(
            '{!join from=uid to=uid}(record_id:856399094)',
            $this->invokeGetCollectionFilterQuery($solrSearch, 'term', false)
        );
    }

    #[Test]
    public function searchPrependsJoinedClauseBeforeOtherCollections(): void
    {
        $prefix = '{!join from=uid to=uid}';
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('v', 'type:issue'),
            $this->createCollection('j', $prefix . '(record_id:856399094)'),
            $this->createCollection('reg', '', 'name_1'),
        ]);

        // The joined clause must lead; regular/virtual collections are OR-combined behind it.
        $expected = $prefix . '(record_id:856399094)'
            . ' OR (collection_faceting:("name_1")) OR ((type:issue))';
        self::assertSame($expected, $this->invokeGetCollectionFilterQuery($solrSearch, 'term', false));
    }

    #[Test]
    public function searchLogsDebugWhenJoinedCollectionsHaveDifferentLocalParameterBlocks(): void
    {
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('a', '{!join from=uid to=uid}(record_id:856399094)'),
            $this->createCollection('b', '{!join from=partof to=uid}(record_id:776857304)'),
        ]);

        // The reference queries are merged into the first join (superset of
        // matches), with a debug message logged because the prefixes differ.
        $this->loggerMock->expects(self::once())->method('debug');
        self::assertSame(
            '{!join from=uid to=uid}((record_id:856399094) OR (record_id:776857304))',
            $this->invokeGetCollectionFilterQuery($solrSearch, 'term', false)
        );
    }

    #[Test]
    public function browsingStripsJoinDownToReferences(): void
    {
        $prefix = '{!join from=uid to=uid}';
        $solrSearch = $this->createSolrSearch([
            $this->createCollection('a', $prefix . '(record_id:856399094)'),
            $this->createCollection('b', $prefix . '(record_id:776857304)'),
        ]);

        // Plain browsing (no query, no facet) lists root documents only: the join
        // is dropped and only the combined reference query with the toplevel/partof
        // restriction is used.
        self::assertSame(
            '((((record_id:856399094)) OR ((record_id:776857304))) AND toplevel:true AND partof:0)',
            $this->invokeGetCollectionFilterQuery($solrSearch, '', false)
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
