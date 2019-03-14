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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_mail',
        'label'     => 'label',
        'sortby' => 'sorting',
        'delete'	=> 'deleted',
        'iconfile'	=> 'EXT:dlf/Resources/Public/Icons/txdlfemail.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => 'label,name,mail',
    ),
    'interface' => array (
        'showRecordFieldList' => 'label,name,mail',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'columns' => array (
        'label' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_mail',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required',
            ),
        ),
        'name' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_mail.name',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => '',
            ),
        ),
        'mail' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_mail.mail',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'unique',
            ),
        ),
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_mail.tab1, label;;;;1-1-1, name;;;;2-2-2, mail;;;;2-2-2'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => ''),
    ),
);
