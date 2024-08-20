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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Common\Solr\SearchResult\ResultDocument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Search in document Middleware for plugin 'Search' of the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SearchInDocument implements MiddlewareInterface
{
    /**
     * This holds the solr instance
     *
     * @var \Kitodo\Dlf\Common\Solr\Solr
     * @access private
     */
    private $solr;

    /**
     * This holds the solr fields
     *
     * @var array
     * @access private
     */
    private $fields;

    /**
     * The process method of the middleware.
     *
     * @access public
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface JSON response of search suggestions
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        // Get input parameters and decrypt core name.
        $parameters = $request->getParsedBody();
        // Return if not this middleware
        if (!isset($parameters['middleware']) || ($parameters['middleware'] != 'dlf/search-in-document')) {
            return $response;
        }

        $encrypted = (string) $parameters['encrypted'];
        if (empty($encrypted)) {
            throw new \InvalidArgumentException('No valid parameter passed: ' . $parameters['middleware'] . '  ' . $parameters['encrypted'] . '!', 1580585079);
        }

        $output = [
            'documents' => [],
            'numFound' => 0
        ];

        $core = Helper::decrypt($encrypted);

        // Perform Solr query.
        $this->solr = Solr::getInstance($core);
        $this->fields = Solr::getFields();

        if ($this->solr->ready) {
            $result = $this->executeSolrQuery($parameters);
            /** @scrutinizer ignore-call */
            $output['numFound'] = $result->getNumFound(); // @phpstan-ignore-line
            $data = $result->getData();
            $highlighting = $data['ocrHighlighting'];

            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByPageId((int) $parameters['pid']);

            // @phpstan-ignore-next-line
            foreach ($result as $record) {
                $resultDocument = new ResultDocument($record, $highlighting, $this->fields);

                $url = (string) $site->getRouter()->generateUri(
                    $parameters['pid'],
                    [
                        'tx_dlf[id]' => !empty($resultDocument->getUid()) ? $resultDocument->getUid() : $parameters['uid'],
                        'tx_dlf[page]' => $resultDocument->getPage(),
                        'tx_dlf[highlight_word]' => $parameters['q']
                    ]
                );

                $document = [
                    'id' => $resultDocument->getId(),
                    'uid' => !empty($resultDocument->getUid()) ? $resultDocument->getUid() : $parameters['uid'],
                    'page' => $resultDocument->getPage(),
                    'snippet' => $resultDocument->getSnippets(),
                    'highlight' => $resultDocument->getHighlightsIds(),
                    'url' => $url
                ];
                $output['documents'][] = $document;
            }
        }

        // Create response object.
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write(json_encode($output));
        return $response;
    }

    /**
     * Execute SOLR query.
     *
     * @access private
     *
     * @param array $parameters array of query parameters
     *
     * @return \Solarium\Core\Query\Result\ResultInterface result
     */
    private function executeSolrQuery($parameters)
    {
        $query = $this->solr->service->createSelect();
        $query->setFields([$this->fields['id'], $this->fields['uid'], $this->fields['page']]);
        $query->setQuery($this->getQuery($parameters));
        $query->setStart(intval($parameters['start']))->setRows(20);
        $query->addSort($this->fields['page'], $query::SORT_ASC);
        $query->getHighlighting();
        $solrRequest = $this->solr->service->createRequest($query);

        // it is necessary to add the custom parameters to the request
        // because query object doesn't allow custom parameters

        // field for which highlighting is going to be performed,
        // is required if you want to have OCR highlighting
        $solrRequest->addParam('hl.ocr.fl', $this->fields['fulltext']);
         // return the coordinates of highlighted search as absolute coordinates
        $solrRequest->addParam('hl.ocr.absoluteHighlights', 'on');
        // max amount of snippets for a single page
        $solrRequest->addParam('hl.snippets', '40');
        // we store the fulltext on page level and can disable this option
        $solrRequest->addParam('hl.ocr.trackPages', 'off');

        $response = $this->solr->service->executeRequest($solrRequest);
        return $this->solr->service->createResult($query, $response);
    }

    /**
     * Build SOLR query for given fields and parameters.
     *
     * @access private
     *
     * @param array $parameters parsed from request body
     *
     * @return string SOLR query
     */
    private function getQuery(array $parameters): string
    {
        return $this->fields['fulltext'] . ':(' . Solr::escapeQuery((string) $parameters['q']) . ') AND ' . $this->fields['uid'] . ':' . $this->getUid($parameters['uid']);
    }

    /**
     * Check if uid is number, if yes convert it to int,
     * otherwise leave uid not changed.
     *
     * @access private
     *
     * @param string $uid of the document
     *
     * @return int|string uid of the document
     */
    private function getUid(string $uid)
    {
        return is_numeric($uid) ? intval($uid) : $uid;
    }
}
