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

namespace Kitodo\Dlf\Common;

use Ubl\Iiif\Tools\UrlReaderInterface;

/**
 * Implementation of Ubl\Iiif\Tools\UrlReaderInterface for the 'dlf' TYPO3 extension.
 * Allows the use of TYPO3 framework functions for loading remote documents in the
 * IIIF library.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class IiifUrlReader implements UrlReaderInterface
{
    /**
     * @access protected
     * @var IiifUrlReader Singleton instance of the class
     */
    protected static IiifUrlReader $instance;

    /**
     * @see UrlReaderInterface::getContent()
     */
    public function getContent($url)
    {
        // Use Helper::getUrl() instead of GeneralUtility::getUrl(), because the
        // latter falls back to file_get_contents() for URLs that are not
        // http(s), which would allow local files to be read. Helper::getUrl()
        // also validates the host, so that a document cannot make the server
        // fetch resources from internal services.
        $fileContents = Helper::getUrl((string) $url);
        if ($fileContents !== false) {
            return $fileContents;
        } else {
            return '';
        }
    }

    /**
     * Return a singleton instance.
     *
     * @access public
     *
     * @static
     *
     * @return IiifUrlReader
     */
    public static function getInstance(): IiifUrlReader
    {
        if (!isset(self::$instance)) {
            self::$instance = new IiifUrlReader();
        }
        return self::$instance;
    }
}
