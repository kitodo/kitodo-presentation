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

if (!defined('TYPO3_MODE')) 	die ('Access denied.');

// Register static typoscript.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Basic Configuration');

// Plugin "audioplayer".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_audioplayer'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_audioplayer'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_audioplayer', $_EXTKEY.'_audioplayer'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_audioplayer', 'FILE:EXT:'.$_EXTKEY.'/plugins/audioplayer/flexform.xml');

// Plugin "basket".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_basket'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_basket'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_basket', $_EXTKEY.'_basket'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_basket', 'FILE:EXT:'.$_EXTKEY.'/plugins/basket/flexform.xml');

// Plugin "collection".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_collection'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_collection'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_collection', $_EXTKEY.'_collection'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_collection', 'FILE:EXT:'.$_EXTKEY.'/plugins/collection/flexform.xml');

// Plugin "feeds".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_feeds'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_feeds'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_feeds', $_EXTKEY.'_feeds'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_feeds', 'FILE:EXT:'.$_EXTKEY.'/plugins/feeds/flexform.xml');

// Plugin "listview".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_listview'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_listview'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_listview', $_EXTKEY.'_listview'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_listview', 'FILE:EXT:'.$_EXTKEY.'/plugins/listview/flexform.xml');

// Plugin "metadata".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_metadata'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_metadata'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_metadata', $_EXTKEY.'_metadata'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_metadata', 'FILE:EXT:'.$_EXTKEY.'/plugins/metadata/flexform.xml');

// Plugin "navigation".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_navigation'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_navigation'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_navigation', $_EXTKEY.'_navigation'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_navigation', 'FILE:EXT:'.$_EXTKEY.'/plugins/navigation/flexform.xml');

// Plugin "newspaper".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_newspaper'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_newspaper'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_newspaper', $_EXTKEY.'_newspaper'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_newspaper', 'FILE:EXT:'.$_EXTKEY.'/plugins/newspaper/flexform.xml');

// Plugin "oai".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_oai'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_oai'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_oai', $_EXTKEY.'_oai'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_oai', 'FILE:EXT:'.$_EXTKEY.'/plugins/oai/flexform.xml');

// Plugin "pagegrid".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pagegrid'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pagegrid'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_pagegrid', $_EXTKEY.'_pagegrid'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pagegrid', 'FILE:EXT:'.$_EXTKEY.'/plugins/pagegrid/flexform.xml');

// Plugin "pageview".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pageview'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pageview'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_pageview', $_EXTKEY.'_pageview'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pageview', 'FILE:EXT:'.$_EXTKEY.'/plugins/pageview/flexform.xml');

// Plugin "search".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_search'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_search'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_search', $_EXTKEY.'_search'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'plugins/search/', 'Search Facets');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_search', 'FILE:EXT:'.$_EXTKEY.'/plugins/search/flexform.xml');

// Plugin "statistics".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_statistics'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_statistics'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_statistics', $_EXTKEY.'_statistics'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_statistics', 'FILE:EXT:'.$_EXTKEY.'/plugins/statistics/flexform.xml');

// Plugin "table of contents".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_toc'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_toc'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_toc', $_EXTKEY.'_toc'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'plugins/toc/', 'Table of Contents');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_toc', 'FILE:EXT:'.$_EXTKEY.'/plugins/toc/flexform.xml');

// Plugin "toolbox".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_toolbox'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_toolbox'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array ('LLL:EXT:dlf/locallang.xml:tt_content.dlf_toolbox', $_EXTKEY.'_toolbox'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_toolbox', 'FILE:EXT:'.$_EXTKEY.'/plugins/toolbox/flexform.xml');

// Plugin "validator".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_validator'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_validator'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_validator', $_EXTKEY.'_validator'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_validator', 'FILE:EXT:'.$_EXTKEY.'/plugins/validator/flexform.xml');

// Register modules.
if (TYPO3_MODE == 'BE')	{

    // Add modules after "web".
    if (!isset($TBE_MODULES['txdlfmodules']))	{

        $modules = array();

        foreach($TBE_MODULES as $key => $val)	{

            if ($key == 'web')	{

                $modules[$key] = $val;

                $modules['txdlfmodules'] = '';

            } else {

                $modules[$key] = $val;

            }

        }

        $TBE_MODULES = $modules;

        unset($modules);

    }

    // Main "dlf" module.
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', '', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent('txdlfmodules', 'typo3-pagetree');

    // Module "indexing".
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', 'txdlfindexing', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/indexing/');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_txdlfmodules_txdlfindexing','EXT:dlf/modules/indexing/locallang_mod.xml');

    // Module "newclient".
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', 'txdlfnewclient', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/newclient/');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_txdlfmodules_txdlfnewclient','EXT:dlf/modules/newclient/locallang_mod.xml');

}
