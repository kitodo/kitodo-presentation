<?php

namespace Kitodo\Dlf\Tests\Functional\Api;

use GuzzleHttp\Client as HttpClient;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageViewProxyTest extends FunctionalTestCase
{
    protected $disableJsonWrappedResponse = true;

    protected function getDlfConfiguration()
    {
        return array_merge(parent::getDlfConfiguration(), [
            'enableInternalProxy' => true,
        ]);
    }

    protected function queryProxy(array $query, string $method = 'GET')
    {
        $query['eID'] = 'tx_dlf_pageview_proxy';

        return $this->httpClient->request($method, '', [
            'query' => $query,
        ]);
    }

    /**
     * @test
     */
    public function cannotAccessFileUrl(): void
    {
        $response = $this->queryProxy([
            'url' => 'file:///etc/passwd',
        ]);

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannotAccessUrlWithoutUrlHash(): void
    {
        $response = $this->queryProxy([
            'url' => 'http://web:8001/Tests/Fixtures/PageViewProxy/test.txt',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannotAccessUrlWithInvalidUrlHash(): void
    {
        $response = $this->queryProxy([
            'url' => 'http://web:8001/Tests/Fixtures/PageViewProxy/test.txt',
            'uHash' => 'nottherealhash',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function canAccessPageWithUrlHash(): void
    {
        $targetUrl = 'http://web:8001/Tests/Fixtures/PageViewProxy/test.txt';
        $uHash = GeneralUtility::hmac($targetUrl, 'PageViewProxy');

        $response = $this->queryProxy([
            'url' => $targetUrl,
            'uHash' => $uHash,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('This is some plain text test file.' . "\n", (string) $response->getBody());
    }

    /**
     * @test
     */
    public function cannotSendPostRequest(): void
    {
        $targetUrl = 'http://web:8001/Tests/Fixtures/PageViewProxy/test.txt';
        $uHash = GeneralUtility::hmac($targetUrl, 'PageViewProxy');

        $response = $this->queryProxy([
            'url' => $targetUrl,
            'uHash' => $uHash,
        ], 'POST');

        $this->assertEquals(405, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function sendsUserAgentToTarget(): void
    {
        $targetUrl = 'http://web:8001/Tests/Fixtures/PageViewProxy/echo_user_agent.php';
        $uHash = GeneralUtility::hmac($targetUrl, 'PageViewProxy');

        $response = $this->queryProxy([
            'url' => $targetUrl,
            'uHash' => $uHash,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Kitodo.Presentation Proxy', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function canQueryOptions(): void
    {
        $response = $this->queryProxy([], 'OPTIONS');

        $this->assertGreaterThanOrEqual(200, $response->getStatusCode());
        $this->assertLessThan(300, $response->getStatusCode());

        $this->assertNotEmpty($response->getHeader('Access-Control-Allow-Methods'));
    }
}
