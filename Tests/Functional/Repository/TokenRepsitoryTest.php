<?php

namespace Kitodo\Dlf\Tests\Functional\Repository;

use Kitodo\Dlf\Domain\Repository\TokenRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class TokenRepsitoryTest extends FunctionalTestCase
{
    /**
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);

        $this->tokenRepository = $this->initializeRepository(
            TokenRepository::class,
            20000
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unlink(__DIR__ . '/../../Fixtures/Repository/tokenTemp.xml');
    }

    /**
     * @test
     * @group delete
     */
    public function deleteExpiredTokens(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Repository/token.xml');

        $expireTime = 3600;
        $i = 1;
        foreach ($xml as $node) {
            if ($i % 2 == 0) {
                $node->tstamp = time() - $expireTime - random_int(10, 3600);
            } else {
                $node->tstamp = time() - $expireTime + random_int(10, 3600);
            }
            $i++;
        }

        $xml->saveXML(__DIR__ . '/../../Fixtures/Repository/tokenTemp.xml');

        $this->importDataSet(__DIR__ . '/../../Fixtures/Repository/tokenTemp.xml');

        $this->tokenRepository->deleteExpiredTokens($expireTime);

        $this->persistenceManager->persistAll();

        $tokens = $this->tokenRepository->findAll();

        $this->assertEquals(2, $tokens->count());

        $tokenUids = [];
        foreach ($tokens as $token) {
            $tokenUids[$token->getUid()] = $token;
        }

        $this->assertArrayHasKey('101', $tokenUids);
        $this->assertArrayHasKey('103', $tokenUids);
    }
}
