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

namespace Kitodo\Dlf\Api\Viaf;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * VIAF API Profile class
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
     * The raw VIAF profile
     *
     * @var \SimpleXmlElement|false
     **/
    private $raw = null;

    /**
     * Constructs client instance
     *
     * @param string $viaf: the VIAF identifier of the profile
     *
     * @return void
     **/
    public function __construct($viaf)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->client = new Client($viaf, GeneralUtility::makeInstance(RequestFactory::class));
    }

    /**
     * Get the VIAF profile data
     *
     * @return array|false
     **/
    public function getData()
    {
        $this->getRaw();
        if (!empty($this->raw)) {
            $data = [];
            $data['address'] = $this->getAddress();
            $data['fullName'] = $this->getFullName();
            return $data;
        } else {
            $this->logger->warning('No data found for given VIAF URL');
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
        $this->getRaw();
        if (!empty($this->raw->asXML())) {
            return (string) $this->raw->xpath('./ns1:nationalityOfEntity/ns1:data/ns1:text')[0];
        } else {
            $this->logger->warning('No address found for given VIAF URL');
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
        $this->getRaw();
        if (!empty($this->raw->asXML())) {
            $rawName = $this->raw->xpath('./ns1:mainHeadings/ns1:data/ns1:text');
            $name = (string) $rawName[0];
            $name = trim(trim(trim($name), ','), '.');
            return $name;
        } else {
            $this->logger->warning('No name found for given VIAF URL');
            return false;
        }
    }

    /**
     * Get the VIAF raw profile data
     *
     * @return void
     **/
    protected function getRaw()
    {
        $data = $this->client->getData();
        if (!isset($this->raw) && $data != false) {
            $this->raw = Helper::getXmlFileAsString($data);
            $this->raw->registerXPathNamespace('ns1', 'http://viaf.org/viaf/terms#');
        }
    }
}
