<?php

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Model\SolrCore;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class SolrIndexingTest extends FunctionalTestCase
{
    /** @var PersistenceManager */
    protected $persistenceManager;

    /** @var CollectionRepository */
    protected $collectionRepository;

    /** @var DocumentRepository */
    protected $documentRepository;

    /** @var SolrCoreRepository */
    protected $solrCoreRepository;

    public function setUp(): void
    {
        parent::setUp();

        // Needed for Indexer::add, which uses the language service
        Bootstrap::initializeLanguageObject();

        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);

        $this->collectionRepository = $this->initializeRepository(CollectionRepository::class, 20000);
        $this->documentRepository = $this->initializeRepository(DocumentRepository::class, 20000);
        $this->solrCoreRepository = $this->initializeRepository(SolrCoreRepository::class, 20000);

        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/documents_1.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/libraries.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/metadata.xml');
    }

    /**
     * @test
     */
    public function canCreateCore()
    {
        $coreName = uniqid('testCore');
        $solr = Solr::getInstance($coreName);
        $this->assertNull($solr->core);

        $actualCoreName = Solr::createCore($coreName);
        $this->assertEquals($actualCoreName, $coreName);

        $solr = Solr::getInstance($coreName);
        $this->assertNotNull($solr->core);
    }

    /**
     * @test
     */
    public function canIndexAndSearchDocument()
    {
        $core = $this->createSolrCore();

        $document = $this->documentRepository->findByUid(1001);
        $document->setSolrcore($core->model->getUid());
        $this->persistenceManager->persistAll();

        $doc = Doc::getInstance($document->getLocation());
        $document->setDoc($doc);

        $indexingSuccessful = Indexer::add($document);
        $this->assertTrue($indexingSuccessful);

        $solrSettings = [
            'solrcore' => $core->solr->core,
            'storagePid' => $document->getPid(),
        ];

        $solrSearch = $this->documentRepository->findSolrByCollection(null, $solrSettings, ['query' => '*']);
        $solrSearch->getQuery()->execute();
        $this->assertEquals(1, count($solrSearch));
        $this->assertEquals(15, $solrSearch->getNumFound());

        // Check that the title stored in Solr matches the title of database entry
        $docTitleInSolr = false;
        foreach ($solrSearch->getSolrResults()['documents'] as $solrDoc) {
            if ($solrDoc['toplevel'] && $solrDoc['uid'] === $document->getUid()) {
                $this->assertEquals($document->getTitle(), $solrDoc['title']);
                $docTitleInSolr = true;
                break;
            }
        }
        $this->assertTrue($docTitleInSolr);

        // $solrSearch[0] is hydrated from the database model
        $this->assertEquals($document->getTitle(), $solrSearch[0]['title']);

        // Test ArrayAccess and Iterator implementation
        $this->assertTrue(isset($solrSearch[0]));
        $this->assertFalse(isset($solrSearch[1]));
        $this->assertNull($solrSearch[1]);
        $this->assertFalse(isset($solrSearch[$document->getUid()]));

        $iter = [];
        foreach ($solrSearch as $key => $value) {
            $iter[$key] = $value;
        }
        $this->assertEquals(1, count($iter));
        $this->assertEquals($solrSearch[0], $iter[0]);
    }

    /**
     * @test
     */
    public function canSearchInCollections()
    {
        $core = $this->createSolrCore();

        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/documents_fulltext.xml');
        $this->importSolrDocuments($core->solr, __DIR__ . '/../../Fixtures/Common/documents_1.solr.json');
        $this->importSolrDocuments($core->solr, __DIR__ . '/../../Fixtures/Common/documents_fulltext.solr.json');

        $collections = $this->collectionRepository->findCollectionsBySettings([
            'index_name' => ['Musik', 'Projekt: Dresdner Hefte'],
        ]);
        $musik = $collections[0];
        $dresdnerHefte = $collections[1];

        $settings = [
            'solrcore' => $core->solr->core,
            'storagePid' => 20000,
        ];

        // No query: Only list toplevel result(s) in collection(s)
        $musikSearch = $this->documentRepository->findSolrByCollection($musik, $settings, []);
        $dresdnerHefteSearch = $this->documentRepository->findSolrByCollection($dresdnerHefte, $settings, []);
        $multiCollectionSearch = $this->documentRepository->findSolrByCollection($collections, $settings, []);
        $this->assertGreaterThanOrEqual(1, $musikSearch->getNumFound());
        $this->assertGreaterThanOrEqual(1, $dresdnerHefteSearch->getNumFound());
        $this->assertEquals('533223312LOG_0000', $dresdnerHefteSearch->getSolrResults()['documents'][0]['id']);
        $this->assertEquals(
            // Assuming there's no overlap
            $dresdnerHefteSearch->getNumFound() + $musikSearch->getNumFound(),
            $multiCollectionSearch->getNumFound()
        );

        // With query: List all results
        $metadataSearch = $this->documentRepository->findSolrByCollection($dresdnerHefte, $settings, ['query' => 'Dresden']);
        $fulltextSearch = $this->documentRepository->findSolrByCollection($dresdnerHefte, $settings, ['query' => 'Dresden', 'fulltext' => '1']);
        $this->assertGreaterThan($metadataSearch->getNumFound(), $fulltextSearch->getNumFound());
    }

    protected function createSolrCore(): object
    {
        $coreName = Solr::createCore();
        $solr = Solr::getInstance($coreName);

        $model = GeneralUtility::makeInstance(SolrCore::class);
        $model->setLabel('Testing Solr Core');
        $model->setIndexName($coreName);
        $this->solrCoreRepository->add($model);
        $this->persistenceManager->persistAll();

        return (object) compact('solr', 'model');
    }
}
