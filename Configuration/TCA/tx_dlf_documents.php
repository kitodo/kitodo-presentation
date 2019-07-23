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
        'title'     => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents',
        'label'     => 'title',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title_sorting',
        'delete'	=> 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ),
        'iconfile'	=> 'EXT:dlf/res/icons/txdlfdocuments.png',
        'rootLevel'	=> 0,
        'dividers2tabs' => 2,
        'searchFields' => 'title,volume,author,year,place,uid,prod_id,location,oai_id,opac_id,union_id,urn',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => '',
    ),
    'interface' => array (
        'showRecordFieldList' => 'title,volume,author,year,place,uid,prod_id,location,oai_id,opac_id,union_id,urn',
        'maxDBListItems' => 25,
        'maxSingleDBListItems' => 50,
    ),
    'columns' => array (
        'hidden' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array (
                'type' => 'check',
                'default' => 0,
            ),
        ),
        'starttime' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
            'config' => array (
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
            ),
        ),
        'endtime' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
            'config' => array (
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
            ),
        ),
        'fe_group' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => array (
                    array ('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
                    array ('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
                    array ('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--'),
                ),
                'foreign_table' => 'fe_groups',
                'size' => 5,
                'autoSizeMax' => 15,
                'minitems' => 0,
                'maxitems' => 20,
                'exclusiveKeys' => '-1,-2',
            ),
        ),
        'prod_id' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.prod_id',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
            ),
        ),
        'location' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.location',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'required,uniqueInPid',
            ),
        ),
        'record_id' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.record_id',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace,uniqueInPid',
            ),
        ),
        'opac_id' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.opac_id',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
            ),
        ),
        'union_id' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.union_id',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
            ),
        ),
        'urn' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.urn',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
            ),
        ),
        'purl' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.purl',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'nospace',
            ),
        ),
        'title' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.title',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
            ),
        ),
        'title_sorting' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.title_sorting',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 1024,
                'eval' => 'trim',
            ),
        ),
        'author' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.author',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        ),
        'year' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.year',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        ),
        'place' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.place',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        ),
        'thumbnail' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.thumbnail',
            'config' => array (
                'type' => 'user',
                'userFunc' => 'EXT:dlf/hooks/class.tx_dlf_tceforms.php:tx_dlf_tceforms->displayThumbnail',
            ),
        ),
        'metadata' => array (
            'config' => array (
                'type' => 'passthrough',
            ),
        ),
        'metadata_sorting' => array (
            'config' => array (
                'type' => 'passthrough',
            ),
        ),
        'structure' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.structure',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_structures',
                'foreign_table_where' => 'AND tx_dlf_structures.pid=###CURRENT_PID### AND tx_dlf_structures.sys_language_uid IN (-1,0) AND tx_dlf_structures.toplevel=1 ORDER BY tx_dlf_structures.label',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ),
        ),
        'partof' => array (
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.partof',
            'config' => array (
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_dlf_documents',
                'prepend_tname' => 0,
                'size' => 1,
                'selectedListStyle' => 'width:400px;',
                'minitems' => 0,
                'maxitems' => 1,
                'disable_controls' => 'browser,delete',
                'default' => 0,
                'readOnly' => 1,
            ),
        ),
        'volume' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.volume',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        ),
        'volume_sorting' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.volume_sorting',
            'config' => array (
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ),
        ),
        'collections' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.collections',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_dlf_collections',
                'foreign_table_where' => 'AND tx_dlf_collections.pid=###CURRENT_PID### AND tx_dlf_collections.sys_language_uid IN (-1,0) ORDER BY tx_dlf_collections.label',
                'size' => 5,
                'autoSizeMax' => 15,
                'minitems' => 1,
                'maxitems' => 1024,
                'MM' => 'tx_dlf_relations',
                'MM_match_fields' => array (
                    'ident' => 'docs_colls',
                ),
            ),
        ),
        'owner' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.owner',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_dlf_libraries',
                'foreign_table_where' => 'AND tx_dlf_libraries.sys_language_uid IN (-1,0) ORDER BY tx_dlf_libraries.label',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ),
        ),
        'solrcore' => array (
            'config' => array (
                'type' => 'passthrough',
            ),
        ),
        'status' => array (
            'exclude' => 1,
            'label' => 'LLL:EXT:dlf/locallang.xml:tx_dlf_documents.status',
            'config' => array (
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array (
                    array ('LLL:EXT:dlf/locallang.xml:tx_dlf_documents.status.default', 0),
                ),
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 0,
            ),
        ),
    ),
    'types' => array (
        '0' => array ('showitem' => '--div--;LLL:EXT:dlf/locallang.xml:tx_dlf_documents.tab1, title,--palette--;;1;;1-1-1, author, year, place, structure,--palette--;;2;;2-2-2, collections;;;;3-3-3, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_documents.tab2, location;;;;1-1-1, record_id, prod_id;;;;2-2-2, oai_id;;;;3-3-3, opac_id, union_id, urn, purl;;;;4-4-4, --div--;LLL:EXT:dlf/locallang.xml:tx_dlf_documents.tab3, hidden,--palette--;;3;;1-1-1, fe_group;;;;2-2-2, status;;;;3-3-3, owner;;;;4-4-4'),
    ),
    'palettes' => array (
        '1' => array ('showitem' => 'title_sorting', 'canNotCollapse' => 1),
        '2' => array ('showitem' => 'partof, thumbnail, --linebreak--, volume, volume_sorting', 'canNotCollapse' => 1),
        '3' => array ('showitem' => 'starttime, endtime', 'canNotCollapse' => 1),
    )
);
