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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/AudioPlayer.php', '_audioplayer', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Basket.php', '_basket', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Calendar.php', '_calendar', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Collection.php', '_collection', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Feeds.php', '_feeds', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/ListView.php', '_listview', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Metadata.php', '_metadata', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Navigation.php', '_navigation', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/OaiPmh.php', '_oaipmh', 'list_type', FALSE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/PageGrid.php', '_pagegrid', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/PageView.php', '_pageview', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Search.php', '_search', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Statistics.php', '_statistics', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/TableOfContents.php', '_tableofcontents', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Toolbox.php', '_toolbox', 'list_type', TRUE);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Validator.php', '_validator', 'list_type', FALSE);

// Register tools for toolbox plugin.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Tools/FulltextTool.php', '_toolsFulltext', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsFulltext'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsFulltext';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Tools/ImageDownloadTool.php', '_toolsImageDownload', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImageDownload'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsImageDownload';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Tools/ImageManipulationTool.php', '_toolsImageManipulation', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImageManipulation'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsImageManipulation';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugins/Tools/PdfDownloadTool.php', '_toolsPdfDownload', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsPdfDownload'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsPdfDownload';

// Register hooks.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:'.$_EXTKEY.'/Classes/Hooks/DataHandler.php:DataHandler';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:'.$_EXTKEY.'/Classes/Hooks/DataHandler.php:DataHandler';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Common/Document.php']['hookClass'][] = 'EXT:'.$_EXTKEY.'/Classes/Hooks/KitodoProductionHacks.php:KitodoProductionHacks';

// Register command line scripts.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = [
    function () {
        $SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Cli\CommandLineIndexer::class);
        $SOBE->main();
    },
    '_CLI_dlf'
];

// Register AJAX eID handlers.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_suggest'] = \Kitodo\Dlf\Plugins\Eid\SearchSuggest::class.'::main';

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_pageview_proxy'] = \Kitodo\Dlf\Plugins\Eid\PageViewProxy::class.'::main';

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

        $hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Common\DocumentTypeCheck::class);

        return ($hook->getDocType() === $type);

    }

}
