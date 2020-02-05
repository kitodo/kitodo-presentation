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
 * eID search in document for plugin 'Search' of the 'dlf' extension
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class SearchInDocument
{
    /**
     * The main method of the eID script
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface JSON response of search suggestions
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
            $uid = (string)$parameters['uid'];
            $start = (string)$parameters['start'];
            $url = trim(Solr::getSolrUrl($core), '/') . '/select?wt=json&q=fulltext:(' . Solr::escapeQuery($query) . ')%20AND%20uid:' . $uid
                . '&hl=on&hl.fl=fulltext&fl=uid,id,page&hl.method=fastVector'
                . '&start=' . $start . '&rows=20';
            $output = GeneralUtility::getUrl($url);
        }

        // create response object
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write($output);
        return $response;
    }
}
