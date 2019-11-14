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

namespace Kitodo\Dlf\Plugin\Eid;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * eID search suggestions for plugin 'Search' of the 'dlf' extension
 *
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class SearchSuggest extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Eid/SearchSuggest.php';

    /**
     * The main method of the eID script
     *
     * @access public
     *
     * @return string XML response of search suggestions
     */
    public function main()
    {
        if (
            GeneralUtility::_GP('encrypted') != ''
            && GeneralUtility::_GP('hashed') != ''
        ) {
            $core = Helper::decrypt(GeneralUtility::_GP('encrypted'), GeneralUtility::_GP('hashed'));
        }
        if (!empty($core)) {
            $url = trim(Solr::getSolrUrl($core), '/') . '/suggest/?wt=xml&q=' . Solr::escapeQuery(GeneralUtility::_GP('q'));
            $output = GeneralUtility::getUrl($url);
        }
        echo $output;
    }
}
