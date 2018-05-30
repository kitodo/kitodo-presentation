<?php
namespace Kitodo\Dlf\Plugins;

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
 * eID to fetch data from server for the plugin 'Pageview' of the 'dlf' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @copyright	Copyright (c) 2015, Alexander Bigga, SLUB Dresden
 * @package	TYPO3
 * @subpackage	dlf
 * @access	public
 */
class PageviewGeturlEid extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

    public $scriptRelPath = 'Classes/Plugins/PageviewGeturlEid.php';

    /**
     * The main method of the eID-Script
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	void
     */
    public function main($content = '', $conf = []) {

        $url = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('url');

        $includeHeader = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('header'), 0, 2, 0);

        // First we fetch header separately.
        $fetchedHeader = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, 2);

        if ($includeHeader == 0) {

            $fetchedData = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, $includeHeader);

        } else {

            $fetchedData = $fetchedHeader;

        }

        // Add some self calculated header tags.
        header('Last-Modified: '.gmdate("D, d M Y H:i:s").'GMT');

        header('Cache-Control: max-age=3600, must-revalidate');

        header('Content-Length: '.strlen($fetchedData));

        $fi = finfo_open(FILEINFO_MIME);

        header('Content-Type: '.finfo_buffer($fi, $fetchedData));

        // Overwrite tags in case already set
        $fetchedHeader = explode("\n", \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, 2));

        foreach ($fetchedHeader as $headerline) {

            if (stripos($headerline, 'Last-Modified:') !== FALSE) {

                header($headerline);

            }

        }

        echo $fetchedData;

    }

}

$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageviewGeturlEid::class);

$cObj->main();
