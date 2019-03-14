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
        'title'     => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket',
        'label'     => 'label',
        'tstamp'    => 'tstamp',
        'fe_user_id' => 'fe_user_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY label',
        'delete'	=> 'deleted',
        'iconfile'	=> 'EXT:dlf/Resources/Public/Icons/txdlfbasket.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => '',
    ),
    'interface' => array (
        'showRecordFieldList' => 'label,doc_ids,session_id',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'columns' => array (
        'label' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.label',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,trim',
            ),
        ),
        'session_id' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.sessionId',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum,unique',
            ),
        ),
        'doc_ids' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.docIds',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'alphanum_x',
            ),
        ),
        'fe_user_id' => array (
            'label' => 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.feUser',
            'config' => array (
                'type' => 'input',
                'eval' => 'int,unique',
            ),
        ),
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_basket.tab1, label;;;;1-1-1, session_id;;;;2-2-2, doc_ids;;;;2-2-2, fe_user_id;;;;2-2-2'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => ''),
    ),
);
