<?php

namespace Kitodo\Dlf\Tests\Functional\Api;

use DateTime;
use GuzzleHttp\Client as HttpClient;
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use Phpoaipmh\Endpoint;
use Phpoaipmh\Exception\OaipmhException;
use SimpleXMLElement;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class OaiPmhTest extends FunctionalTestCase
{
    protected $disableJsonWrappedResponse = true;

    protected $coreExtensionsToLoad = [
        'fluid',
        'fluid_styled_content',
    ];

    /** @var int */
    protected $oaiPage = 20001;

    /** @var string */
    protected $oaiUrl;

    /** @var int */
    protected $oaiPageNoStoragePid = 20002;

    /** @var string */
    protected $oaiUrlNoStoragePid;

    public function setUp(): void
    {
        parent::setUp();

        $this->oaiUrl = $this->baseUrl . 'index.php?id=' . $this->oaiPage;
        $this->oaiUrlNoStoragePid = $this->baseUrl . 'index.php?id=' . $this->oaiPageNoStoragePid;

        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/documents_1.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/metadata.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/libraries.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/OaiPmh/pages.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/OaiPmh/solrcores.xml');

        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $this->solrCoreRepository = $this->initializeRepository(SolrCoreRepository::class, 20000);

        $this->setUpOaiSolr();
    }

    protected function setUpOaiSolr()
    {
        // Setup Solr only once for all tests in this suite
        static $solr = null;

        if ($solr === null) {
            $coreName = Solr::createCore();
            $solr = Solr::getInstance($coreName);

            $this->importSolrDocuments($solr, __DIR__ . '/../../Fixtures/Common/documents_1.solr.json');
        }

        $oaiCoreModel = $this->solrCoreRepository->findByUid(11001);
        $oaiCoreModel->setIndexName($solr->core);
        $this->solrCoreRepository->update($oaiCoreModel);
        $this->persistenceManager->persistAll();
    }

    /**
     * @test
     */
    public function correctlyRespondsOnBadVerb()
    {
        $client = new HttpClient();
        $response = $client->get($this->baseUrl, [
            'query' => [
                'id' => $this->oaiPage,
                'verb' => 'nastyVerb',
            ],
        ]);
        $xml = new SimpleXMLElement((string) $response->getBody());

        $this->assertEquals('badVerb', (string) $xml->error['code']);

        // The base URL may be different from the one used that we actually used,
        // but it shouldn't contain the verb argument
        $this->assertStringNotContainsString('nastyVerb', (string) $xml->request);

        // For bad verbs, the <request> element must not contain any attributes
        // - http://www.openarchives.org/OAI/openarchivesprotocol.html#XMLResponse
        // - http://www.openarchives.org/OAI/openarchivesprotocol.html#ErrorConditions
        $this->assertEmpty($xml->request->attributes());
    }

    /**
     * @test
     */
    public function canIdentify()
    {
        $oai = Endpoint::build($this->oaiUrl);
        $identity = $oai->identify();

        $this->assertEquals('Identify', (string) $identity->request['verb']);
        $this->assertEquals('Default Library - OAI Repository', (string) $identity->Identify->repositoryName);
        $this->assertUtcDateString((string) $identity->Identify->earliestDatestamp);
        $this->assertEquals('default-library@example.com', (string) $identity->Identify->adminEmail);
    }

    /**
     * @test
     */
    public function identifyGivesFallbackDatestampWhenNoDocuments()
    {
        $oai = Endpoint::build($this->oaiUrlNoStoragePid);
        $identity = $oai->identify();

        $this->assertUtcDateString((string) $identity->Identify->earliestDatestamp);
    }

    /**
     * @test
     */
    public function canListMetadataFormats()
    {
        $oai = Endpoint::build($this->oaiUrl);
        $formats = $oai->listMetadataFormats();

        $formatMap = [];
        foreach ($formats as $format) {
            $formatMap[(string) $format->metadataPrefix] = $format;
        }

        $this->assertEquals('http://www.loc.gov/METS/', (string) $formatMap['mets']->metadataNamespace);
    }

    /**
     * @test
     */
    public function canListRecords()
    {
        $oai = Endpoint::build($this->oaiUrl);
        $result = $oai->listRecords('mets');

        $record = $result->current();
        $metsRoot = $record->metadata->children('http://www.loc.gov/METS/')[0];
        $this->assertNotNull($metsRoot);
        $this->assertEquals('mets', $metsRoot->getName());
    }

    /**
     * @test
     */
    public function noRecordsUntil1900()
    {
        $this->expectException(OaipmhException::class);
        $this->expectExceptionMessage('empty list');

        $oai = Endpoint::build($this->oaiUrl);
        $result = $oai->listRecords('mets', null, (new DateTime())->setDate(1900, 1, 1));

        $result->current();
    }

    /**
     * @test
     */
    public function canUseResumptionToken()
    {
        // NOTE: cursor and expirationDate are optional by the specification,
        //       but we include them in our implementation

        $client = new HttpClient();

        // The general handling of resumption tokens should be the same for these verbs
        foreach (['ListIdentifiers', 'ListRecords'] as $verb) {
            // Check that we get a proper resumption token when starting a list
            $response = $client->get($this->baseUrl, [
                'query' => [
                    'id' => $this->oaiPage,
                    'verb' => $verb,
                    'metadataPrefix' => 'mets',
                ],
            ]);
            $xml = new SimpleXMLElement((string) $response->getBody());

            $resumptionToken = $xml->$verb->resumptionToken;
            $this->assertEquals('0', (string) $resumptionToken['cursor']);
            $this->assertInFuture((string) $resumptionToken['expirationDate']);
            $this->assertNotEmpty((string) $resumptionToken);

            // Store list size to check that it remains constant (and check its sanity)
            $completeListSize = (int) $resumptionToken['completeListSize'];
            $this->assertGreaterThan(2, $completeListSize); // we have more than two documents in document set

            // Check that we can resume and get a proper cursor value
            $cursor = 1;
            do {
                $response = $client->get($this->baseUrl, [
                    'query' => [
                        'id' => $this->oaiPage,
                        'verb' => $verb,
                        'resumptionToken' => (string) $resumptionToken,
                    ],
                ]);
                $xml = new SimpleXMLElement((string) $response->getBody());

                $resumptionToken = $xml->$verb->resumptionToken;
                $tokenStr = (string) $resumptionToken;

                $this->assertEquals($cursor, (string) $resumptionToken['cursor']); // settings.limit = 1
                $this->assertEquals($completeListSize, (string) $resumptionToken['completeListSize']);

                // The last resumptionToken is empty and doesn't have expirationDate
                $isLastBatch = $cursor + 1 >= $completeListSize;
                $this->assertEquals($isLastBatch, empty((string) $resumptionToken['expirationDate']));
                $this->assertEquals($isLastBatch, empty($tokenStr));

                $cursor++;
            } while ($tokenStr);
        }
    }

    /**
     * @test
     */
    public function noResumptionTokenForCompleteList()
    {
        $client = new HttpClient();

        foreach (['ListIdentifiers', 'ListRecords'] as $verb) {
            $response = $client->get($this->baseUrl, [
                'query' => [
                    'id' => $this->oaiPage,
                    'verb' => $verb,
                    'metadataPrefix' => 'mets',
                    'set' => 'collection-with-single-document',
                ],
            ]);
            $xml = new SimpleXMLElement((string) $response->getBody());

            $this->assertEquals(1, count($xml->$verb->children()));
            $this->assertEmpty($xml->$verb->resumptionToken);
        }
    }

    /**
     * @test
     */
    public function canListAndResumeIdentifiers()
    {
        $oai = Endpoint::build($this->oaiUrl);
        $result = $oai->listIdentifiers('mets');

        $record = $result->current();
        $this->assertEquals('oai:de:slub-dresden:db:id-476251419', $record->identifier);
        $this->assertEquals(['collection-with-single-document', 'music'], (array) $record->setSpec);

        // This should use a resumption token because settings.limit is 1
        $record = $result->next();
        $this->assertEquals('oai:de:slub-dresden:db:id-476248086', $record->identifier);
    }

    protected function parseUtc(string $dateTime)
    {
        return DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $dateTime);
    }

    protected function assertUtcDateString(string $dateTime)
    {
        $this->assertInstanceOf(DateTime::class, $this->parseUtc($dateTime));
    }

    protected function assertInFuture(string $dateTime)
    {
        $this->assertGreaterThan(new DateTime(), $this->parseUtc($dateTime));
    }
}
