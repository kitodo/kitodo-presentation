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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_audioplayer', 'dlf_audioplayer'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_audioplayer', 'FILE:EXT:'.'dlf/plugins/audioplayer/flexform.xml');

// Plugin "basket".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_basket'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_basket'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_basket', 'dlf_basket'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_basket', 'FILE:EXT:'.'dlf/plugins/basket/flexform.xml');

// Plugin "collection".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_collection'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_collection'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_collection', 'dlf_collection'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_collection', 'FILE:EXT:'.'dlf/plugins/collection/flexform.xml');

// Plugin "feeds".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_feeds'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_feeds'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_feeds', 'dlf_feeds'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_feeds', 'FILE:EXT:'.'dlf/plugins/feeds/flexform.xml');

// Plugin "listview".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_listview'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_listview'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_listview', 'dlf_listview'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_listview', 'FILE:EXT:'.'dlf/plugins/listview/flexform.xml');

// Plugin "metadata".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_metadata'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_metadata'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_metadata', 'dlf_metadata'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_metadata', 'FILE:EXT:'.'dlf/plugins/metadata/flexform.xml');

// Plugin "navigation".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_navigation'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_navigation'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_navigation', 'dlf_navigation'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_navigation', 'FILE:EXT:'.'dlf/plugins/navigation/flexform.xml');

// Plugin "newspaper".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_newspaper'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_newspaper'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_newspaper', 'dlf_newspaper'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_newspaper', 'FILE:EXT:'.'dlf/plugins/newspaper/flexform.xml');

// Plugin "oai".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_oai'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_oai'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_oai', 'dlf_oai'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_oai', 'FILE:EXT:'.'dlf/plugins/oai/flexform.xml');

// Plugin "pagegrid".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_pagegrid'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_pagegrid'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_pagegrid', 'dlf_pagegrid'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_pagegrid', 'FILE:EXT:'.'dlf/plugins/pagegrid/flexform.xml');

// Plugin "pageview".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_pageview'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_pageview'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_pageview', 'dlf_pageview'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_pageview', 'FILE:EXT:'.'dlf/plugins/pageview/flexform.xml');

// Plugin "search".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_search'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_search'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_search', 'dlf_search'), 'list_type', 'dlf');


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_search', 'FILE:EXT:'.'dlf/plugins/search/flexform.xml');

// Plugin "statistics".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_statistics'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_statistics'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_statistics', 'dlf_statistics'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_statistics', 'FILE:EXT:'.'dlf/plugins/statistics/flexform.xml');

// Plugin "table of contents".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_toc'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_toc'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_toc', 'dlf_toc'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_toc', 'FILE:EXT:'.'dlf/plugins/toc/flexform.xml');

// Plugin "toolbox".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_toolbox'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_toolbox'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_toolbox', 'dlf_toolbox'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_toolbox', 'FILE:EXT:'.'dlf/plugins/toolbox/flexform.xml');

// Plugin "validator".
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['dlf_validator'] = 'layout,select_key,pages,recursive';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['dlf_validator'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_validator', 'dlf_validator'), 'list_type', 'dlf');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('dlf_validator', 'FILE:EXT:'.'dlf/plugins/validator/flexform.xml');
