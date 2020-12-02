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
        $output = [
            'documents' => [],
            'numFound' => 0
        ];
        // Get input parameters and decrypt core name.
        $parameters = $request->getParsedBody();
        $encrypted = (string) $parameters['encrypted'];
        $count = intval($parameters['start']);
        if (empty($encrypted)) {
            throw new \InvalidArgumentException('No valid parameter passed!', 1580585079);
        }

        $core = Helper::decrypt($encrypted);

        // Perform Solr query.
        $solr = Solr::getInstance($core);
        $fields = Solr::getFields();

        if ($solr->ready) {
            $query = $solr->service->createSelect();
            $query->setFields([$fields['id'], $fields['uid'], $fields['page']]);
            $query->setQuery($fields['fulltext'] . ':(' . Solr::escapeQuery((string) $parameters['q']) . ') AND ' . $fields['uid'] . ':' . intval($parameters['uid']));
            $query->setStart($count)->setRows(20);
            $hl = $query->getHighlighting();
            $hl->setFields([$fields['fulltext']]);
            $hl->setUseFastVectorHighlighter(true);
            var_dump($query);
            $results = $solr->service->select($query);
            var_dump($results);
            $output['numFound'] = $results->getNumFound();
            $highlighting = $results->getHighlighting();
            foreach ($results as $result) {
                $snippet = $highlighting->getResult($result->id)->getField($fields['fulltext']);
                $document = [
                    'id' => $result->id,
                    'uid' => $result->uid,
                    'page' => $result->page,
                    'snippet' => !empty($snippet) ? implode(' [...] ', $snippet) : ''
                ];
                $output['documents'][$count] = $document;
                $count++;
            }
        }
        // Create response object.
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write(json_encode($output));
        return $response;
    }
}
