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

namespace Kitodo\Dlf\Tests\Functional\Api;

use \Exception;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use Phpoaipmh\ClientInterface;
use Phpoaipmh\Exception\OaipmhException;
use Phpoaipmh\Exception\MalformedResponseException;
use SimpleXMLElement;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Custom implementation of Phpoaipmh\ClientInterface such that Phpoaipmh\Endpoint can be used
 * to test the Typo3 OAI endpoint.
 *
 * See: https://github.com/caseyamcl/phpoaipmh/blob/master/src/ClientInterface.php
 */
class OaiPmhTypo3Client implements ClientInterface
{

    /**
     * The base url of the Typo3 server used for functional tests
     */
    private string $baseUrl;

    /**
     * The Typo3 page id of the OAI endpoint
     */
    private int $pageId;

    /**
     * A reference to the functional test case class such that internal Typo3 requests can be issued.
     */
    private FunctionalTestCase $functionalTestCase;

    /**
     * Whether to throw an OaipmhException if the OAI response contains error information.
     */
    private bool $throwError;

    /**
     * Initialize a OaiPmh client.
     *
     * @param string $baseUrl the base url of the Typo3 server used for functional tests
     * @param int $pageId the Typo3 page id of the OAI endpoint
     * @param FunctionalTestCase $funtionalTestCase reference to the functional test case in order to issue internal requests
     * @param bool $throwError whether to throw an OaiPmhException if the OAI response contains error information
     */
    public function __construct(
        string $baseUrl,
        int $pageId,
        FunctionalTestCase $functionalTestCase,
        bool $throwError = true
    ) {
        $this->baseUrl = $baseUrl;
        $this->pageId = $pageId;
        $this->functionalTestCase = $functionalTestCase;
        $this->throwError = $throwError;
    }

    /**
     * Issue a OaiPmh request for a given verb and options.
     *
     * @param $verb the verb as string
     * @param array $params additional options
     * @throws OaipmhException if there is an OaiPmh error and $throwError is true
     * @throws MalformedResponseException if the XML response cannot be parsed
     * @return SimpleXMLElement the parsed response as XML element
     */
    public function request($verb, array $params = [])
    {
        $request = (new InternalRequest($this->baseUrl))->withQueryParameters(
            array_merge([ 'id' => $this->pageId, 'verb' => $verb ], $params)
        );

        $response = $this->functionalTestCase->executeInternalRequest($request);

        try {
            $xml = new SimpleXMLElement((string) $response->getBody());
        } catch (Exception $e) {
            // ignore phpcs error "All output should be run through an escaping function, see WordPress security"
            // phpcs:ignore
            throw new MalformedResponseException(sprintf("Could not decode XML Response: %s", $e->getMessage()));
        }

        if ($this->throwError && isset($xml->error)) {
            $code = (string) $xml->error['code'];
            $msg  = (string) $xml->error;
            // ignore phpcs error "All output should be run through an escaping function, see WordPress security"
            // phpcs:ignore
            throw new OaipmhException($code, $msg);
        }

        return $xml;
    }

    /**
     * Empty implementation
     */
    public function getHttpAdapter()
    {
        return null;
    }
}
