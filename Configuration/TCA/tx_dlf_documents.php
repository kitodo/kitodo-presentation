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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents',
        'label'     => 'title',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
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
        'searchFields' => 'title,volume,author,year,place,uid,prod_id,location,record_id,oai_id,opac_id,union_id,urn',
    ],
    'interface' => [
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'tstamp' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.timestamp',
            'config' => [
                'type' => 'datetime',
            ]
        ],
        'crdate' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationDate',
            'config' => [
                'type' => 'datetime',
            ]
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'size' => 13,
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'size' => 13,
                'default' => 0,
            ],
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.prod_id',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.location',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
                'eval' => 'uniqueInPid',
                'default' => '',
            ],
        ],
        'record_id' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.record_id',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.opac_id',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.union_id',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.urn',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.purl',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.title',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.title_sorting',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.author',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.year',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.place',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.thumbnail',
            'config' => [
                'type' => 'user',
                'renderType' => 'thumbnailCustomElement'
            ],
        ],
        'structure' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.structure',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_structures',
                'foreign_table_where' => 'AND tx_dlf_structures.pid=###CURRENT_PID### AND tx_dlf_structures.sys_language_uid IN (-1,0) AND tx_dlf_structures.toplevel=1 ORDER BY tx_dlf_structures.label',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'partof' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.partof',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_dlf_documents',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.volume',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.volume_sorting',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.license',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.terms',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.restrictions',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.out_of_print',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.rights_info',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.mets_label',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.mets_orderlabel',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.collections',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_dlf_collections',
                'foreign_table_where' => 'AND tx_dlf_collections.pid=###CURRENT_PID### AND tx_dlf_collections.sys_language_uid IN (-1,0) ORDER BY tx_dlf_collections.label',
                'size' => 5,
                'autoSizeMax' => 15,
                'minitems' => 0,
                'maxitems' => 1024,
                'MM' => 'tx_dlf_relations',
                'MM_match_fields' => [
                    'ident' => 'docs_colls',
                ],
                'default' => 0,
            ],
        ],
        'owner' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.owner',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_libraries',
                'foreign_table_where' => 'AND tx_dlf_libraries.sys_language_uid IN (-1,0) ORDER BY tx_dlf_libraries.label',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.status.default',
                        'value' => 0,
                    ],
                ],
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'document_format' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.document_format',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.document_format.mets',
                        'value' => 'METS',
                    ],
                    [
                        'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.document_format.iiif',
                        'value' => 'IIIF',
                    ],
                ],
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ]
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.tab1,--palette--;;1,author,--palette--;;2,structure,--palette--;;3,collections,--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.tab2,--palette--;;4,--palette--;;5,--palette--;;6,--palette--;;7,--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_documents.tab3,--palette--;;8,hidden,--palette--;;9,fe_group,status,owner,license,'],
    ],
    'palettes' => [
        '1' => ['showitem' => 'title, title_sorting, --linebreak--, mets_label, thumbnail'],
        '2' => ['showitem' => 'year, place'],
        '3' => ['showitem' => 'partof, mets_orderlabel, --linebreak--, volume, volume_sorting'],
        '4' => ['showitem' => 'location, document_format'],
        '5' => ['showitem' => 'record_id, prod_id'],
        '6' => ['showitem' => 'opac_id, union_id'],
        '7' => ['showitem' => 'urn, purl'],
        '8' => ['showitem' => 'license, terms, --linebreak--, restrictions, rights_info, --linebreak--, out_of_print'],
        '9' => ['showitem' => 'starttime, endtime'],
    ]
];
