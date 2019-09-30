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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * eID search in document for plugin 'Search' of the 'dlf' extension
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class SearchInDocument extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
    public $scriptRelPath = 'Classes/Plugin/Eid/SearchInDocument.php';

    /**
     * The main method of the eID script
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string JSON response of search suggestions
     */
    public function main($content = '', $conf = []) {
        if (GeneralUtility::_GP('encrypted') != ''
            && GeneralUtility::_GP('hashed') != '') {
            $core = Helper::decrypt(GeneralUtility::_GP('encrypted'), GeneralUtility::_GP('hashed'));
        }
        if (!empty($core)) {
            $url = trim(Solr::getSolrUrl($core), '/').'/select?wt=json&q=fulltext:('.Solr::escapeQuery(GeneralUtility::_GP('q')).')%20AND%20uid:'.GeneralUtility::_GP('uid')
              .'&hl=on&hl.fl=fulltext&fl=uid,id,page&hl.method=fastVector'
              .'&start='.GeneralUtility::_GP('start').'&rows=20';
            $output = GeneralUtility::getUrl($url);
        }
        echo $output;
    }
}
