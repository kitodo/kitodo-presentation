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

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Register plugins.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/audioplayer/class.tx_dlf_audioplayer.php', '_audioplayer', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/basket/class.tx_dlf_basket.php', '_basket', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/collection/class.tx_dlf_collection.php', '_collection', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/feeds/class.tx_dlf_feeds.php', '_feeds', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/listview/class.tx_dlf_listview.php', '_listview', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/metadata/class.tx_dlf_metadata.php', '_metadata', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/navigation/class.tx_dlf_navigation.php', '_navigation', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/newspaper/class.tx_dlf_newspaper.php', '_newspaper', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/oai/class.tx_dlf_oai.php', '_oai', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/pagegrid/class.tx_dlf_pagegrid.php', '_pagegrid', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/pageview/class.tx_dlf_pageview.php', '_pageview', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/search/class.tx_dlf_search.php', '_search', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/statistics/class.tx_dlf_statistics.php', '_statistics', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/toc/class.tx_dlf_toc.php', '_toc', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/toolbox/class.tx_dlf_toolbox.php', '_toolbox', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/validator/class.tx_dlf_validator.php', '_validator', 'list_type', FALSE);

// Register tools for toolbox plugin.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php', '_toolsFulltext', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsFulltext'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsFulltext';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/toolbox/tools/imagemanipulation/class.tx_dlf_toolsImagemanipulation.php', '_toolsImagemanipulation', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImagemanipulation'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsImagemanipulation';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php', '_toolsPdf', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsPdf'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsPdf';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'plugins/toolbox/tools/imagedownload/class.tx_dlf_toolsImagedownload.php', '_toolsImagedownload', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImagedownload'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsImagedownload';

// Register hooks.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:'.$_EXTKEY.'/hooks/class.tx_dlf_tcemain.php:tx_dlf_tcemain';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:'.$_EXTKEY.'/hooks/class.tx_dlf_tcemain.php:tx_dlf_tcemain';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/common/class.tx_dlf_document.php']['hookClass'][] = 'EXT:'.$_EXTKEY.'/hooks/class.tx_dlf_hacks.php:tx_dlf_hacks';

// Register command line scripts.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array ('EXT:'.$_EXTKEY.'/cli/class.tx_dlf_cli.php', '_CLI_dlf');

// Register AJAX eID handlers.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_suggest'] = 'EXT:'.$_EXTKEY.'/plugins/search/class.tx_dlf_search_suggest.php';

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_geturl_eid'] = 'EXT:'.$_EXTKEY.'/plugins/pageview/class.tx_dlf_geturl_eid.php';

if (TYPO3_MODE === 'FE') {

    /**
     * docTypeCheck user function to use in Typoscript
     * @example [userFunc = user_dlf_docTypeCheck($type)]
     *
     * @access	public
     *
     * @param	string		$type: document type string to test for
     *
     * @return	boolean		TRUE if document type matches, FALSE if not
     */
    function user_dlf_docTypeCheck($type) {

        $hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_doctype');

        return ($hook->getDocType() === $type);

    }

}
