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

return array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_solrcores',
        'label'     => 'label',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY label',
        'delete'	=> 'deleted',
        'iconfile'	=> 'EXT:dlf/Resources/Public/Icons/txdlfsolrcores.png',
        'rootLevel'	=> -1,
        'dividers2tabs' => 2,
        'searchFields' => 'label,index_name',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'interface' => array (
        'showRecordFieldList' => 'label,index_name',
    ),
    'columns' => array (
        'label' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_solrcores.label',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,trim',
            ),
        ),
        'index_name' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_solrcores.index_name',
            'config' => array (
                'type' => 'none',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum,unique',
            ),
        ),
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_solrcores.tab1, label;;;;1-1-1, index_name;;;;2-2-2'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => ''),
    ),
);
