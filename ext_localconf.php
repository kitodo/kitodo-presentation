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
    die('Access denied.');
}
// Define constants.
if (!defined('DEVLOG_SEVERITY_OK')) {
    define('DEVLOG_SEVERITY_OK', -1);
}
if (!defined('DEVLOG_SEVERITY_INFO')) {
    define('DEVLOG_SEVERITY_INFO', 0);
}
if (!defined('DEVLOG_SEVERITY_NOTICE')) {
    define('DEVLOG_SEVERITY_NOTICE', 1);
}
if (!defined('DEVLOG_SEVERITY_WARNING')) {
    define('DEVLOG_SEVERITY_WARNING', 2);
}
if (!defined('DEVLOG_SEVERITY_ERROR')) {
    define('DEVLOG_SEVERITY_ERROR', 3);
}
// Register plugins without addPItoST43() as this is not working with TYPO3 9.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
    '
plugin.tx_dlf_audioplayer = USER
plugin.tx_dlf_audioplayer {
    userFunc = Kitodo\Dlf\Plugin\AudioPlayer->main
}
tt_content.list.20.dlf_audioplayer < plugin.tx_dlf_audioplayer

plugin.tx_dlf_videoplayer = USER
plugin.tx_dlf_videoplayer {
    userFunc = Kitodo\Dlf\Plugin\VideoPlayer->main
}
tt_content.list.20.dlf_videoplayer < plugin.tx_dlf_videoplayer

plugin.tx_dlf_basket = USER_INT
plugin.tx_dlf_basket {
    userFunc = Kitodo\Dlf\Plugin\Basket->main
}
tt_content.list.20.dlf_basket < plugin.tx_dlf_basket

plugin.tx_dlf_calendar = USER
plugin.tx_dlf_calendar {
    userFunc = Kitodo\Dlf\Plugin\Calendar->main
}
tt_content.list.20.dlf_calendar < plugin.tx_dlf_calendar

plugin.tx_dlf_collection = USER
plugin.tx_dlf_collection {
    userFunc = Kitodo\Dlf\Plugin\Collection->main
}
tt_content.list.20.dlf_collection < plugin.tx_dlf_collection

plugin.tx_dlf_feeds = USER_INT
plugin.tx_dlf_feeds {
    userFunc = Kitodo\Dlf\Plugin\Feeds->main
}
tt_content.list.20.dlf_feeds < plugin.tx_dlf_feeds

plugin.tx_dlf_listview = USER_INT
plugin.tx_dlf_listview {
    userFunc = Kitodo\Dlf\Plugin\ListView->main
}
tt_content.list.20.dlf_listview < plugin.tx_dlf_listview

plugin.tx_dlf_metadata = USER
plugin.tx_dlf_metadata {
    userFunc = Kitodo\Dlf\Plugin\Metadata->main
}
tt_content.list.20.dlf_metadata < plugin.tx_dlf_metadata

plugin.tx_dlf_navigation = USER
plugin.tx_dlf_navigation {
    userFunc = Kitodo\Dlf\Plugin\Navigation->main
}
tt_content.list.20.dlf_navigation < plugin.tx_dlf_navigation

plugin.tx_dlf_oaipmh = USER_INT
plugin.tx_dlf_oaipmh {
    userFunc = Kitodo\Dlf\Plugin\OaiPmh->main
}
tt_content.list.20.dlf_oaipmh < plugin.tx_dlf_oaipmh

plugin.tx_dlf_pagegrid = USER
plugin.tx_dlf_pagegrid {
    userFunc = Kitodo\Dlf\Plugin\PageGrid->main
}
tt_content.list.20.dlf_pagegrid < plugin.tx_dlf_pagegrid

plugin.tx_dlf_pageview = USER
plugin.tx_dlf_pageview {
    userFunc = Kitodo\Dlf\Plugin\PageView->main
}
tt_content.list.20.dlf_pageview < plugin.tx_dlf_pageview

plugin.tx_dlf_search = USER
plugin.tx_dlf_search {
    userFunc = Kitodo\Dlf\Plugin\Search->main
}
tt_content.list.20.dlf_search < plugin.tx_dlf_search

plugin.tx_dlf_statistics = USER
plugin.tx_dlf_statistics {
    userFunc = Kitodo\Dlf\Plugin\Statistics->main
}
tt_content.list.20.dlf_statistics < plugin.tx_dlf_statistics

plugin.tx_dlf_tableofcontents = USER
plugin.tx_dlf_tableofcontents {
    userFunc = Kitodo\Dlf\Plugin\TableOfContents->main
}
tt_content.list.20.dlf_tableofcontents < plugin.tx_dlf_tableofcontents

plugin.tx_dlf_toolbox = USER
plugin.tx_dlf_toolbox {
    userFunc = Kitodo\Dlf\Plugin\Toolbox->main
}
tt_content.list.20.dlf_toolbox < plugin.tx_dlf_toolbox

plugin.tx_dlf_validator = USER_INT
plugin.tx_dlf_validator {
    userFunc = Kitodo\Dlf\Plugin\Validator->main
}
tt_content.list.20.dlf_validator < plugin.tx_dlf_validator

plugin.tx_dlf_fulltexttool = USER
plugin.tx_dlf_fulltexttool {
    userFunc = Kitodo\Dlf\Plugin\Tools\FulltextTool->main
}
tt_content.list.20.dlf_fulltexttool < plugin.tx_dlf_fulltexttool

plugin.tx_dlf_annotationtool = USER
plugin.tx_dlf_annotationtool {
    userFunc = Kitodo\Dlf\Plugin\Tools\AnnotationTool->main
}
tt_content.list.20.dlf_annotationtool < plugin.tx_dlf_annotationtool

plugin.tx_dlf_imagedownloadtool = USER
plugin.tx_dlf_imagedownloadtool {
    userFunc = Kitodo\Dlf\Plugin\Tools\ImageDownloadTool->main
}
tt_content.list.20.dlf_imagedownloadtool < plugin.tx_dlf_imagedownloadtool

plugin.tx_dlf_imagemanipulationtool = USER
plugin.tx_dlf_imagemanipulationtool {
    userFunc = Kitodo\Dlf\Plugin\Tools\ImageManipulationTool->main
}
tt_content.list.20.dlf_imagemanipulationtool < plugin.tx_dlf_imagemanipulationtool

plugin.tx_dlf_pdfdownloadtool = USER
plugin.tx_dlf_pdfdownloadtool {
    userFunc = Kitodo\Dlf\Plugin\Tools\PdfDownloadTool->main
}
tt_content.list.20.dlf_pdfdownloadtool < plugin.tx_dlf_pdfdownloadtool

plugin.tx_dlf_searchindocumenttool = USER
plugin.tx_dlf_searchindocumenttool {
    userFunc = Kitodo\Dlf\Plugin\Tools\SearchInDocumentTool->main
}
tt_content.list.20.dlf_searchindocumenttool < plugin.tx_dlf_searchindocumenttool
'
);
// Register plugin icons.
$iconArray = [
    'tx-dlf-audioplayer' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-audioplayer.svg',
    'tx-dlf-basket' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-basket.svg',
    'tx-dlf-calendar' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-calendar.svg',
    'tx-dlf-collection' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-collection.svg',
    'tx-dlf-feeds' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-feeds.svg',
    'tx-dlf-metadata' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-metadata.svg',
    'tx-dlf-navigation' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-navigation.svg',
    'tx-dlf-oaipmh' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-oaipmh.svg',
    'tx-dlf-pagegrid' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-pagegrid.svg',
    'tx-dlf-pageview' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-pageview.svg',
    'tx-dlf-search' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-search.svg',
    'tx-dlf-statistics' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-statistics.svg',
    'tx-dlf-tableofcontents' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-tableofcontents.svg',
    'tx-dlf-toolbox' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-toolbox.svg',
    'tx-dlf-validator' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-validator.svg',
];
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Imaging\IconRegistry::class
);
foreach ($iconArray as $key => $value) {
    $iconRegistry->registerIcon(
        $key,
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => $value]
    );
}
// Register plugins as content elements.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:dlf/Configuration/TsConfig/ContentElements.tsconfig">'
);
// Register tools for toolbox plugin.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'] = [];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_fulltexttool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.fulltexttool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_annotationtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.annotationtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_imagedownloadtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.imagedownloadtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_imagemanipulationtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.imagemanipulationtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_pdfdownloadtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.pdfdownloadtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_searchindocumenttool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.searchindocumenttool';
// Register hooks.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Common/MetsDocument.php']['hookClass'][] = \Kitodo\Dlf\Hooks\KitodoProductionHacks::class;
// Register AJAX eID handlers.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_suggest'] = \Kitodo\Dlf\Plugin\Eid\SearchSuggest::class . '::main';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_in_document'] = \Kitodo\Dlf\Plugin\Eid\SearchInDocument::class . '::main';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_pageview_proxy'] = \Kitodo\Dlf\Plugin\Eid\PageViewProxy::class . '::main';
// Use Caching Framework for Solr queries
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr'] = [];
}
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['backend'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\SimpleFileBackend';
}
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['options']['defaultLifeTime'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['options']['defaultLifeTime'] = 87600; // 87600 seconds = 1 day
}
// Register Typoscript user function.
if (\TYPO3_MODE === 'FE') {
    /**
     * docTypeCheck user function to use in Typoscript
     * @example [userFunc = user_dlf_docTypeCheck($type, $pid)]
     *
     * @access public
     *
     * @param string $type The document type string to test for
     * @param int $pid The PID for the metadata definitions
     *
     * @return bool true if document type matches, false if not
     */
    function user_dlf_docTypeCheck(string $type, int $pid): bool
    {
        $hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Hooks\UserFunc::class);
        return ($hook->getDocumentType($pid) === $type);
    }
}
