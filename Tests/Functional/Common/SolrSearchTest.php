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

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Common\Solr\SolrSearch;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class SolrSearchTest extends FunctionalTestCase
{
    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Common/solrcores.csv'
    ];

    private static array $solrFixtures = [
        __DIR__ . '/../../Fixtures/Common/documents_1.solr.json'
    ];

    /**
     * Sets up the test environment.
     *
     * This method is called before each test method is executed.
     * It imports the necessary CSV datasets and sets up the Solr core for the tests.
     *
     * @access public
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
        $this->setUpSolr(5, 0, self::$solrFixtures);
    }

    /**
     * @test
     */
    public function canPrepareAndSubmit()
    {
        $documentRepository = $this->initializeRepository(DocumentRepository::class, 0);
        $solrCoreName = $this->solrCoreRepository->findByUid(5)->getIndexName();
        $settings = ['solrcore' => $solrCoreName, 'storagePid' => 0];

        $resultSet = $this->solr->searchRaw(['core' => 5, 'collection' => 1]);
        self::assertCount(33, $resultSet);

        $params1 = ['query' => '*'];
        $search = new SolrSearch($documentRepository, [], $settings, $params1);
        $search->prepare();
        self::assertEquals(33, $search->getNumFound());
        self::assertEquals(3, $search->getSolrResults()['numberOfToplevels']);
        self::assertCount(15, $search->getSolrResults()['documents']);

        $params2 = ['query' => '10 Keyboard pieces'];
        $search2 = new SolrSearch($documentRepository, [], $settings, $params2);
        $search2->prepare();
        self::assertEquals(1, $search2->getNumFound());
        self::assertEquals(1, $search2->getSolrResults()['numberOfToplevels']);
        self::assertCount(1, $search2->getSolrResults()['documents']);

        $params3 = ['query' => 'foobar'];
        $search3 = new SolrSearch($documentRepository, [], $settings, $params3);
        $search3->prepare();

        $this->assertEquals(0, $search3->getNumFound());
        $this->assertEquals(0, $search3->getSolrResults()['numberOfToplevels']);
        $this->assertCount(0, $search3->getSolrResults()['documents']);
    }
}
