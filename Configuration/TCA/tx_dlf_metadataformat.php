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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_metadataformat',
        'label'     => 'encoded',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY encoded',
        'delete' => 'deleted',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfmetadata.png',
        'rootLevel' => 0,
        'dividers2tabs' => 2,
        'searchFields' => 'encoded',
        'hideTable' => 1,
    ],
    'feInterface' => [
        'fe_admin_fieldList' => '',
    ],
    'interface' => [
        'showRecordFieldList' => 'parent_id,encoded,metadataquery,metadataquery_sorting',
    ],
    'columns' => [
        'parent_id' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'encoded' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_metadataformat.encoded',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_formats',
                'foreign_table_where' => 'ORDER BY tx_dlf_formats.type',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'metadataquery' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_metadataformat.metadataquery',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'required,trim',
            ],
        ],
        'metadataquery_sorting' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_metadataformat.metadataquery_sorting',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
            ],
        ],
        'mandatory' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_metadataformat.mandatory',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_metadataformat.tab1, encoded;;;;1-1-1, metadataquery;;;;2-2-2, metadataquery_sorting, mandatory;;;;3-3-3'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
