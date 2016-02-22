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

$TCA['tx_dlf_documents'] = array (
	'ctrl' => $TCA['tx_dlf_documents']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'title,volume,author,year,place,uid,prod_id,location,oai_id,opac_id,union_id,urn',
		'maxDBListItems' => 25,
		'maxSingleDBListItems' => 50,
	),
	'feInterface' => $TCA['tx_dlf_documents']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'starttime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array (
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
			),
		),
		'endtime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array (
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
			),
		),
		'fe_group' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--'),
				),
				'foreign_table' => 'fe_groups',
				'size' => 5,
				'autoSizeMax' => 15,
				'minitems' => 0,
				'maxitems' => 20,
				'exclusiveKeys' => '-1,-2',
			),
		),
		'prod_id' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.prod_id',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'location' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.location',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'required,uniqueInPid',
			),
		),
		'record_id' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.record_id',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace,uniqueInPid',
			),
		),
		'opac_id' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.opac_id',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'union_id' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.union_id',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'urn' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.urn',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'purl' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.purl',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'title' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.title',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'trim',
			),
		),
		'title_sorting' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.title_sorting',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'trim',
			),
		),
		'author' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.author',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'trim',
			),
		),
		'year' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.year',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'trim',
			),
		),
		'place' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.place',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'trim',
			),
		),
		'thumbnail' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.thumbnail',
			'config' => array (
				'type' => 'user',
				'userFunc' => 'EXT:dlf/hooks/class.tx_dlf_tceforms.php:tx_dlf_tceforms->displayThumbnail',
			),
		),
		'metadata' => array (
			'config' => array (
				'type' => 'passthrough',
			),
		),
		'metadata_sorting' => array (
			'config' => array (
				'type' => 'passthrough',
			),
		),
		'structure' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.structure',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_dlf_structures',
				'foreign_table_where' => 'AND tx_dlf_structures.pid=###CURRENT_PID### AND tx_dlf_structures.sys_language_uid IN (-1,0) AND tx_dlf_structures.toplevel=1 ORDER BY tx_dlf_structures.label',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'partof' => array (
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.partof',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_dlf_documents',
				'prepend_tname' => 0,
				'size' => 1,
				'selectedListStyle' => 'width:400px;',
				'minitems' => 0,
				'maxitems' => 1,
				'disable_controls' => 'browser,delete',
				'default' => 0,
				'readOnly' => 1,
			),
		),
		'volume' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.volume',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'trim',
			),
		),
		'volume_sorting' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.volume_sorting',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'trim',
			),
		),
		'collections' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.collections',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_dlf_collections',
				'foreign_table_where' => 'AND tx_dlf_collections.pid=###CURRENT_PID### AND tx_dlf_collections.sys_language_uid IN (-1,0) ORDER BY tx_dlf_collections.label',
				'size' => 5,
				'autoSizeMax' => 15,
				'minitems' => 1,
				'maxitems' => 1024,
				'MM' => 'tx_dlf_relations',
				'MM_match_fields' => array (
					'ident' => 'docs_colls',
				),
			),
		),
		'owner' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.owner',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_dlf_libraries',
				'foreign_table_where' => 'AND tx_dlf_libraries.sys_language_uid IN (-1,0) ORDER BY tx_dlf_libraries.label',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'solrcore' => array (
			'config' => array (
				'type' => 'passthrough',
			),
		),
		'status' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.status',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:dlf/locallang.xml:tx_dlf_documents.status.default', 0),
				),
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'default' => 0,
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_documents.tab1, title;;1;;1-1-1, author, year, place, structure;;2;;2-2-2, collections;;;;3-3-3, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_documents.tab2, location;;;;1-1-1, record_id, prod_id;;;;2-2-2, oai_id;;;;3-3-3, opac_id, union_id, urn, purl;;;;4-4-4, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_documents.tab3, hidden;;3;;1-1-1, fe_group;;;;2-2-2, status;;;;3-3-3, owner;;;;4-4-4'),
	),
	'palettes' => array (
		'1' => array ('showitem' => 'title_sorting', 'canNotCollapse' => 1),
		'2' => array ('showitem' => 'partof, thumbnail, --linebreak--, volume, volume_sorting', 'canNotCollapse' => 1),
		'3' => array ('showitem' => 'starttime, endtime', 'canNotCollapse' => 1),
	),
);

$TCA['tx_dlf_structures'] = array (
	'ctrl' => $TCA['tx_dlf_structures']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'label,index_name,oai_name,toplevel',
	),
	'feInterface' => $TCA['tx_dlf_structures']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array (
					array ('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('', 0),
				),
				'foreign_table' => 'tx_dlf_structures',
				'foreign_table_where' => 'AND tx_dlf_structures.pid=###CURRENT_PID### AND tx_dlf_structures.sys_language_uid IN (-1,0)',
			),
		),
		'l18n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough',
			),
		),
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'toplevel' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_structures.toplevel',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'label' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_structures.label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,trim',
			),
		),
		'index_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_structures.index_name',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,nospace,alphanum_x,uniqueInPid',
			),
		),
		'oai_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_structures.oai_name',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'trim',
			),
		),
		'thumbnail' => array (
			'exclude' => 1,
			'displayCond' => 'FIELD:toplevel:REQ:true',
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_structures.thumbnail',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:dlf/locallang.xml:tx_dlf_structures.thumbnail.self', 0),
				),
				'foreign_table' => 'tx_dlf_structures',
				'foreign_table_where' => 'AND tx_dlf_structures.pid=###CURRENT_PID### AND tx_dlf_structures.toplevel=0 AND tx_dlf_structures.sys_language_uid IN (-1,0) ORDER BY tx_dlf_structures.label',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'default' => 0,
			),
		),
		'status' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_structures.status',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:dlf/locallang.xml:tx_dlf_structures.status.default', 0),
				),
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'default' => 0,
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_structures.tab1, toplevel;;;;1-1-1, label;;1, thumbnail, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_structures.tab2, sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_structures.tab3, hidden;;;;1-1-1, status;;;;2-2-2'),
	),
	'palettes' => array (
		'1' => array ('showitem' => 'index_name, --linebreak--, oai_name', 'canNotCollapse' => 1),
	),
);

$TCA['tx_dlf_metadata'] = array (
	'ctrl' => $TCA['tx_dlf_metadata']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'label,index_name,is_sortable,is_facet,is_listed,autocomplete',
	),
	'feInterface' => $TCA['tx_dlf_metadata']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array (
					array ('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('', 0),
				),
				'foreign_table' => 'tx_dlf_metadata',
				'foreign_table_where' => 'AND tx_dlf_metadata.pid=###CURRENT_PID### AND tx_dlf_metadata.sys_language_uid IN (-1,0)',
			),
		),
		'l18n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			),
		),
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'label' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,trim',
			),
		),
		'index_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.index_name',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,nospace,alphanum_x,uniqueInPid',
			),
		),
		'format' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.format',
			'config' => array (
				'type' => 'inline',
				'foreign_table' => 'tx_dlf_metadataformat',
				'foreign_field' => 'parent_id',
				'foreign_unique' => 'encoded',
				'appearance' => array (
					'expandSingle' => 1,
					'levelLinksPosition' => 'bottom',
					'enabledControls' => array (
						'info' => 0,
						'new' => 0,
						'dragdrop' => 0,
						'sort' => 0,
						'hide' => 0,
						'delete' => 1,
						'localize' => 0,
					),
				),
			),
		),
		'default_value' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.default_value',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'trim',
			),
		),
		'wrap' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.wrap',
			'config' => array (
				'type' => 'text',
				'cols' => 48,
				'rows' => 20,
				'wrap' => 'off',
				'eval' => 'trim',
				'default' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
			),
			'defaultExtras' => 'nowrap:fixed-font:enable-tab',
		),
		'tokenized' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.tokenized',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'stored' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.stored',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'indexed' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.indexed',
			'config' => array (
				'type' => 'check',
				'default' => 1,
			),
		),
		'boost' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.boost',
			'config' => array (
				'type' => 'input',
				'size' => 5,
				'max' => 64,
				'default' => '1.00',
				'eval' => 'double2',
			),
		),
		'is_sortable' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.is_sortable',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'is_facet' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.is_facet',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'is_listed' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.is_listed',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'autocomplete' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.autocomplete',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'status' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.status',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.status.default', 0),
				),
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'default' => 0,
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.tab1, label;;1;;1-1-1, format;;;;2-2-2, default_value;;;;3-3-3, wrap, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.tab2, sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.tab3, hidden;;;;1-1-1, status;;;;2-2-2'),
	),
	'palettes' => array (
		'1' => array ('showitem' => 'index_name, --linebreak--, tokenized, stored, indexed, boost, --linebreak--, is_sortable, is_facet, is_listed, autocomplete', 'canNotCollapse' => 1),
	),
);

$TCA['tx_dlf_metadataformat'] = array (
	'ctrl' => $TCA['tx_dlf_metadataformat']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'parent_id,encoded,xpath,xpath_sorting',
	),
	'feInterface' => $TCA['tx_dlf_metadataformat']['feInterface'],
	'columns' => array (
		'parent_id' => array (
			'config' => array (
				'type' => 'passthrough',
			),
		),
		'encoded' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.encoded',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_dlf_formats',
				'foreign_table_where' => 'ORDER BY tx_dlf_formats.type',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'xpath' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.xpath',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'required,trim',
			),
		),
		'xpath_sorting' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.xpath_sorting',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'trim',
			),
		),
		'mandatory' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.mandatory',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.tab1, encoded;;;;1-1-1, xpath;;;;2-2-2, xpath_sorting, mandatory;;;;3-3-3'),
	),
	'palettes' => array (
		'1' => array ('showitem' => ''),
	),
);

$TCA['tx_dlf_formats'] = array (
	'ctrl' => $TCA['tx_dlf_formats']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'type,class',
	),
	'feInterface' => $TCA['tx_dlf_formats']['feInterface'],
	'columns' => array (
		'type' => array (
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_formats.type',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,nospace,alphanum_x,unique',
			),
		),
		'root' => array (
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_formats.root',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,nospace,alphanum_x,unique',
			),
		),
		'namespace' => array (
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_formats.namespace',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'required,nospace,unique',
			),
		),
		'class' => array (
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_formats.class',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 1024,
				'eval' => 'nospace,alphanum_x,unique',
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_formats.tab1, type;;;;1-1-1, root;;;;2-2-2, namespace, class;;;;3-3-3'),
	),
	'palettes' => array (
		'1' => array ('showitem' => ''),
	),
);

$TCA['tx_dlf_solrcores'] = array (
	'ctrl' => $TCA['tx_dlf_solrcores']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'label,index_name',
	),
	'feInterface' => $TCA['tx_dlf_solrcores']['feInterface'],
	'columns' => array (
		'label' => array (
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_solrcores.label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,trim',
			),
		),
		'index_name' => array (
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_solrcores.index_name',
			'config' => array (
				'type' => 'input',
				'form_type' => 'none',
				'size' => 30,
				'max' => 255,
				'eval' => 'alphanum,unique',
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_solrcores.tab1, label;;;;1-1-1, index_name;;;;2-2-2'),
	),
	'palettes' => array (
		'1' => array ('showitem' => ''),
	),
);

$TCA['tx_dlf_collections'] = array (
	'ctrl' => $TCA['tx_dlf_collections']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'label,index_name,oai_name,fe_cruser_id',
	),
	'feInterface' => $TCA['tx_dlf_collections']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array (
					array ('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('', 0),
				),
				'foreign_table' => 'tx_dlf_collections',
				'foreign_table_where' => 'AND tx_dlf_collections.pid=###CURRENT_PID### AND tx_dlf_collections.sys_language_uid IN (-1,0)',
			),
		),
		'l18n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			),
		),
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'fe_group' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--'),
				),
				'foreign_table' => 'fe_groups',
				'size' => 5,
				'autoSizeMax' => 15,
				'minitems' => 0,
				'maxitems' => 20,
				'exclusiveKeys' => '-1,-2',
			),
		),
		'label' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,trim',
			),
		),
		'index_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.index_name',
			'config' => array (
				'type' => 'input',
				'form_type' => 'none',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,uniqueInPid',
			),
		),
		'oai_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.oai_name',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace,alphanum_x,uniqueInPid',
			),
		),
		'description' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.description',
			'config' => array (
				'type' => 'text',
				'cols' => 30,
				'rows' => 10,
				'wrap' => 'virtual',
			),
			'defaultExtras' => 'richtext[undo,redo,cut,copy,paste,link,image,line,acronym,chMode,blockstylelabel,formatblock,blockstyle,textstylelabel,textstyle,bold,italic,unorderedlist,orderedlist]:rte_transform[mode=ts_css]',
		),
		'thumbnail' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.thumbnail',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file_reference',
				'allowed' => 'gif,jpg,png',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'priority' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.priority',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('1', 1),
					array ('2', 2),
					array ('3', 3),
					array ('4', 4),
					array ('5', 5),
				),
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'default' => 3,
			),
		),
		'documents' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.documents',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_dlf_documents',
				'foreign_table_where' => 'AND tx_dlf_documents.pid=###CURRENT_PID### ORDER BY tx_dlf_documents.title_sorting',
				'size' => 5,
				'autoSizeMax' => 15,
				'minitems' => 0,
				'maxitems' => 1048576,
				'MM' => 'tx_dlf_relations',
				'MM_match_fields' => array (
					'ident' => 'docs_colls',
				),
				'MM_opposite_field' => 'collections',
			),
		),
		'owner' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.owner',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:dlf/locallang.xml:tx_dlf_collections.owner.none', 0),
				),
				'foreign_table' => 'tx_dlf_libraries',
				'foreign_table_where' => 'AND tx_dlf_libraries.sys_language_uid IN (-1,0) ORDER BY tx_dlf_libraries.label',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'fe_cruser_id' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.fe_cruser_id',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:dlf/locallang.xml:tx_dlf_collections.fe_cruser_id.none', 0),
				),
				'foreign_table' => 'fe_users',
				'foreign_table_where' => 'ORDER BY fe_users.username',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'fe_admin_lock' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.fe_admin_lock',
			'config' => array (
				'type' => 'check',
				'default' => 0,
			),
		),
		'status' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_collections.status',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('LLL:EXT:dlf/locallang.xml:tx_dlf_collections.status.default', 0),
				),
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
				'default' => 0,
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_collections.tab1, label;;1;;1-1-1, description;;2;;2-2-2, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_collections.tab2, sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_collections.tab3, hidden;;;;1-1-1, fe_group;;;;2-2-2, status;;;;3-3-3, owner;;;;4-4-4, fe_cruser_id;;3'),
	),
	'palettes' => array (
		'1' => array ('showitem' => 'index_name, --linebreak--, oai_name', 'canNotCollapse' => 1),
		'2' => array ('showitem' => 'thumbnail, priority', 'canNotCollapse' => 1),
		'3' => array ('showitem' => 'fe_admin_lock', 'canNotCollapse' => 1),
	),
);

$TCA['tx_dlf_libraries'] = array (
	'ctrl' => $TCA['tx_dlf_libraries']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'label,website,contact',
	),
	'feInterface' => $TCA['tx_dlf_libraries']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array (
					array ('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
			),
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array ('', 0),
				),
				'foreign_table' => 'tx_dlf_libraries',
				'foreign_table_where' => 'AND tx_dlf_libraries.pid=###CURRENT_PID### AND tx_dlf_libraries.sys_language_uid IN (-1,0)',
			),
		),
		'l18n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			),
		),
		'label' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,trim',
			),
		),
		'index_name' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.index_name',
			'config' => array (
				'type' => 'input',
				'form_type' => 'none',
				'size' => 30,
				'max' => 255,
				'eval' => 'required,uniqueInPid',
			),
		),
		'website' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.website',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'contact' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.contact',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'image' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.image',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => 256,
				'uploadfolder' => 'uploads/tx_dlf',
				'show_thumbs' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'oai_label' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.oai_label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			),
		),
		'oai_base' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.oai_base',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			),
		),
		'opac_label' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.opac_label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			),
		),
		'opac_base' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.opac_base',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
		'union_label' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.union_label',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			),
		),
		'union_base' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.union_base',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'nospace',
			),
		),
	),
	'types' => array (
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.tab1, label;;1;;1-1-1, website;;;;2-2-2, contact, image;;;;3-3-3, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.tab2, sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.tab3, oai_label;;2;;1-1-1, opac_label;;3;;2-2-2, union_label;;4;;3-3-3'),
	),
	'palettes' => array (
		'1' => array ('showitem' => 'index_name', 'canNotCollapse' => 1),
		'2' => array ('showitem' => 'oai_base', 'canNotCollapse' => 1),
		'3' => array ('showitem' => 'opac_base', 'canNotCollapse' => 1),
		'4' => array ('showitem' => 'union_base', 'canNotCollapse' => 1),
	),
);
