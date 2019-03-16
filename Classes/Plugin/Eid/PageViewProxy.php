<?php
namespace Kitodo\Dlf\Plugin\Eid;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * eID image proxy for plugin 'Page View' of the 'dlf' extension
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class PageViewProxy extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
    public $scriptRelPath = 'Classes/Plugin/Eid/PageViewProxy.php';

    /**
     * The main method of the eID script
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string
     */
    public function main($content = '', $conf = []) {
        $this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        $header = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('header');
        $url = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('url');
        $fetchedData = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($header, 0, 2, 0));
        // Add some header tags
        header('Last-Modified: '.gmdate("D, d M Y H:i:s").'GMT');
        header('Cache-Control: max-age=3600, must-revalidate');
        header('Content-Length: '.strlen($fetchedData));
        header('Content-Type: '.finfo_buffer(finfo_open(FILEINFO_MIME), $fetchedData));
        // Get last modified date from request header
        $fetchedHeader = explode("\n", \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, 2));
        foreach ($fetchedHeader as $headerline) {
            if (stripos($headerline, 'Last-Modified:') !== FALSE) {
                header($headerline);
                break;
            }
        }
        echo $fetchedData;
    }
}
