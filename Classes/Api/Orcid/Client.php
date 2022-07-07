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

namespace Kitodo\Dlf\Api\Orcid;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * ORCID API Client class
 **/
class Client implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * constants for API endpoint
     **/
    const HOSTNAME  = 'orcid.org';
    const VERSION   = '3.0';

    /**
     * The ORCID API endpoint
     *
     * @var string
     **/
    private $endpoint = 'record';

    /**
     * The ORCID API access level
     *
     * @var string
     **/
    private $level = 'pub';

    /**
     * The login/registration page ORCID
     *
     * @var string
     **/
    private $orcid = null;

    /**
     * The request object
     *
     * @var RequestFactoryInterface
     **/
    private $requestFactory = null;

    /**
     * Constructs a new instance
     *
     * @param string $orcid: the ORCID to search for
     * @param RequestFactory $requestFactory a request object to inject
     * @return void
     **/
    public function __construct($orcid, RequestFactory $requestFactory)
    {
        $this->orcid = $orcid;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @param string  $endpoint the shortname of the endpoint
     */
    public function setEndpoint($endpoint) {
        $this->endpoint = $endpoint;
    }

    /**
     * Get the profile data
     *
     * @return object
     * @throws Exception
     **/
    public function getData()
    {
        $url = $this->getApiEndpoint();
        try {
            $response = $this->requestFactory->request($url);
        } catch (\Exception $e) {
            $this->logger->warning('Could not fetch data from URL "' . $url . '". Error: ' . $e->getMessage() . '.');
            return false;
        }
        return $response->getBody()->getContents();
    }

    /**
     * Creates the qualified API endpoint for retrieving the desired data
     *
     * @param string  $endpoint the shortname of the endpoint
     * @return string
     **/
    private function getApiEndpoint()
    {
        $url  = 'https://' . $this->level . '.' .  self::HOSTNAME;
        $url .= '/v' . self::VERSION . '/';
        $url .= '0000-0001-9483-5161';
        //$url .= $this->orcid;
        $url .= '/' . $this->endpoint;
        return $url;
    }
}
