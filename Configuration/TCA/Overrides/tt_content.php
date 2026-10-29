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

if (!defined('TYPO3')) {
    die('Access denied.');
}

$excludeList = 'layout,select_key,pages,recursive';
$addList = 'pi_flexform';

$flexFormsPathPrefix = 'FILE:EXT:dlf/Configuration/FlexForms/';
$langFile = 'LLL:EXT:dlf/Resources/Private/Language/locallang_be.xlf:';
$title = '.plugin.title';
$description = '.plugin.description';
$pluginGroup = 'dlf';

$pluginDefinitions = [
    [
        'name' => 'Annotation',
        'langKey' => 'annotation',
        'icon' => null,
        'flexFormFile' => null
    ],
    [
        'name' => 'Basket',
        'langKey' => 'basket',
        'icon' => 'tx-dlf-basket',
        'flexFormFile' => 'Basket.xml'
    ],
    [
        'name' => 'Calendar',
        'langKey' => 'calendar',
        'icon' => 'tx-dlf-calendar',
        'flexFormFile' => 'Calendar.xml'
    ],
    [
        'name' => 'Collection',
        'langKey' => 'collection',
        'icon' => 'tx-dlf-collection',
        'flexFormFile' => 'Collection.xml'
    ],
    [
        'name' => 'Embedded3dViewer',
        'langKey' => 'embedded3dViewer',
        'icon' => 'tx-dlf-embedded3dviewer',
        'flexFormFile' => 'Embedded3dViewer.xml'
    ],
    [
        'name' => 'Validation',
        'langKey' => 'validation',
        'icon' => 'tx-dlf-validation',
        'flexFormFile' => 'Validation.xml'
    ],
    [
        'name' => 'Feeds',
        'langKey' => 'feeds',
        'icon' => 'tx-dlf-feeds',
        'flexFormFile' => 'Feeds.xml'
    ],
    [
        'name' => 'ListView',
        'langKey' => 'listView',
        'icon' => 'tx-dlf-listview',
        'flexFormFile' => 'ListView.xml'
    ],
    [
        'name' => 'MediaPlayer',
        'langKey' => 'mediaPlayer',
        'icon' => 'tx-dlf-mediaplayer',
        'flexFormFile' => 'MediaPlayer.xml'
    ],
    [
        'name' => 'Metadata',
        'langKey' => 'metadata',
        'icon' => 'tx-dlf-metadata',
        'flexFormFile' => 'Metadata.xml'
    ],
    [
        'name' => 'Navigation',
        'langKey' => 'navigation',
        'icon' => 'tx-dlf-navigation',
        'flexFormFile' => 'Navigation.xml'
    ],
    [
        'name' => 'OaiPmh',
        'langKey' => 'oaiPmh',
        'icon' => 'tx-dlf-oaipmh',
        'flexFormFile' => 'OaiPmh.xml'
    ],
    [
        'name' => 'PageGrid',
        'langKey' => 'pageGrid',
        'icon' => 'tx-dlf-pagegrid',
        'flexFormFile' => 'PageGrid.xml'
    ],
    [
        'name' => 'PageView',
        'langKey' => 'pageView',
        'icon' => 'tx-dlf-pageview',
        'flexFormFile' => 'PageView.xml'
    ],
    [
        'name' => 'Search',
        'langKey' => 'search',
        'icon' => 'tx-dlf-search',
        'flexFormFile' => 'Search.xml'
    ],
    [
        'name' => 'Statistics',
        'langKey' => 'statistics',
        'icon' => 'tx-dlf-statistics',
        'flexFormFile' => 'Statistics.xml'
    ],
    [
        'name' => 'TableOfContents',
        'langKey' => 'tableOfContents',
        'icon' => 'tx-dlf-tableofcontents',
        'flexFormFile' => 'TableOfContents.xml'
    ],
    [
        'name' => 'Toolbox',
        'langKey' => 'toolbox',
        'icon' => 'tx-dlf-toolbox',
        'flexFormFile' => 'Toolbox.xml'
    ]
];

// Create wizard item group for all plugins
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    $pluginGroup,
    'Kitodo Plugins',
);

foreach ($pluginDefinitions as $pluginDefinition) {
    $pluginSignature = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Dlf',
        $pluginDefinition['name'],
        $langFile . $pluginDefinition['langKey'] . $title,
        $pluginDefinition['icon'],
        $pluginGroup,
        $langFile . $pluginDefinition['langKey'] . $description,
        empty($pluginDefinition['flexFormFile']) ? '' : $flexFormsPathPrefix . $pluginDefinition['flexFormFile']
    );

    if (!empty($pluginDefinition['flexFormFile'])) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            '--div--;Configuration,pi_flexform,',
            $pluginSignature,
            'after:subheader',
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            '*',
            $flexFormsPathPrefix . $pluginDefinition['flexFormFile'],
            $pluginSignature,
        );
    }
}
