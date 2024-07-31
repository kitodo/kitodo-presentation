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

namespace Kitodo\Dlf\Middleware;

use Kitodo\Dlf\Common\Solr\Solr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Search suggestions Middleware for plugin 'Search' of the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SearchSuggest implements MiddlewareInterface
{
    /**
     * The process method of the middleware.
     *
     * @access public
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface XML response of search suggestions
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        // Get input parameters and decrypt core name.
        $parameters = $request->getParsedBody();
        // Return if not this middleware
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/search-suggest')) {
            return $response;
        }

        $output = [];
        $solrCore = (string) $parameters['solrcore'];
        $uHash = (string) $parameters['uHash'];
        if (hash_equals(GeneralUtility::hmac((string) (new Typo3Version()) . Environment::getExtensionsPath(), 'SearchSuggest'), $uHash) === false) {
            throw new \InvalidArgumentException('No valid parameter passed!', 1580585079);
        }
        // Perform Solr query.
        $solr = Solr::getInstance($solrCore);
        if ($solr->ready) {
            $query = $solr->service->createSuggester();
            $query->setQuery(Solr::escapeQuery((string) $parameters['q']));
            $query->setCount(10);
            $results = $solr->service->suggester($query);
            foreach ($results as $termResult) {
                foreach ($termResult as $termSuggestions) {
                    $suggestions = $termSuggestions->getSuggestions();
                    foreach ($suggestions as $suggestion) {
                        $output[] = $suggestion['term'];
                    }
                }
            }
        }

        // Create response object.
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write(json_encode($output));
        return $response;
    }
}
