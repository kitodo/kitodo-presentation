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

/**
 * eID search in document for plugin 'Search' of the 'dlf' extension
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_search_in_document_eid extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

    public $scriptRelPath = 'plugins/toolbox/tools/searchindocument/class.tx_dlf_search_in_document.php';

    /**
     * The main method of the PlugIn
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	string JSON response of search suggestions
     */
    public function main($content = '', $conf = array ()) {

        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted') != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed') != '') {

            $core = tx_dlf_helper::decrypt(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('encrypted'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('hashed'));

        }

        if (!empty($core)) {

            $url = trim(tx_dlf_solr::getSolrUrl($core), '/').'/select?wt=json&q='.urlencode('fulltext:('.tx_dlf_solr::escapeQuery(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('q'))).')%20AND%20uid:'.\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('uid')
              .'&hl=on&hl.fl=fulltext&fl=uid,id,page&hl.method=fastVector'
              .'&start='.\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('start').'&rows=20';

            $output = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url);
        }

        echo $output;
    }

}

$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_search_in_document_eid');

$cObj->main();
