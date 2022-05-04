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

        $result = $this->documentRepository->findSolrByCollection(null, $solrSettings, ['query' => '*']);
        $this->assertEquals(1, $result['numberOfToplevels']);
        $this->assertEquals(15, count($result['solrResults']['documents']));

        // Check that the title stored in Solr matches the title of database entry
        $docTitleInSolr = false;
        foreach ($result['solrResults']['documents'] as $solrDoc) {
            if ($solrDoc['toplevel'] && $solrDoc['uid'] === $document->getUid()) {
                $this->assertEquals($document->getTitle(), $solrDoc['title']);
                $docTitleInSolr = true;
                break;
            }
        }
        $this->assertTrue($docTitleInSolr);

        // $result['documents'] is hydrated from the database model
        $this->assertEquals($document->getTitle(), $result['documents'][$document->getUid()]['title']);
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
        $musikResults = $this->documentRepository->findSolrByCollection($musik, $settings, []);
        $dresdnerHefteResults = $this->documentRepository->findSolrByCollection($dresdnerHefte, $settings, []);
        $multiCollectionResults = $this->documentRepository->findSolrByCollection($collections, $settings, []);
        $this->assertGreaterThanOrEqual(1, $musikResults['solrResults']['numFound']);
        $this->assertGreaterThanOrEqual(1, $dresdnerHefteResults['solrResults']['numFound']);
        $this->assertEquals('533223312LOG_0000', $dresdnerHefteResults['solrResults']['documents'][0]['id']);
        $this->assertEquals(
            // Assuming there's no overlap
            $dresdnerHefteResults['solrResults']['numFound'] + $musikResults['solrResults']['numFound'],
            $multiCollectionResults['solrResults']['numFound']
        );

        // With query: List all results
        $metadataResults = $this->documentRepository->findSolrByCollection($dresdnerHefte, $settings, ['query' => 'Dresden']);
        $fulltextResults = $this->documentRepository->findSolrByCollection($dresdnerHefte, $settings, ['query' => 'Dresden', 'fulltext' => '1']);
        $this->assertGreaterThan($metadataResults['solrResults']['numFound'], $fulltextResults['solrResults']['numFound']);
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
