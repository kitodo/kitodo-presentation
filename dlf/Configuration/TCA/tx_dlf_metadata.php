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

return array (
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
		'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('dlf').'res/icons/txdlfmetadata.png',
		'rootLevel'	=> 0,
		'dividers2tabs' => 2,
		'searchFields' => 'label,index_name',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => '',
	),
  'interface' => array (
		'showRecordFieldList' => 'label,index_name,is_sortable,is_facet,is_listed,autocomplete',
	),
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array (
					array ('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array ('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
				),
				'default' => 0
			),
		),
		'l18n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array ('', 0),
				),
				'foreign_table' => 'tx_dlf_metadata',
				'foreign_table_where' => 'AND tx_dlf_metadata.pid=###CURRENT_PID### AND tx_dlf_metadata.sys_language_uid IN (-1,0) ORDER BY label ASC',
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
						'new' => 1,
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
				'renderType' => 'selectSingle',
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
		'0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.tab1, label,--palette--;;1;;1-1-1, format;;;;2-2-2, default_value;;;;3-3-3, wrap, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.tab2, sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadata.tab3, hidden;;;;1-1-1, status;;;;2-2-2'),
	),
	'palettes' => array (
		'1' => array ('showitem' => 'index_name, --linebreak--, tokenized, stored, indexed, boost, --linebreak--, is_sortable, is_facet, is_listed, autocomplete', 'canNotCollapse' => 1),
	),
);
