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
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ORCID API Client class
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 **/
class Client
{
    /**
     * constants for API endpoint
     **/
    const HOSTNAME  = 'orcid.org';
    const VERSION   = '3.0';

    /**
     * This holds the logger
     *
     * @var LogManager
     * @access protected
     */
    protected $logger;

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
     * The ORCID ID to search for
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
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->orcid = $orcid;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Sets API endpoint
     *
     * @param string  $endpoint the shortname of the endpoint
     *
     * @return void
     */
    public function setEndpoint($endpoint) {
        $this->endpoint = $endpoint;
    }

    /**
     * Get the profile data
     *
     * @return object|bool
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
     * @return string
     **/
    private function getApiEndpoint()
    {
        $url  = 'https://' . $this->level . '.' . self::HOSTNAME;
        $url .= '/v' . self::VERSION . '/';
        $url .= $this->orcid;
        $url .= '/' . $this->endpoint;
        return $url;
    }
}
