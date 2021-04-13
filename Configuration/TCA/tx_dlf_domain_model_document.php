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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document',
        'label'     => 'title',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title_sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfdocuments.png',
        'rootLevel' => 0,
        'dividers2tabs' => 2,
        'searchFields' => 'title,volume,author,year,place,uid,prod_id,location,oai_id,opac_id,union_id,urn',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => '',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,volume,author,year,place,uid,prod_id,location,oai_id,opac_id,union_id,urn,document_format',
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login', '-1'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.any_login', '-2'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.usergroups', '--div--'],
                ],
                'foreign_table' => 'fe_groups',
                'size' => 5,
                'autoSizeMax' => 15,
                'minitems' => 0,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
                'default' => '',
            ],
        ],
        'prod_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:_domain_model_document.prod_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
                'default' => '',
            ],
        ],
        'location' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.location',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,uniqueInPid',
                'default' => '',
            ],
        ],
        'record_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.record_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace,uniqueInPid',
                'default' => '',
            ],
        ],
        'opac_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.opac_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
                'default' => '',
            ],
        ],
        'union_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:_domain_model_document.union_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
                'default' => '',
            ],
        ],
        'urn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.urn',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
                'default' => '',
            ],
        ],
        'purl' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.purl',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
                'default' => '',
            ],
        ],
        'title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'title_sorting' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.title_sorting',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'author' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.author',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'year' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.year',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'place' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.place',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'thumbnail' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.thumbnail',
            'config' => [
                'type' => 'user',
                'userFunc' => \Kitodo\Dlf\Hooks\UserFunc::class . '->displayThumbnail',
            ],
        ],
        'metadata' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'metadata_sorting' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'structure' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.structure',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_domain_model_structure',
                'foreign_table_where' => 'AND tx_dlf_domain_model_structure.pid=###CURRENT_PID### AND tx_dlf_domain_model_structure.sys_language_uid IN (-1,0) AND tx_dlf_domain_model_structure.toplevel=1 ORDER BY tx_dlf_domain_model_structure.label',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'partof' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.partof',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_dlf_domain_model_document',
                'prepend_tname' => 0,
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
                'readOnly' => 1,
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => true
                    ]
                ],
                'hideDeleteIcon' => true
            ],
        ],
        'volume' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.volume',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'volume_sorting' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.volume_sorting',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'license' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.license',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'terms' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.terms',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'restrictions' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.restrictions',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'out_of_print' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.out_of_print',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'rights_info' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.rights_info',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'mets_label' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.mets_label',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'mets_orderlabel' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.mets_orderlabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'collections' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.collections',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'enableMultiSelectFilterTextfield' => 1,
                'foreign_table' => 'tx_dlf_domain_model_collection',
                'foreign_table_where' => 'AND tx_dlf_domain_model_collection.pid=###CURRENT_PID### AND tx_dlf_domain_model_collection.sys_language_uid IN (-1,0) ORDER BY tx_dlf_domain_model_collections.label',
                'size' => 5,
                'autoSizeMax' => 15,
                'minitems' => 1,
                'maxitems' => 1024,
                'MM' => 'tx_dlf_domain_model_relation',
                'MM_match_fields' => [
                    'ident' => 'docs_colls',
                ],
                'default' => 0,
            ],
        ],
        'owner' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.owner',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_domain_model_library',
                'foreign_table_where' => 'AND tx_dlf_domain_model_library.sys_language_uid IN (-1,0) ORDER BY tx_dlf_domain_model_library.label',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'solrcore' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'status' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.status.default', 0],
                ],
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'document_format' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.document_format',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.document_format.mets', 'METS'],
                    ['LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.document_format.iiif', 'IIIF'],
                ],
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.tab1,--palette--;;1,author,--palette--;;2,structure,--palette--;;3,collections,--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.tab2,--palette--;;4,--palette--;;5,--palette--;;6,--palette--;;7,--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_domain_model_document.tab3,--palette--;;8,hidden,--palette--;;9,fe_group,status,owner,license,'],
    ],
    'palettes' => [
        '1' => ['showitem' => 'title, title_sorting, --linebreak--, mets_label, thumbnail', 'canNotCollapse' => 1],
        '2' => ['showitem' => 'year, place', 'canNotCollapse' => 1],
        '3' => ['showitem' => 'partof, mets_orderlabel, --linebreak--, volume, volume_sorting', 'canNotCollapse' => 1],
        '4' => ['showitem' => 'location, document_format', 'canNotCollapse' => 1],
        '5' => ['showitem' => 'record_id, prod_id', 'canNotCollapse' => 1],
        '6' => ['showitem' => 'opac_id, union_id', 'canNotCollapse' => 1],
        '7' => ['showitem' => 'urn, purl', 'canNotCollapse' => 1],
        '8' => ['showitem' => 'license, terms, --linebreak--, restrictions, rights_info, --linebreak--, out_of_print', 'canNotCollapse' => 1],
        '9' => ['showitem' => 'starttime, endtime', 'canNotCollapse' => 1],
    ]
];
