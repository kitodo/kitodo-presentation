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
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class SolrSearchTest extends FunctionalTestCase
{
    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Common/solrcores.csv'
    ];

    static array $solrFixtures = [
        __DIR__ . '/../../Fixtures/Common/documents_1.solr.json'
    ];

    private Solr $solr;

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
        $this->solr = $this->setUpSolr(4, 0, self::$solrFixtures);
    }

    /**
     * @test
     */
    public function canPrepareAndSubmit()
    {
        $this->markTestSkipped('Does not work in combination with other tests.');

        $documentRepository = $this->initializeRepository(DocumentRepository::class, 0);
        $settings = ['solrcore' => 4, 'storagePid' => 0];

        $resultSet = $this->solr->searchRaw(['core' => 4, 'collection' => 1]);
        $this->assertCount(33, $resultSet);


        $params1 = ['query' => '*'];
        $search = new SolrSearch($documentRepository, null, $settings, $params1);
        $search->prepare();
        $this->assertEquals(33, $search->getNumFound());
        $this->assertEquals(3, $search->getSolrResults()['numberOfToplevels']);
        $this->assertCount(15, $search->getSolrResults()['documents']);

        $params2 = ['query' => '10 Keyboard pieces'];
        $search2 = new SolrSearch($documentRepository, null, $settings, $params2);
        $search2->prepare();
        $this->assertEquals(1, $search2->getNumFound());
        $this->assertEquals(1, $search2->getSolrResults()['numberOfToplevels']);
        $this->assertCount(1, $search2->getSolrResults()['documents']);

        $params3 = ['query' => 'foobar'];
        $search3 = new SolrSearch($documentRepository, null, $settings, $params3);
        $search3->prepare();
        $this->assertEquals(0, $search3->getNumFound());
        $this->assertEquals(0, $search3->getSolrResults()['numberOfToplevels']);
        $this->assertCount(0, $search3->getSolrResults()['documents']);
    }

    protected function setUpData($databaseFixtures): void
    {
        foreach ($databaseFixtures as $filePath) {
            $this->importCSVDataSet($filePath);
        }
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $this->initializeRepository(DocumentRepository::class, 0);
    }

    protected function setUpSolr($uid, $storagePid, $solrFixtures)
    {
        $this->solrCoreRepository = $this->initializeRepository(SolrCoreRepository::class, $storagePid);

        // Setup Solr only once for all tests in this suite
        static $solr = null;

        if ($solr === null) {
            $coreName = Solr::createCore();
            $solr = Solr::getInstance($coreName);
            foreach ($solrFixtures as $filePath) {
                $this->importSolrDocuments($solr, $filePath);
            }
        }

        $coreModel = $this->solrCoreRepository->findByUid($uid);
        $coreModel->setIndexName($solr->core);
        $this->solrCoreRepository->update($coreModel);
        $this->persistenceManager->persistAll();
        return $solr;
    }
}
