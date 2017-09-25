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
        'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_actionlog',
        'label'     => 'label',
        'crdate'    => 'crdate',
        'cruser_id' => 'user_id',
        'default_sortby' => 'ORDER BY label',
        'delete'	=> 'deleted',
        'iconfile'	=> \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('dlf').'res/icons/txdlfreport.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => 'label,name,crdate',
    ),
    'interface' => array (
        'showRecordFieldList' => 'label,name,creation_date',
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'columns' => array (
        'label' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_actionlog.label',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,trim',
            ),
        ),
        'user_id' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_actionlog.user_id',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum,unique',
            ),
        ),
        'file_name' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_actionlog.file_name',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum,unique',
            ),
        ),
        'count_pages' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_actionlog.count_pages',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        ),
        'name' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_actionlog.name',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        )
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_actionlog.tab1, label;;;;1-1-1, name;;;;2-2-2, file_name;;;;2-2-2, creation_date;;;;2-2-2, count_pages;;;;2-2-2'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => ''),
    ),
);