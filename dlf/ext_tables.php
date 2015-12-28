<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// Register database tables.
$TCA['tx_dlf_documents'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents',
		'label'     => 'title',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title_sorting',
		'delete'	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlfdocuments.png',
		'rootLevel'	=> 0,
		'dividers2tabs' => 2,
		'searchFields' => 'title,volume,author,year,place,uid,prod_id,location,oai_id,opac_id,union_id,urn',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	)
);

$TCA['tx_dlf_structures'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_structures',
		'label'     => 'label',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY label',
		'delete'	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlfstructures.png',
		'rootLevel'	=> 0,
		'dividers2tabs' => 2,
		'searchFields' => 'label,index_name,oai_name',
		'requestUpdate' => 'toplevel',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	)
);

$TCA['tx_dlf_metadata'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata',
		'label'     => 'label',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'sortby' => 'sorting',
		'delete'	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlfmetadata.png',
		'rootLevel'	=> 0,
		'dividers2tabs' => 2,
		'searchFields' => 'label,index_name',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	)
);

$TCA['tx_dlf_metadataformat'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat',
		'label'     => 'encoded',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY encoded',
		'delete'	=> 'deleted',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlfmetadata.png',
		'rootLevel'	=> 0,
		'dividers2tabs' => 2,
		'searchFields' => 'encoded',
		'hideTable'	=> 1,
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	)
);

$TCA['tx_dlf_formats'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_formats',
		'label'     => 'type',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY type',
		'delete'	=> 'deleted',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlfformats.png',
		'rootLevel'	=> 1,
		'dividers2tabs' => 2,
		'searchFields' => 'type,class',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	)
);

$TCA['tx_dlf_solrcores'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_solrcores',
		'label'     => 'label',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY label',
		'delete'	=> 'deleted',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlfsolrcores.png',
		'rootLevel'	=> -1,
		'dividers2tabs' => 2,
		'searchFields' => 'label,index_name',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	)
);

$TCA['tx_dlf_collections'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections',
		'label'     => 'label',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'fe_cruser_id' => 'fe_cruser_id',
		'fe_admin_lock' => 'fe_admin_lock',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY label',
		'delete'	=> 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'fe_group' => 'fe_group',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlfcollections.png',
		'rootLevel'	=> 0,
		'dividers2tabs' => 2,
		'searchFields' => 'label,index_name,oai_name,fe_cruser_id',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'label,description,thumbnail,documents',
	)
);

$TCA['tx_dlf_libraries'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries',
		'label'     => 'label',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY label',
		'delete'	=> 'deleted',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'tca.php',
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY).'res/icons/txdlflibraries.png',
		'rootLevel'	=> 0,
		'dividers2tabs' => 2,
		'searchFields' => 'label,website,contact',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	)
);

// Register static typoscript.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'typoscript/', 'Basic Configuration');

// Plugin "collection".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_collection'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_collection'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_collection', $_EXTKEY.'_collection'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_collection', 'FILE:EXT:'.$_EXTKEY.'/plugins/collection/flexform.xml');

// Plugin "feeds".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_feeds'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_feeds'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_feeds', $_EXTKEY.'_feeds'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_feeds', 'FILE:EXT:'.$_EXTKEY.'/plugins/feeds/flexform.xml');

// Plugin "listview".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_listview'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_listview'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_listview', $_EXTKEY.'_listview'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_listview', 'FILE:EXT:'.$_EXTKEY.'/plugins/listview/flexform.xml');

// Plugin "metadata".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_metadata'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_metadata'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_metadata', $_EXTKEY.'_metadata'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_metadata', 'FILE:EXT:'.$_EXTKEY.'/plugins/metadata/flexform.xml');

// Plugin "navigation".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_navigation'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_navigation'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_navigation', $_EXTKEY.'_navigation'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_navigation', 'FILE:EXT:'.$_EXTKEY.'/plugins/navigation/flexform.xml');

// Plugin "oai".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_oai'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_oai'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_oai', $_EXTKEY.'_oai'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_oai', 'FILE:EXT:'.$_EXTKEY.'/plugins/oai/flexform.xml');

// Plugin "pagegrid".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pagegrid'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pagegrid'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_pagegrid', $_EXTKEY.'_pagegrid'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pagegrid', 'FILE:EXT:'.$_EXTKEY.'/plugins/pagegrid/flexform.xml');

// Plugin "pageview".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pageview'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pageview'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_pageview', $_EXTKEY.'_pageview'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pageview', 'FILE:EXT:'.$_EXTKEY.'/plugins/pageview/flexform.xml');

// Plugin "search".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_search'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_search'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_search', $_EXTKEY.'_search'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'plugins/search/', 'Search Facets');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_search', 'FILE:EXT:'.$_EXTKEY.'/plugins/search/flexform.xml');

// Plugin "statistics".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_statistics'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_statistics'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_statistics', $_EXTKEY.'_statistics'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_statistics', 'FILE:EXT:'.$_EXTKEY.'/plugins/statistics/flexform.xml');

// Plugin "table of contents".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_toc'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_toc'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_toc', $_EXTKEY.'_toc'), 'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'plugins/toc/', 'Table of Contents');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_toc', 'FILE:EXT:'.$_EXTKEY.'/plugins/toc/flexform.xml');

// Plugin "toolbox".
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_toolbox'] = 'layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_toolbox'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:dlf/locallang.xml:tt_content.dlf_toolbox', $_EXTKEY.'_toolbox'), 'list_type');

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

	// Module "indexing".
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', 'txdlfindexing', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/indexing/');

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_txdlfmodules_txdlfindexing','EXT:dlf/modules/indexing/locallang_mod.xml');

	// Module "newclient".
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', 'txdlfnewclient', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/newclient/');

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_txdlfmodules_txdlfnewclient','EXT:dlf/modules/newclient/locallang_mod.xml');

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent('txdlfmodules', 'typo3-pagetree');
}
