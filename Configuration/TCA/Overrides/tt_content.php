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
// Plugin "audioplayer".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_audioplayer'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_audioplayer'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_audioplayer', 'dlf_audioplayer'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_audioplayer', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/AudioPlayer.xml');
// Plugin "basket".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_basket'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_basket'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_basket', 'dlf_basket'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_basket', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Basket.xml');
// Plugin "calendar".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_calendar'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_calendar'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_calendar', 'dlf_calendar'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_calendar', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Calendar.xml');
// Plugin "collection".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_collection'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_collection'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_collection', 'dlf_collection'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_collection', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Collection.xml');
// Plugin "feeds".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_feeds'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_feeds'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_feeds', 'dlf_feeds'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_feeds', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Feeds.xml');
// Plugin "listview".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_listview'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_listview'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_listview', 'dlf_listview'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_listview', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/ListView.xml');
// Plugin "metadata".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_metadata'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_metadata'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_metadata', 'dlf_metadata'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_metadata', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Metadata.xml');
// Plugin "navigation".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_navigation'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_navigation'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_navigation', 'dlf_navigation'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_navigation', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Navigation.xml');
// Plugin "oaipmh".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_oaipmh'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_oaipmh'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_oaipmh', 'dlf_oaipmh'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_oaipmh', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/OaiPmh.xml');
// Plugin "pagegrid".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_pagegrid'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_pagegrid'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_pagegrid', 'dlf_pagegrid'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_pagegrid', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/PageGrid.xml');
// Plugin "pageview".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_pageview'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_pageview'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_pageview', 'dlf_pageview'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_pageview', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/PageView.xml');
// Plugin "search".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_search'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_search'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_search', 'dlf_search'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_search', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Search.xml');
// Plugin "statistics".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_statistics'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_statistics'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_statistics', 'dlf_statistics'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_statistics', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Statistics.xml');
// Plugin "tableofcontents".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_tableofcontents'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_tableofcontents'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_tableofcontents', 'dlf_tableofcontents'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_tableofcontents', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/TableOfContents.xml');
// Plugin "toolbox".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_toolbox'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_toolbox'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_toolbox', 'dlf_toolbox'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_toolbox', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Toolbox.xml');
// Plugin "validator".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_validator'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_validator'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tt_content.dlf_validator', 'dlf_validator'], 'list_type', 'dlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_validator', 'FILE:EXT:' . 'dlf/Configuration/Flexforms/Validator.xml');
