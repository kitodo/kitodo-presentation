<?php

namespace Kitodo\Dlf\Tests\Functional\Api;

use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageViewProxyDisabledTest extends FunctionalTestCase
{
    protected $disableJsonWrappedResponse = true;

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
    public function cannotAccessPageWhenProxyIsDisabled(): void
    {
        $targetUrl = 'http://web:8001/Tests/Fixtures/PageViewProxy/test.txt';
        $uHash = GeneralUtility::hmac($targetUrl, 'PageViewProxy');

        $response = $this->queryProxy([
            'url' => $targetUrl,
            'uHash' => $uHash,
        ]);

        self::assertEquals(404, $response->getStatusCode());
    }
}
