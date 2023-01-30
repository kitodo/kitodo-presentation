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

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ORCID API Profile class
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 **/
class Profile
{
    /**
     * This holds the logger
     *
     * @var LogManager
     * @access protected
     */
    protected $logger;

    /**
     * This holds the client
     *
     * @var Client
     * @access protected
     */
    protected $client;

    /**
     * The raw ORCID profile
     *
     * @var \SimpleXmlElement|false
     **/
    private $raw = null;

    /**
     * Constructs client instance
     *
     * @param string $orcid: the ORCID to search for
     *
     * @return void
     **/
    public function __construct($orcid)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->client = new Client($orcid, GeneralUtility::makeInstance(RequestFactory::class));
    }

    /**
     * Get the ORCID profile data
     *
     * @return array|false
     **/
    public function getData()
    {
        $this->getRaw('person');
        if (!empty($this->raw)) {
            $data = [];
            $data['address'] = $this->getAddress();
            $data['email'] = $this->getEmail();
            $data['fullName'] = $this->getFullName();
            return $data;
        } else {
            $this->logger->warning('No data found for given ORCID');
            return false;
        }
    }

    /**
     * Get the address
     *
     * @return string|false
     **/
    public function getAddress()
    {
        $this->getRaw('address');
        if (!empty($this->raw)) {
            $this->raw->registerXPathNamespace('address', 'http://www.orcid.org/ns/address');
            return (string) $this->raw->xpath('./address:address/address:country')[0];
        } else {
            $this->logger->warning('No address found for given ORCID');
            return false;
        }
    }

    /**
     * Get the email
     *
     * @return string|false
     **/
    public function getEmail()
    {
        $this->getRaw('email');
        if (!empty($this->raw)) {
            $this->raw->registerXPathNamespace('email', 'http://www.orcid.org/ns/email');
            return (string) $this->raw->xpath('./email:email/email:email')[0];
        } else {
            $this->logger->warning('No email found for given ORCID');
            return false;
        }
    }

    /**
     * Get the full name
     *
     * @return string|false
     **/
    public function getFullName()
    {
        $this->getRaw('personal-details');
        if (!empty($this->raw)) {
            $this->raw->registerXPathNamespace('personal-details', 'http://www.orcid.org/ns/personal-details');
            $givenNames = $this->raw->xpath('./personal-details:name/personal-details:given-names');
            $familyName = $this->raw->xpath('./personal-details:name/personal-details:family-name');
            return (string) $givenNames[0] . ' ' . (string) $familyName[0];
        } else {
            $this->logger->warning('No name found for given ORCID');
            return false;
        }
    }

    /**
     * Get the ORCID part of profile data for given endpoint
     *
     * @param string  $endpoint the shortname of the endpoint
     *
     * @return void
     **/
    protected function getRaw($endpoint)
    {
        $this->client->setEndpoint($endpoint);
        $data = $this->client->getData();
        if (!isset($this->raw) && $data != false) {
            $this->raw = Helper::getXmlFileAsString($data);
        }
    }
}
