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
        'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_printer',
        'label'     => 'label',
        'default_sortby' => 'ORDER BY label',
        'delete'	=> 'deleted',
        'iconfile'	=> 'EXT:dlf/Resources/Public/Icons/txdlfprinter.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => 'label,print',
    ),
    'interface' => array (
        'showRecordFieldList' => 'label,name,address',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'columns' => array (
        'label' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_printer.label',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,trim',
            ),
        ),
        'print' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_printer.printcommand',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required',
            ),
        ),
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_printer.tab1, label;;;;1-1-1, print;;;;2-2-2'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => ''),
    ),
);
