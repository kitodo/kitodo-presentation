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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Ubl\Iiif\Tools\UrlReaderInterface;

/**
 * Implementation of Ubl\Iiif\Tools\UrlReaderInterface for the 'dlf' TYPO3 extension.
 * Allows the use of TYPO3 framework functions for loading remote documents in the
 * IIIF library.
 *
 * @author Lutz Helm <helm@ub.uni-leipzig.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class IiifUrlReader implements UrlReaderInterface
{
    /**
     * Singleton instance of the class
     *
     * @access protected
     * @var IiifUrlReader
     */
    protected static $instance;

    /**
     *
     * {@inheritDoc}
     * @see \Ubl\Iiif\Tools\UrlReaderInterface::getContent()
     */
    public function getContent($url)
    {
        $fileContents = GeneralUtility::getUrl($url);
        if ($fileContents !== false) {
            return $fileContents;
        } else {
            return '';
        }
    }

    /**
     * Return a singleton instance.
     *
     * @static
     *
     * @return IiifUrlReader
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new IiifUrlReader();
        }
        return self::$instance;
    }
}
