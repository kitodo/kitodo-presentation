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
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class PageViewProxyTest extends FunctionalTestCase
{
    /**
     * Returns the DLF configuration for the test instance.
     *
     * This configuration is loaded from a .env file in the test directory.
     * It includes general settings, file groups, and Solr settings.
     *
     * @access protected
     *
     * @return array The DLF configuration
     *
     * @access protected
     */
    protected function getDlfConfiguration(): array
    {
        return array_merge(parent::getDlfConfiguration(), [
            'general' => [
                'enableInternalProxy' => true
            ]
        ]);
    }

    /**
     * Query the page view proxy with the given parameters.
     *
     * @access protected
     *
     * @param array $query The query parameters to send
     * @param string $method The HTTP method to use (default: 'GET')
     *
     * @return ResponseInterface
     *
     * @access protected
     */
    protected function queryProxy(array $query, string $method = 'GET'): ResponseInterface
    {
        $request = (new InternalRequest($this->baseUrl))->withQueryParameters(
            array_merge([ 'eID' => 'tx_dlf_pageview_proxy' ], $query)
        )->withMethod($method);

        return $this->executeInternalRequest($request);
    }

    /**
     * @test
     */
    public function cannotAccessFileUrl(): void
    {
        $response = $this->queryProxy([
            'url' => 'file:///etc/passwd',
        ]);

        self::assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cannotAccessUrlWithoutUrlHash(): void
    {
        $response = $this->queryProxy([
            'url' => 'http://web:8001/Tests/Fixtures/PageViewProxy/test.txt',
        ]);

        self::assertEquals(401, $response->getStatusCode());
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

        self::assertEquals(401, $response->getStatusCode());
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

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('This is some plain text test file.' . "\n", (string) $response->getBody());
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

        self::assertEquals(405, $response->getStatusCode());
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

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Kitodo.Presentation', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function canQueryOptions(): void
    {
        $response = $this->queryProxy([], 'OPTIONS');

        self::assertGreaterThanOrEqual(200, $response->getStatusCode());
        self::assertLessThan(300, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Access-Control-Allow-Methods'));
    }
}
