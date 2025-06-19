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

use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ORCID API Client class
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 **/
class Client
{
    /**
     * @var string constant for API hostname
     **/
    const HOSTNAME = 'orcid.org';

    /**
     * @var string constant for API version
     **/
    const VERSION = '3.0';

    /**
     * @access protected
     * @var Logger This holds the logger
     */
    protected Logger $logger;

    /**
     * @access private
     * @var string The ORCID API endpoint
     **/
    private string $endpoint = 'record';

    /**
     * @access private
     * @var string The ORCID API access level
     **/
    private string $level = 'pub';

    /**
     * @access private
     * @var string The ORCID ID to search for
     **/
    private string $orcid;

    /**
     * @access private
     * @var RequestFactoryInterface The request object
     **/
    private RequestFactoryInterface $requestFactory;

    /**
     * Constructs a new instance
     *
     * @access public
     *
     * @param string $orcid the ORCID to search for
     * @param RequestFactory $requestFactory a request object to inject
     *
     * @return void
     **/
    public function __construct(string $orcid, RequestFactory $requestFactory)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->orcid = $orcid;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Sets API endpoint
     *
     * @access public
     *
     * @param string $endpoint the shortname of the endpoint
     *
     * @return void
     */
    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Get the profile data
     *
     * @access public
     *
     * @return object|bool
     **/
    public function getData(): object|bool
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
     * @access private
     *
     * @return string
     **/
    private function getApiEndpoint(): string
    {
        $url  = 'https://' . $this->level . '.' . self::HOSTNAME;
        $url .= '/v' . self::VERSION . '/';
        $url .= $this->orcid;
        $url .= '/' . $this->endpoint;
        return $url;
    }
}
