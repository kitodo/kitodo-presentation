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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * eID-script helper to fetch data from Javascript via server
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @copyright	Copyright (c) 2015, Alexander Bigga, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_geturl_eid extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	/**
	 *
	 */
	public $cObj;


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
	public function main($content = '', $conf = array ()) {

		$this->cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

		$this->extKey = 'dlf';

		$this->scriptRelPath = 'plugins/pageview/class.tx_dlf_geturl_eid.php';

		$url = GeneralUtility::_GP('url');
    $includeHeader = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(GeneralUtility::_GP('header'), 0, 2, 0);

		// first we fetch header separately
		$fetchedHeader = GeneralUtility::getUrl($url, 2);

		if ($includeHeader == 0) {

			$fetchedData = GeneralUtility::getUrl($url, $includeHeader);

		} else {

			$fetchedData = $fetchedHeader;

		}

		// add some self calculated header tags
		header('Last-Modified: ' . gmdate( "D, d M Y H:i:s" ) . 'GMT');
		header('Cache-Control: max-age=3600, must-revalidate');
		header('Content-Length: '.strlen($fetchedData));
		$fi = finfo_open(FILEINFO_MIME);
		header('Content-Type: ' . finfo_buffer($fi, $fetchedData));

		// take some tags from request header and overwrite in case already set
		$fetchedHeader = explode("\n", GeneralUtility::getUrl($url, 2));

		foreach ($fetchedHeader as $headerline) {
			if (stripos($headerline, 'Last-Modified:') !== FALSE) {
				header($headerline);
			}
		}

		echo $fetchedData;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_geturl_eid.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pageview/class.tx_dlf_geturl_eid.php']);
}

$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_geturl_eid');

$cObj->main();
