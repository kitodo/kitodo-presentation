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
            $query->setQuery($this->getQuery($fields, $parameters));
            $query->setStart($count)->setRows(20);
            $hl = $query->getHighlighting();
            $solrRequest = $solr->service->createRequest($query);
            // it is necessary to add this custom parameter to request
            // query object doesn't allow custom parameters
            $solrRequest->addParam('hl.ocr.fl', $fields['fulltext']);
            $response = $solr->service->executeRequest($solrRequest);
            $result = $solr->service->createResult($query, $response);
            $output['numFound'] = $result->getNumFound();
            $data = $result->getData();
            $highlighting = $data['ocrHighlighting'];
            foreach ($result as $record) {
                $snippets = $highlighting[$record[$fields['id']]][$fields['fulltext']]['snippets'];
                $snippetArray = array();
                foreach ($snippets as $snippet) {
                    array_push($snippetArray, $snippet['text']);
                }

                $document = [
                    'id' => $record[$fields['id']],
                    'uid' => $parameters['uid'],
                    'page' => $record[$fields['page']],
                    'snippet' => !empty($snippetArray) ? implode(' [...] ', $snippetArray) : ''
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

    private function getQuery($fields, $parameters) {
        return $fields['fulltext'] . ':(' . Solr::escapeQuery((string) $parameters['q']) . ') AND ' . $fields['uid'] . ':' . intval($parameters['uid']);
    }

    private function getUid($uid) {
        return intval($uid) > 0  ? intval($uid) : $uid;
    }
}
