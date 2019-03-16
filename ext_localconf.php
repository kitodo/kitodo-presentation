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
// Define constants.
if (!defined('DEVLOG_SEVERITY_OK')) {
    define ('DEVLOG_SEVERITY_OK', -1);
}
if (!defined('DEVLOG_SEVERITY_INFO')) {
    define ('DEVLOG_SEVERITY_INFO', 0);
}
if (!defined('DEVLOG_SEVERITY_NOTICE')) {
    define ('DEVLOG_SEVERITY_NOTICE', 1);
}
if (!defined('DEVLOG_SEVERITY_WARNING')) {
    define ('DEVLOG_SEVERITY_WARNING', 2);
}
if (!defined('DEVLOG_SEVERITY_ERROR')) {
    define ('DEVLOG_SEVERITY_ERROR', 3);
}
// Register plugins.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/AudioPlayer.php', '_audioplayer', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Basket.php', '_basket', 'list_type', FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Calendar.php', '_calendar', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Collection.php', '_collection', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Feeds.php', '_feeds', 'list_type', FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/ListView.php', '_listview', 'list_type', FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Metadata.php', '_metadata', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Navigation.php', '_navigation', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/OaiPmh.php', '_oaipmh', 'list_type', FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/PageGrid.php', '_pagegrid', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/PageView.php', '_pageview', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Search.php', '_search', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Statistics.php', '_statistics', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/TableOfContents.php', '_tableofcontents', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Toolbox.php', '_toolbox', 'list_type', TRUE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Validator.php', '_validator', 'list_type', FALSE);
// Register tools for toolbox plugin.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'] = [];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Tools/FulltextTool.php', '_toolsFulltext', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsFulltext'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsFulltext';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Tools/ImageDownloadTool.php', '_toolsImageDownload', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImageDownload'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsImageDownload';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Tools/ImageManipulationTool.php', '_toolsImageManipulation', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsImageManipulation'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsImageManipulation';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Tools/PdfDownloadTool.php', '_toolsPdfDownload', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_toolsPdfDownload'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.toolsPdfDownload';
// Register hooks.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Common/Document.php']['hookClass'][] = \Kitodo\Dlf\Hooks\KitodoProductionHacks::class;
// Register command line scripts.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = [
    function () {
        $SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Cli\CommandLineIndexer::class);
        $SOBE->main();
    },
    '_cli_dlf'
];
// Register AJAX eID handlers.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_suggest'] = \Kitodo\Dlf\Plugin\Eid\SearchSuggest::class.'::main';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_pageview_proxy'] = \Kitodo\Dlf\Plugin\Eid\PageViewProxy::class.'::main';
// Register Typoscript user function.
if (TYPO3_MODE === 'FE') {
    /**
     * docTypeCheck user function to use in Typoscript
     * @example [userFunc = user_dlf_docTypeCheck($type)]
     *
     * @access public
     *
     * @param string $type: document type string to test for
     *
     * @return boolean TRUE if document type matches, FALSE if not
     */
    function user_dlf_docTypeCheck($type) {
        $hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Common\DocumentTypeCheck::class);
        return ($hook->getDocType() === $type);
    }
}
