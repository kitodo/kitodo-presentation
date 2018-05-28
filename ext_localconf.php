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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Audioplayer.php', '_audioplayer', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Basket.php', '_basket', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Collection.php', '_collection', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Feeds.php', '_feeds', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Listview.php', '_listview', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Metadata.php', '_metadata', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Navigation.php', '_navigation', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Newspaper.php', '_newspaper', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Oai.php', '_oai', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Pagegrid.php', '_pagegrid', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Pageview.php', '_pageview', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Search.php', '_search', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Statistics.php', '_statistics', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Toc.php', '_toc', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Toolbox.php', '_toolbox', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Validator.php', '_validator', 'list_type', FALSE);

// Register tools for toolbox plugin.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Tools/FulltextTool.php', '_toolsFulltext', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsFulltext'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsFulltext';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Tools/ImagedownloadTool.php', '_toolsImagedownload', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImagedownload'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsImagedownload';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Tools/ImagemanipulationTool.php', '_toolsImagemanipulation', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImagemanipulation'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsImagemanipulation';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Tools/PdfTool.php', '_toolsPdf', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsPdf'] = 'LLL:EXT:dlf/locallang.xml:tx_dlf_toolbox.toolsPdf';

// Register hooks.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:'.$_EXTKEY.'/Classes/Hooks/DataHandler.php:DataHandler';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:'.$_EXTKEY.'/Classes/Hooks/DataHandler.php:DataHandler';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Common/Document.php']['hookClass'][] = 'EXT:'.$_EXTKEY.'/Classes/Hooks/KitodoHacks.php:KitodoHacks';

// Register command line scripts.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = [
    function () {
        $SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Cli\CommandLineIndexer::class);
        $SOBE->main();
    },
    '_CLI_dlf'
];

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

        $obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Common\DocumentTypeChecker::class);

        return ($obj->getDocType() === $type);

    }

}
