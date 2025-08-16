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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_tokens',
        'label'     => 'token',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'default_sortby' => 'ORDER BY token',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfsolrcores.png',
        'rootLevel' => -1,
        'searchFields' => 'token',
    ],
    'columns' => [
        'token' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_tokens.label',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'required' => true,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'options' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_tokens.index_name',
            'config' => [
                'type' => 'input',
                'eval' => 'alphanum,nospace',
                'default' => '',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_tokens.tab1,token,options'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
