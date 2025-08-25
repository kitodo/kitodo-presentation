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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadataformat',
        'label'     => 'encoded',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'delete' => 'deleted',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfmetadata.png',
        'rootLevel' => 0,
        'searchFields' => 'encoded',
        'hideTable' => 1,
    ],
    'interface' => [
    ],
    'columns' => [
        'parent_id' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'encoded' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadataformat.encoded',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_formats',
                'foreign_table_where' => 'ORDER BY tx_dlf_formats.type',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'xpath' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadataformat.xpath',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'required' => true,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'xpath_sorting' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadataformat.xpath_sorting',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'mandatory' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadataformat.mandatory',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'subentries' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadatasubentries',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_dlf_metadatasubentries',
                'foreign_field' => 'parent_id',
                'appearance' => [
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'enabledControls' => [
                        'info' => 1,
                        'new' => 1,
                        'dragdrop' => 0,
                        'sort' => 1,
                        'hide' => 0,
                        'delete' => 1,
                        'localize' => 0,
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadataformat.tab1,encoded,xpath,xpath_sorting,mandatory,subentries;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_metadatasubentries'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
