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
        'iconfile'	=> 'EXT:dlf/Resources/Public/Icons/txdlflibraries.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => 'label,website,contact',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'interface' => array (
        'showRecordFieldList' => 'label,website,contact',
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
                'type' => 'none',
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
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.tab1, label,--palette--;;1;;1-1-1, website;;;;2-2-2, contact, image;;;;3-3-3, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.tab2, sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_libraries.tab3, oai_label,--palette--;;2;;1-1-1, opac_label,--palette--;;3;;2-2-2, union_label,--palette--;;4;;3-3-3'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => 'index_name', 'canNotCollapse' => 1),
        '2' => array ('showitem' => 'oai_base', 'canNotCollapse' => 1),
        '3' => array ('showitem' => 'opac_base', 'canNotCollapse' => 1),
        '4' => array ('showitem' => 'union_base', 'canNotCollapse' => 1),
    ),
);
