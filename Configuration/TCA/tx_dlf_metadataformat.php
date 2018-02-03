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
        'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat',
        'label'     => 'encoded',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY encoded',
        'delete'	=> 'deleted',
        'iconfile'	=> 'EXT:dlf/res/icons/txdlfmetadata.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => 'encoded',
        'hideTable'	=> 1,
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'interface' => array (
        'showRecordFieldList' => 'parent_id,encoded,xpath,xpath_sorting',
    ),
    'columns' => array (
        'parent_id' => array (
            'config' => array (
                'type' => 'passthrough',
            ),
        ),
        'encoded' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.encoded',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_formats',
                'foreign_table_where' => 'ORDER BY tx_dlf_formats.type',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ),
        ),
        'xpath' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.xpath',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'required,trim',
            ),
        ),
        'xpath_sorting' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.xpath_sorting',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
            ),
        ),
        'mandatory' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.mandatory',
            'config' => array (
                'type' => 'check',
                'default' => 0,
            ),
        ),
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_metadataformat.tab1, encoded;;;;1-1-1, xpath;;;;2-2-2, xpath_sorting, mandatory;;;;3-3-3'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => ''),
    ),
);
