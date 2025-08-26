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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections',
        'label'     => 'label',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'fe_cruser_id' => 'fe_cruser_id',
        'fe_admin_lock' => 'fe_admin_lock',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY label',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfcollections.png',
        'rootLevel' => 0,
        'searchFields' => 'label,index_name,oai_name,fe_cruser_id',
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
                'foreign_table' => 'tx_dlf_collections',
                'foreign_table_where' => 'AND tx_dlf_collections.pid=###CURRENT_PID### AND tx_dlf_collections.sys_language_uid IN (-1,0)',
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
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        'value' => '-1',
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        'value' => '-2',
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        'value' => '--div--',
                    ],
                ],
                'size' => 5,
                'autoSizeMax' => 15,
                'minitems' => 0,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
                'default' => '',
            ],
        ],
        'label' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.label',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.index_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
                'eval' => 'uniqueInPid',
                'default' => '',
                'fieldInformation' => [
                    'editInProductionWarning' => [
                        'renderType' => 'editInProductionWarning',
                    ],
                ],
            ],
        ],
        'index_search' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.index_search',
            'config' => [
                'type' => 'text',
                'size' => 30,
                'rows' => 5,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'oai_name' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.oai_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                // TODO: Add own form evaluation (see OAI-PMH setSpec).
                'eval' => 'nospace,uniqueInPid',
                'default' => '',
            ],
        ],
        'description' => [
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank', // deprecated in 8.7 but kept for upgrade wizard
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.description',
            'config' => [
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ],
                'type' => 'text',
                'cols' => 30,
                'rows' => 10,
                'wrap' => 'virtual',
                'default' => '',
                'enableRichtext' => true,
            ],
        ],
        'thumbnail' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.thumbnail',
            'config' => [
                'type' => 'file',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ],
                'foreign_match_fields' => [
                    'fieldname' => 'thumbnail',
                    'tablenames' => 'tx_dlf_collections',
                ],
                'allowed' => 'common-image-types'
            ],
        ],
        'priority' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.priority',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '1',
                        'value' => 1,
                    ],
                    [
                        'label' => '2',
                        'value' => 2,
                    ],
                    [
                        'label' => '3',
                        'value' => 3,
                    ],
                    [
                        'label' => '4',
                        'value' => 4,
                    ],
                    [
                        'label' => '5',
                        'value' => 5,
                    ],
                ],
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'eval' => 'num,int',
                'default' => 3,
            ],
        ],
        'documents' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.documents',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'foreign_table' => 'tx_dlf_documents',
                'foreign_table_where' => 'AND tx_dlf_documents.pid=###CURRENT_PID### ORDER BY tx_dlf_documents.title_sorting',
                'size' => 5,
                'autoSizeMax' => 15,
                'minitems' => 0,
                'maxitems' => 1048576,
                'MM' => 'tx_dlf_relations',
                'MM_match_fields' => [
                    'ident' => 'docs_colls',
                ],
                'MM_opposite_field' => 'collections',
                'default' => 0,
            ],
        ],
        'owner' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.owner',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.owner.none',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_dlf_libraries',
                'foreign_table_where' => 'AND tx_dlf_libraries.sys_language_uid IN (-1,0) ORDER BY tx_dlf_libraries.label',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'fe_cruser_id' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.fe_cruser_id',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.fe_cruser_id.none',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'fe_users',
                'foreign_table_where' => 'ORDER BY fe_users.username',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'fe_admin_lock' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.fe_admin_lock',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'status' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.status.default',
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
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.tab1,label,--palette--;;1,description,--palette--;;2,--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.tab2,sys_language_uid,l18n_parent,l18n_diffsource,--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_collections.tab3,hidden,fe_group,status,owner,fe_cruser_id,--palette--;;3'],
    ],
    'palettes' => [
        '1' => ['showitem' => 'index_name, --linebreak--, index_search, --linebreak--, oai_name'],
        '2' => ['showitem' => 'thumbnail, priority'],
        '3' => ['showitem' => 'fe_admin_lock'],
    ],
];
