<?php
namespace Kitodo\Dlf\Plugins\Eid;

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
 * eID search suggestions for plugin 'Search' of the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	dlf
 * @access	public
 */
class SearchSuggest extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

    public $scriptRelPath = 'Classes/Plugins/Eid/SearchSuggest.php';

    /**
     * The main method of the eID script
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	string      XML response of search suggestions
     */
    public function main($content = '', $conf = array ()) {

        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted') != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed') != '') {

            $core = Helper::decrypt(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed'));

        }

        if (!empty($core)) {

            $url = trim(Solr::getSolrUrl($core), '/').'/suggest/?wt=xml&q='.Solr::escapeQuery(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('q'));

            if ($stream = fopen($url, 'r')) {

                $content .= stream_get_contents($stream);

                fclose($stream);

            }

        }

        echo $content;

    }

}
