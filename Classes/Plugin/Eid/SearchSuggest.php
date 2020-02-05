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
        $parameters = $request->getParsedBody();
        $encrypted = (string)$parameters['encrypted'];
        $hashed = (string)$parameters['hashed'];
        if (empty($encrypted) || empty($hashed)) {
            throw new \InvalidArgumentException('No valid parameter passed!', 1580585079);
        }
        $core = Helper::decrypt($encrypted, $hashed);

        $output = '';
        if (!empty($core)) {
            $query = (string)$parameters['q'];
            $url = trim(Solr::getSolrUrl($core), '/') . '/suggest/?wt=xml&q=' . Solr::escapeQuery($query);
            $output = GeneralUtility::getUrl($url);
        }

        // create response object
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write($output);
        return $response;
    }
}
