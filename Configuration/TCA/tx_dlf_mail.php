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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_mail',
        'label'     => 'label',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfemail.png',
        'rootLevel' => 0,
        'searchFields' => 'label,name,mail',
    ],
    'interface' => [
    ],
    'columns' => [
        'label' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_mail',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
                'default' => '',
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_mail.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'default' => '',
            ],
        ],
        'mail' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_mail.mail',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'unique',
                'default' => '',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_mail.tab1,label,name,mail'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
