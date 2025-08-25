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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_solrcores',
        'label'     => 'label',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'default_sortby' => 'ORDER BY label',
        'delete' => 'deleted',
        'iconfile' => 'EXT:dlf/Resources/Public/Icons/txdlfsolrcores.png',
        'rootLevel' => -1,
        'searchFields' => 'label,index_name',
    ],
    'interface' => [
    ],
    'columns' => [
        'label' => [
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_solrcores.label',
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
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_solrcores.index_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum,nospace,unique',
                'default' => '',
                'readOnly' => 1,
                'fieldInformation' => [
                    'solrCoreStatus' => [
                        'renderType' => 'solrCoreStatus',
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/locallang_labels.xlf:tx_dlf_solrcores.tab1,label,index_name'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
