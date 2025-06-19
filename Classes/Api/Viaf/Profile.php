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
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * VIAF API Profile class
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 **/
class Profile
{
    /**
     * @access private
     * @var Logger This holds the logger
     */
    protected Logger $logger;

    /**
     * @access private
     * @var Client This holds the client
     */
    private Client $client;

    /**
     * @access private
     * @var \SimpleXmlElement|false The raw VIAF profile or false if not found
     **/
    private \SimpleXmlElement|false $raw = false;

    /**
     * Constructs client instance
     *
     * @access public
     *
     * @param string $viaf the VIAF identifier of the profile
     *
     * @return void
     **/
    public function __construct(string $viaf)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->client = new Client($viaf, GeneralUtility::makeInstance(RequestFactory::class));
    }

    /**
     * Get the VIAF profile data
     *
     * @access public
     *
     * @return array|false
     **/
    public function getData(): array|false
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
     * @access public
     *
     * @return string|false
     **/
    public function getAddress(): string|false
    {
        $this->getRaw();
        if ($this->raw !== false && !empty($this->raw->asXML())) {
            return (string) $this->raw->xpath('./ns1:nationalityOfEntity/ns1:data/ns1:text')[0];
        } else {
            $this->logger->warning('No address found for given VIAF URL');
            return false;
        }
    }

    /**
     * Get the full name
     *
     * @access public
     *
     * @return string|false
     **/
    public function getFullName(): string|false
    {
        $this->getRaw();
        if ($this->raw !== false && !empty($this->raw->asXML())) {
            $rawName = $this->raw->xpath('./ns1:mainHeadings/ns1:data/ns1:text');
            $name = (string) $rawName[0];
            return trim(trim(trim($name), ','), '.');
        } else {
            $this->logger->warning('No name found for given VIAF URL');
            return false;
        }
    }

    /**
     * Get the VIAF raw profile data
     *
     * @access private
     *
     * @return void
     **/
    private function getRaw(): void
    {
        $data = $this->client->getData();
        if ($data != false) {
            $this->raw = Helper::getXmlFileAsString($data);
            if ($this->raw !== false && !empty($this->raw->asXML())) {
                $this->raw->registerXPathNamespace('ns1', 'http://viaf.org/viaf/terms#');
            }
        }
    }
}
