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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * eID image proxy for plugin 'Page View' of the 'dlf' extension
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class PageViewProxy
{
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
        // the URI to fetch data or header from
        $url = (string) $request->getQueryParams()['url'];
        if (!Helper::isValidHttpUrl($url)) {
            throw new \InvalidArgumentException('No valid url passed!', 1580482805);
        }

        // get and verify the uHash
        $uHash = (string) $request->getQueryParams()['uHash'];
        if (!hash_equals(GeneralUtility::hmac($url, 'PageViewProxy'), $uHash)) {
            throw new \InvalidArgumentException('No valid uHash passed!', 1643796565);
        }

        // fetch the requested data
        $fetchedData = GeneralUtility::getUrl($url);

        // Fetch header data separately to get "Last-Modified" info
        $fetchedHeaderString = GeneralUtility::getUrl($url, 2);
        if (!empty($fetchedHeaderString)) {
            $fetchedHeader = explode("\n", $fetchedHeaderString);
            foreach ($fetchedHeader as $headerline) {
                if (stripos($headerline, 'Last-Modified:') !== false) {
                    $lastModified = trim(substr($headerline, strpos($headerline, ':') + 1));
                    break;
                }
            }
        }

        // create response object
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        if ($fetchedData) {
            $response->getBody()->write($fetchedData);
            $response = $response->withHeader('Access-Control-Allow-Methods', 'GET');
            $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin') ? : '*');
            $response = $response->withHeader('Access-Control-Max-Age', '86400');
            $response = $response->withHeader('Content-Type', finfo_buffer(finfo_open(FILEINFO_MIME), $fetchedData));
        }
        if (!empty($lastModified)) {
            $response = $response->withHeader('Last-Modified', $lastModified);
        }
        return $response;
    }
}
