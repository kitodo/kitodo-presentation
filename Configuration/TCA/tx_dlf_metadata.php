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

return [
    'ctrl' => [
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata',
        'label'     => 'label',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfmetadata.png',
        'rootLevel' => 0,
        'searchFields' => 'label,index_name',
    ],
    'interface' => [
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language'
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_metadata',
                'foreign_table_where' => 'AND tx_dlf_metadata.pid=###CURRENT_PID### AND tx_dlf_metadata.sys_language_uid IN (-1,0) ORDER BY label ASC',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'default' => 0,
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'label' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.label',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'index_name' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.index_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
                'eval' => 'nospace,alphanum_x,uniqueInPid',
                'default' => '',
                'fieldInformation' => [
                    'editInProductionWarning' => [
                        'renderType' => 'editInProductionWarning',
                    ],
                ],
            ],
        ],
        'format' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.format',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_dlf_metadataformat',
                'foreign_field' => 'parent_id',
                'foreign_unique' => 'encoded',
                'appearance' => [
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'enabledControls' => [
                        'info' => 0,
                        'new' => 1,
                        'dragdrop' => 0,
                        'sort' => 0,
                        'hide' => 0,
                        'delete' => 1,
                        'localize' => 0,
                    ],
                ],
            ],
        ],
        'default_value' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.default_value',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'wrap' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.wrap',
            'config' => [
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ],
                'type' => 'text',
                'cols' => 48,
                'rows' => 20,
                'wrap' => 'off',
                'eval' => 'trim',
                'default' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
                'fixedFont' => true,
                'enableTabulator' => true
            ],
        ],
        'index_tokenized' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.index_tokenized',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'index_stored' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.index_stored',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'index_indexed' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.index_indexed',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'index_boost' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.index_boost',
            'config' => [
                'type' => 'number',
                'size' => 5,
                'default' => 1.0,
                'format' => 'decimal',
            ],
        ],
        'is_sortable' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.is_sortable',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'is_facet' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.is_facet',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'is_listed' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.is_listed',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'index_autocomplete' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.index_autocomplete',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'status' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.status.default',
                        'value' => 0,
                    ],
                ],
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.tab1,label,--palette--;;1,format,default_value,wrap,--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.tab2,sys_language_uid,l18n_parent,l18n_diffsource,--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadata.tab3,hidden,status'],
    ],
    'palettes' => [
        '1' => ['showitem' => 'index_name, --linebreak--, index_tokenized, index_stored, index_indexed, index_boost, --linebreak--, is_sortable, is_facet, is_listed, index_autocomplete'],
    ],
];
