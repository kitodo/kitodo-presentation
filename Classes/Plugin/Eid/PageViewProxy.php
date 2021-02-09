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
        // header parameter for getUrl(); allowed values 0,1,2; default 0
        $header = (int) $request->getQueryParams()['header'];
        $header = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($header, 0, 2, 0);

        // the URI to fetch data or header from
        $url = (string) $request->getQueryParams()['url'];
        if (!GeneralUtility::isValidUrl($url)) {
            throw new \InvalidArgumentException('No valid url passed!', 1580482805);
        }

        // fetch the requested data or header
        $fetchedData = GeneralUtility::getUrl($url, $header);

        // Fetch header data separately to get "Last-Modified" info
        if ($header === 0) {
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
        }

        // create response object
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        if ($fetchedData) {
            $response->getBody()->write($fetchedData);
            $response = $response->withHeader('Access-Control-Allow-Methods', 'GET');
            // temporally replaced by * in .htaccess
            // $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin') ? : '*');
            $response = $response->withHeader('Access-Control-Max-Age', '86400');
            $response = $response->withHeader('Content-Type', finfo_buffer(finfo_open(FILEINFO_MIME), $fetchedData));
        }
        if ($header === 0 && !empty($lastModified)) {
            $response = $response->withHeader('Last-Modified', $lastModified);
        }
        return $response;
    }
}