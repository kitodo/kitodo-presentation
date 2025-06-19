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

use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageViewProxyDisabledTest extends FunctionalTestCase
{
    protected bool $disableJsonWrappedResponse = true;

    /**
     * Query the page view proxy with the given parameters.
     *
     * @access protected
     *
     * @param array $query The query parameters to send
     * @param string $method The HTTP method to use (default: 'GET')
     *
     * @return ResponseInterface
     */
    protected function queryProxy(array $query, string $method = 'GET'): ResponseInterface
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
