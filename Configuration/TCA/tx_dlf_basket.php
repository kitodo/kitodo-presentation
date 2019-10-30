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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket',
        'label'     => 'label',
        'tstamp'    => 'tstamp',
        'fe_user_id' => 'fe_user_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY label',
        'delete' => 'deleted',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfbasket.png',
        'rootLevel' => 0,
        'dividers2tabs' => 2,
        'searchFields' => '',
    ],
    'interface' => [
        'showRecordFieldList' => 'label,doc_ids,session_id',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => '',
    ],
    'columns' => [
        'label' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.label',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,trim',
                'default' => '',
            ],
        ],
        'session_id' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.sessionId',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 32,
                'eval' => 'alphanum,unique',
                'default' => '',
            ],
        ],
        'doc_ids' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.docIds',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum_x',
                'default' => '',
            ],
        ],
        'fe_user_id' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.feUser',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'foreign_table_where' => 'ORDER BY fe_users.username',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.tab1,label,session_id,doc_ids,fe_user_id'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
