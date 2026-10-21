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

if (!defined('TYPO3')) {
    die('Access denied.');
}

$excludeList = 'layout,select_key,pages,recursive';
$addList = 'pi_flexform';

$flexFormsPathPrefix = 'FILE:EXT:dlf/Configuration/FlexForms/';
$langFile = 'LLL:EXT:dlf/Resources/Private/Language/locallang_be.xlf:';
$title = '.plugin.title';
$description = '.plugin.description';
$pluginGroup = 'dlf';

// Create wizard item group for all plugins
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
  'tt_content',
  'CType',
  $pluginGroup,
  'Kitodo Plugins',
);

// Plugin "annotation".
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Annotation',
    $langFile . 'annotation' . $title,
    null,
    $pluginGroup,
    $langFile . 'annotation' . $description
);

// Plugin "basket".
$plugin = 'dlf_basket';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Basket.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Basket',
    $langFile . 'basket' . $title,
    'tx-dlf-basket',
    $pluginGroup,
    $langFile . 'basket' . $description,
    $flexFormsPathPrefix . 'Basket.xml'
);

// Plugin "calendar".
$plugin = 'dlf_calendar';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Calendar.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Calendar',
    $langFile . 'calendar' . $title,
    'tx-dlf-calendar',
    $pluginGroup,
    $langFile . 'calendar' . $description,
    $flexFormsPathPrefix . 'Calendar.xml'
);

// Plugin "collection".
$plugin = 'dlf_collection';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Collection.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Collection',
    $langFile . 'collection' . $title,
    'tx-dlf-collection',
    $pluginGroup,
    $langFile . 'collection' . $description,
    $flexFormsPathPrefix . 'Collection.xml'
);

// Plugin "embedded3dviewer".
$plugin = 'dlf_embedded3dviewer';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'dlf_embedded3dviewer',
    $flexFormsPathPrefix . 'Embedded3dViewer.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Embedded3dViewer',
    $langFile . 'embedded3dViewer' . $title,
    'tx-dlf-embedded3dviewer',
    $pluginGroup,
    $langFile . 'embedded3dViewer' . $description,
    $flexFormsPathPrefix . 'Embedded3dViewer.xml'
);

// Plugin "validationform".
$plugin = 'dlf_validationform';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'dlf_validationform',
    $flexFormsPathPrefix . 'ValidationForm.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'ValidationForm',
    $langFile . 'validationForm' . $title,
    'tx-dlf-validationform',
    $pluginGroup,
    $langFile . 'validationForm' . $description,
    $flexFormsPathPrefix . 'ValidationForm.xml'
);

// Plugin "feeds".
$plugin = 'dlf_feeds';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Feeds.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Feeds',
    $langFile . 'feeds' . $title,
    'tx-dlf-feeds',
    $pluginGroup,
    $langFile . 'feeds' . $description,
    $flexFormsPathPrefix . 'Feeds.xml'
);

// Plugin "listview".
$plugin = 'dlf_listview';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'ListView.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'ListView',
    $langFile . 'listView' . $title,
    'tx-dlf-listview',
    $pluginGroup,
    $langFile . 'listView' . $description,
    $flexFormsPathPrefix . 'ListView.xml'
);

// Plugin "mediaplayer".
$plugin = 'dlf_mediaplayer';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'MediaPlayer.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'MediaPlayer',
    $langFile . 'mediaPlayer' . $title,
    'tx-dlf-mediaplayer',
    $pluginGroup,
    $langFile . 'mediaPlayer' . $description,
    $flexFormsPathPrefix . 'MediaPlayer.xml'
);

// Plugin "metadata".
$plugin = 'dlf_metadata';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Metadata.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Metadata',
    $langFile . 'metadata' . $title,
    'tx-dlf-metadata',
    $pluginGroup,
    $langFile . 'metadata' . $description,
    $flexFormsPathPrefix . 'Metadata.xml'
);

// Plugin "navigation".
$plugin = 'dlf_navigation';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Navigation.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Navigation',
    $langFile . 'navigation' . $title,
    'tx-dlf-navigation',
    $pluginGroup,
    $langFile . 'navigation' . $description,
    $flexFormsPathPrefix . 'Navigation.xml'
);

// Plugin "oaipmh".
$plugin = 'dlf_oaipmh';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'OaiPmh.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'OaiPmh',
    $langFile . 'oaiPmh' . $title,
    'tx-dlf-oaipmh',
    $pluginGroup,
    $langFile . 'oaiPmh' . $description,
    $flexFormsPathPrefix . 'OaiPmh.xml'
);

// Plugin "pagegrid".
$plugin = 'dlf_pagegrid';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'PageGrid.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'PageGrid',
    $langFile . 'pageGrid' . $title,
    'tx-dlf-pagegrid',
    $pluginGroup,
    $langFile . 'pageGrid' . $description,
    $flexFormsPathPrefix . 'PageGrid.xml'
);

// Plugin "pageview".
$plugin = 'dlf_pageview';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'PageView.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'PageView',
    $langFile . 'pageView' . $title,
    'tx-dlf-pageview',
    $pluginGroup,
    $langFile . 'pageView' . $description,
    $flexFormsPathPrefix . 'PageView.xml'
);

// Plugin "search".
$plugin = 'dlf_search';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Search.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Search',
    $langFile . 'search' . $title,
    'tx-dlf-search',
    $pluginGroup,
    $langFile . 'search' . $description,
    $flexFormsPathPrefix . 'Search.xml'
);

// Plugin "statistics".
$plugin = 'dlf_statistics';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Statistics.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Statistics',
    $langFile . 'statistics' . $title,
    'tx-dlf-statistics',
    $pluginGroup,
    $langFile . 'statistics' . $description,
    $flexFormsPathPrefix . 'Statistics.xml'
);

// Plugin "tableofcontents".
$plugin = 'dlf_tableofcontents';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'TableOfContents.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'TableOfContents',
    $langFile . 'tableOfContents' . $title,
    'tx-dlf-tableofcontents',
    $pluginGroup,
    $langFile . 'tableOfContents' . $description,
    $flexFormsPathPrefix . 'TableOfContents.xml'
);

// Plugin "toolbox".
$plugin = 'dlf_toolbox';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$plugin] = $excludeList;
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$plugin] = $addList;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $plugin,
    $flexFormsPathPrefix . 'Toolbox.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Dlf',
    'Toolbox',
    $langFile . 'toolbox' . $title,
    'tx-dlf-toolbox',
    $pluginGroup,
    $langFile . 'toolbox' . $description,
    $flexFormsPathPrefix . 'Toolbox.xml'
);
