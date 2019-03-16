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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_actionlog',
        'label'     => 'label',
        'crdate'    => 'crdate',
        'cruser_id' => 'user_id',
        'default_sortby' => 'ORDER BY label',
        'delete' => 'deleted',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfreport.png',
        'rootLevel' => 0,
        'dividers2tabs' => 2,
        'searchFields' => 'label,name,crdate',
    ],
    'interface' => [
        'showRecordFieldList' => 'label,name,crdate',
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ],
    'feInterface' => [
        'fe_admin_fieldList' => '',
    ],
    'columns' => [
        'label' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_actionlog.label',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,trim',
            ],
        ],
        'user_id' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_actionlog.user_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum,unique',
            ],
        ],
        'file_name' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_actionlog.file_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum,unique',
            ],
        ],
        'count_pages' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_actionlog.count_pages',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_actionlog.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ],
        ]
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_actionlog.tab1, label;;;;1-1-1, name;;;;2-2-2, file_name;;;;2-2-2, crdate;;;;2-2-2, count_pages;;;;2-2-2'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
