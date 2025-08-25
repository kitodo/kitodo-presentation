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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_actionlog',
        'label'     => 'label',
        'crdate'    => 'crdate',
        'default_sortby' => 'ORDER BY label',
        'delete' => 'deleted',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfreport.png',
        'rootLevel' => 0,
        'searchFields' => 'label,name,crdate',
    ],
    'interface' => [
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ],
    'columns' => [
        'label' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_actionlog.label',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'user_id' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_actionlog.user_id',
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
        'file_name' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_actionlog.file_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum_x,unique',
                'default' => '',
            ],
        ],
        'count_pages' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_actionlog.count_pages',
            'config' => [
                'type' => 'number',
                'size' => 30,
                'format' => 'integer',
                'default' => 0,
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_actionlog.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 100,
                'eval' => 'trim',
                'default' => '',
            ],
        ]
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_actionlog.tab1,label,name,file_name,crdate,count_pages'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
