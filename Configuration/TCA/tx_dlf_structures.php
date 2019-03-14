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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures',
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
        'iconfile'	=> 'EXT:dlf/Resources/Public/Icons/txdlfstructures.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => 'label,index_name,oai_name',
        'requestUpdate' => 'toplevel',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'interface' => array (
        'showRecordFieldList' => 'label,index_name,oai_name,toplevel',
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
                'foreign_table' => 'tx_dlf_structures',
                'foreign_table_where' => 'AND tx_dlf_structures.pid=###CURRENT_PID### AND tx_dlf_structures.sys_language_uid IN (-1,0) ORDER BY label ASC',
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
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.toplevel',
            'config' => array (
                'type' => 'check',
                'default' => 0,
            ),
        ),
        'label' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.label',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,trim',
            ),
        ),
        'index_name' => array (
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.index_name',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,nospace,alphanum_x,uniqueInPid',
            ),
        ),
        'oai_name' => array (
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.oai_name',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        ),
        'thumbnail' => array (
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'displayCond' => 'FIELD:toplevel:REQ:true',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.thumbnail',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array ('LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.thumbnail.self', 0),
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.status',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array ('LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.status.default', 0),
                ),
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ),
        ),
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.tab1, toplevel;;;;1-1-1, label,--palette--;;1, thumbnail, --div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.tab2, sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, --div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_structures.tab3, hidden;;;;1-1-1, status;;;;2-2-2'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => 'index_name, --linebreak--, oai_name', 'canNotCollapse' => 1),
    ),
);
