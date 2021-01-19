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
use Kitodo\Dlf\Common\Solr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * eID search suggestions for plugin 'Search' of the 'dlf' extension
 *
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class SearchSuggest
{
    /**
     * The main method of the eID script
     *
    *  @param ServerRequestInterface $request
     * @return ResponseInterface XML response of search suggestions
     */
    public function main(ServerRequestInterface $request)
    {
        $output = [];
        // Get input parameters and decrypt core name.
        $parameters = $request->getParsedBody();
        $encrypted = (string) $parameters['encrypted'];
        if (empty($encrypted)) {
            throw new \InvalidArgumentException('No valid parameter passed!', 1580585079);
        }
        $core = Helper::decrypt($encrypted);
        // Perform Solr query.
        $solr = Solr::getInstance($core);
        if ($solr->ready) {
            $query = $solr->service->createSelect();
            $query->setHandler('suggest');
            $query->setQuery(Solr::escapeQuery((string) $parameters['q']));
            $query->setRows(0);
            $results = $solr->service->select($query)->getResponse()->getBody();
            $result = json_decode($results);
            foreach ($result->spellcheck->suggestions as $suggestions) {
                if (is_object($suggestions)) {
                    foreach ($suggestions->suggestion as $suggestion) {
                        $output[] = $suggestion;
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
