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

namespace Kitodo\Dlf\Tests\Functional\Api;

use DateTime;
use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use Kitodo\Dlf\Tests\Functional\Api\OaiPmhTypo3Client;
use Phpoaipmh\Endpoint;
use Phpoaipmh\Exception\OaipmhException;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class OaiPmhTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'fluid',
        'fluid_styled_content',
    ];

    /** @var int */
    protected int $oaiPage = 20001;

    /** @var string */
    protected string $oaiUrl;

    /** @var int */
    protected int $oaiPageNoStoragePid = 20002;

    /** @var string */
    protected string $oaiUrlNoStoragePid;

    /**
     * Sets up the test case environment.
     *
     * @access public
     *
     * @return void
     *
     * @access public
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->oaiUrl = $this->baseUrl . 'index.php?id=' . $this->oaiPage;
        $this->oaiUrlNoStoragePid = $this->baseUrl . 'index.php?id=' . $this->oaiPageNoStoragePid;

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/documents_1.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/metadata.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/libraries.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/OaiPmh/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/OaiPmh/solrcores.csv');

        $this->solrCoreRepository = $this->initializeRepository(SolrCoreRepository::class, 20000);

        $this->setUpOaiSolr();
    }

    /**
     * Sets up the OAI Solr core for the tests.
     *
     * This method initializes the Solr core and imports documents from a JSON file.
     * It is called only once for all tests in this suite to avoid redundant setup.
     *
     * @access protected
     *
     * @return void
     */
    protected function setUpOaiSolr(): void
    {
        // Setup Solr only once for all tests in this suite
        static $solr = null;

        if ($solr === null) {
            $coreName = Solr::createCore('OaiCore');
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
        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this, false);
        $xml = $client->request('nastyVerb');

        self::assertEquals('badVerb', (string) $xml->error['code']);

        // The base URL may be different from the one used that we actually used,
        // but it shouldn't contain the verb argument
        self::assertStringNotContainsString('nastyVerb', (string) $xml->request);

        // For bad verbs, the <request> element must not contain any attributes
        // - https://www.openarchives.org/OAI/openarchivesprotocol.html#XMLResponse
        // - https://www.openarchives.org/OAI/openarchivesprotocol.html#ErrorConditions
        self::assertEmpty($xml->request->attributes());
    }

    /**
     * @test
     */
    public function canIdentify()
    {
        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this);
        $identity = (new Endpoint($client))->identify();

        self::assertEquals('Identify', (string) $identity->request['verb']);
        self::assertEquals('Default Library - OAI Repository', (string) $identity->Identify->repositoryName);
        self::assertUtcDateString((string) $identity->Identify->earliestDatestamp);
        self::assertEquals('default-library@example.com', (string) $identity->Identify->adminEmail);
    }

    /**
     * @test
     */
    public function identifyGivesFallbackDatestampWhenNoDocuments()
    {
        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPageNoStoragePid, $this);
        $identity = (new Endpoint($client))->identify();

        self::assertUtcDateString((string) $identity->Identify->earliestDatestamp);
    }

    /**
     * @test
     */
    public function canListMetadataFormats()
    {
        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this);
        $formats = (new Endpoint($client))->listMetadataFormats();

        $formatMap = [];
        foreach ($formats as $format) {
            $formatMap[(string) $format->metadataPrefix] = $format;
        }

        self::assertEquals('http://www.loc.gov/METS/', (string) $formatMap['mets']->metadataNamespace);
    }

    /**
     * @test
     */
    public function canListRecords()
    {
        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this);
        $result = (new Endpoint($client))->listRecords('mets');

        $record = $result->current();
        $metsRoot = $record->metadata->children('http://www.loc.gov/METS/')[0];
        self::assertNotNull($metsRoot);
        self::assertEquals('mets', $metsRoot->getName());
    }

    /**
     * @test
     */
    public function noRecordsUntil1900()
    {
        $this->expectException(OaipmhException::class);
        $this->expectExceptionMessage('empty list');

        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this);
        $result = (new Endpoint($client))->listRecords('mets', null, (new DateTime())->setDate(1900, 1, 1));

        $result->current();
    }

    /**
     * @test
     */
    public function canUseResumptionToken()
    {
        // NOTE: cursor and expirationDate are optional by the specification,
        //       but we include them in our implementation

        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this);

        // The general handling of resumption tokens should be the same for these verbs
        foreach (['ListIdentifiers', 'ListRecords'] as $verb) {
            // Check that we get a proper resumption token when starting a list
            $xml = $client->request($verb, [ 'metadataPrefix' => 'mets' ]);

            $resumptionToken = $xml->$verb->resumptionToken;
            self::assertEquals('0', (string) $resumptionToken['cursor']);
            self::assertInFuture((string) $resumptionToken['expirationDate']);
            self::assertNotEmpty((string) $resumptionToken);

            // Store list size to check that it remains constant (and check its sanity)
            $completeListSize = (int) $resumptionToken['completeListSize'];
            self::assertGreaterThan(2, $completeListSize); // we have more than two documents in document set

            // Check that we can resume and get a proper cursor value
            $cursor = 1;
            do {
                $xml = $client->request($verb, [ 'resumptionToken' => (string) $resumptionToken ]);

                $resumptionToken = $xml->$verb->resumptionToken;
                $tokenStr = (string) $resumptionToken;

                self::assertEquals($cursor, (string) $resumptionToken['cursor']); // settings.limit = 1
                self::assertEquals($completeListSize, (string) $resumptionToken['completeListSize']);

                // The last resumptionToken is empty and doesn't have expirationDate
                $isLastBatch = $cursor + 1 >= $completeListSize;
                self::assertEquals($isLastBatch, empty((string) $resumptionToken['expirationDate']));
                self::assertEquals($isLastBatch, empty($tokenStr));

                $cursor++;
            } while ($tokenStr);
        }
    }

    /**
     * @test
     */
    public function noResumptionTokenForCompleteList()
    {
        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this);

        foreach (['ListIdentifiers', 'ListRecords'] as $verb) {
            $xml = $client->request($verb, [ 'metadataPrefix' => 'mets', 'set' => 'collection-with-single-document' ]);

            self::assertCount(1, $xml->$verb->children());
            self::assertEmpty($xml->$verb->resumptionToken);
        }
    }

    /**
     * @test
     */
    public function canListAndResumeIdentifiers()
    {
        $client = new OaiPmhTypo3Client($this->baseUrl, $this->oaiPage, $this);
        $result = (new Endpoint($client))->listIdentifiers('mets');

        $record = $result->current();
        self::assertEquals('oai:de:slub-dresden:db:id-476251419', $record->identifier);
        self::assertEquals(['collection-with-single-document', 'music'], (array) $record->setSpec);

        // This should use a resumption token because settings.limit is 1
        $record = $result->next();
        self::assertEquals('oai:de:slub-dresden:db:id-476248086', $record->identifier);
    }

    /**
     * Parses a UTC date string into a DateTime object.
     *
     * @access protected
     *
     * @static
     *
     * @param string $dateTime The date string in UTC format (e.g., '2023-10-01T12:00:00Z')
     *
     * @return DateTime|false Returns a DateTime object or false on failure
     *
     * @access protected
     *
     * @static
     */
    protected static function parseUtc(string $dateTime): DateTime|false
    {
        return DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $dateTime);
    }

    /**
     * Asserts that a given date string is a valid UTC date string.
     *
     * @access protected
     *
     * @static
     *
     * @param string $dateTime The date string to check
     *
     * @return void
     *
     * @access protected
     *
     * @static
     */
    protected static function assertUtcDateString(string $dateTime): void
    {
        self::assertInstanceOf(DateTime::class, self::parseUtc($dateTime));
    }

    /**
     * Asserts that a given date string is in the future.
     *
     * @access protected
     *
     * @static
     *
     * @param string $dateTime The date string to check
     *
     * @return void
     *
     * @access protected
     *
     * @static
     */
    protected static function assertInFuture(string $dateTime): void
    {
        self::assertGreaterThan(new DateTime(), self::parseUtc($dateTime));
    }
}
