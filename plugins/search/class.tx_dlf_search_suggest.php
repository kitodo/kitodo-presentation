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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;

/**
 * Search suggestions for the plugin 'DLF: Search' of the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_search_suggest extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

    public $scriptRelPath = 'plugins/search/class.tx_dlf_search_suggest.php';

    /**
     * The main method of the PlugIn
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	void
     */
    public function main($content = '', $conf = []) {

        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted') != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed') != '') {

            $core = Helper::decrypt(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed'));

        }

        if (!empty($core)) {

            $url = trim(Solr::getSolrUrl($core), '/').'/suggest/?q='.Solr::escapeQuery(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('q'));

            if ($stream = fopen($url, 'r')) {

                $content .= stream_get_contents($stream);

                fclose($stream);

            }

        }

        echo $content;

    }

}

$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_search_suggest');

$cObj->main();
