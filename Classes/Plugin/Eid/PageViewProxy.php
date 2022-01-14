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

namespace Kitodo\Dlf\Plugin\Eid;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\StdOutStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * eID image proxy for plugin 'Page View' of the 'dlf' extension
 *
 * Supported query parameters:
 * - `url` (mandatory): The URL to be proxied
 * - `uHash` (mandatory): HMAC of the URL
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class PageViewProxy
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var mixed
     */
    protected $extConf;

    public function __construct()
    {
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');
    }

    /**
     * The main method of the eID script
     *
     * @access public
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function main(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();

        $url = (string) ($queryParams['url'] ?? '');
        if (!Helper::isValidHttpUrl($url)) {
            return new JsonResponse(['message' => 'Did not receive a valid URL.'], 400);
        }

        // get and verify the uHash
        $uHash = (string) ($queryParams['uHash'] ?? '');
        if (!hash_equals(GeneralUtility::hmac($url, 'PageViewProxy'), $uHash)) {
            return new JsonResponse(['message' => 'No valid uHash passed!'], 401);
        }

        try {
            $response = $this->requestFactory->request($url, 'GET', [
                'headers' => [
                    'User-Agent' => $this->extConf['useragent'] ?? 'Kitodo.Presentation Proxy',
                ],

                // For performance, don't download content up-front. Rather, we'll
                // download and upload simultaneously.
                // https://docs.guzzlephp.org/en/6.5/request-options.html#stream
                'stream' => true,

                // Don't throw exceptions when a non-success status code is
                // received. We handle these manually.
                'http_errors' => false,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Could not fetch resource of given URL.'], 500);
        }

        $body = new StdOutStream($response->getBody());

        return GeneralUtility::makeInstance(Response::class)
            ->withStatus($response->getStatusCode())
            ->withHeader('Access-Control-Allow-Methods', 'GET')
            ->withHeader('Access-Control-Allow-Origin', (string) ($request->getHeaderLine('Origin') ?: '*'))
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Content-Type', (string) $response->getHeader('Content-Type')[0])
            ->withHeader('Last-Modified', (string) $response->getHeader('Last-Modified')[0])
            ->withBody($body);
    }
}
