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

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
// Define constants.
if (!defined('LOG_SEVERITY_OK')) {
    define('LOG_SEVERITY_OK', -1);
}
if (!defined('LOG_SEVERITY_INFO')) {
    define('LOG_SEVERITY_INFO', 0);
}
if (!defined('LOG_SEVERITY_NOTICE')) {
    define('LOG_SEVERITY_NOTICE', 1);
}
if (!defined('LOG_SEVERITY_WARNING')) {
    define('LOG_SEVERITY_WARNING', 2);
}
if (!defined('LOG_SEVERITY_ERROR')) {
    define('LOG_SEVERITY_ERROR', 3);
}

// Register plugin icons.
$iconArray = [
    'tx-dlf-audioplayer' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-audioplayer.svg',
    'tx-dlf-basket' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-basket.svg',
    'tx-dlf-calendar' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-calendar.svg',
    'tx-dlf-collection' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-collection.svg',
    'tx-dlf-feeds' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-feeds.svg',
    'tx-dlf-listview' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-listview.svg',
    'tx-dlf-metadata' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-metadata.svg',
    'tx-dlf-navigation' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-navigation.svg',
    'tx-dlf-oaipmh' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-oaipmh.svg',
    'tx-dlf-pagegrid' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-pagegrid.svg',
    'tx-dlf-pageview' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-pageview.svg',
    'tx-dlf-search' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-search.svg',
    'tx-dlf-statistics' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-statistics.svg',
    'tx-dlf-tableofcontents' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-tableofcontents.svg',
    'tx-dlf-toolbox' => 'EXT:dlf/Resources/Public/Icons/tx-dlf-toolbox.svg',
    'tx_dlf_view3d' => 'EXT:dlf/Resources/Public/Icons/tx_dlf_view3d.svg',
];
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Imaging\IconRegistry::class
);
foreach ($iconArray as $key => $value) {
    $iconRegistry->registerIcon(
        $key,
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => $value]
    );
}
// Register plugins as content elements.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:dlf/Configuration/TsConfig/ContentElements.tsconfig">'
);
$_EXTKEY = 'dlf';
// Register tools for toolbox plugin.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'] = [];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_fulltexttool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.fulltexttool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_annotationtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.annotationtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_fulltextdownloadtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.fulltextdownloadtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_imagedownloadtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.imagedownloadtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_imagemanipulationtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.imagemanipulationtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_pdfdownloadtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.pdfdownloadtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_searchindocumenttool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.searchindocumenttool';
// Register hooks.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Common/MetsDocument.php']['hookClass'][] = \Kitodo\Dlf\Hooks\KitodoProductionHacks::class;
// Register AJAX eID handlers.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_suggest'] = \Kitodo\Dlf\Eid\SearchSuggest::class . '::main';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_in_document'] = \Kitodo\Dlf\Eid\SearchInDocument::class . '::main';
if ($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['dlf']['enableInternalProxy'] ?? false) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_pageview_proxy'] = \Kitodo\Dlf\Eid\PageViewProxy::class . '::main';
}
// Use Caching Framework for Solr queries
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr'] = [];
}
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['backend'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\SimpleFileBackend';
}
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['options']['defaultLifeTime'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_solr']['options']['defaultLifeTime'] = 87600; // 87600 seconds = 1 day
}
// Use Caching Framework for XML file caching
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_doc'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_doc'] = [];
}
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_doc']['backend'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_doc']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\SimpleFileBackend';
}
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_doc']['options']['defaultLifeTime'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_dlf_doc']['options']['defaultLifeTime'] = 87600; // 87600 seconds = 1 day
}
// Add new renderType for TCA fields.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][] = [
    'nodeName' => 'editInProductionWarning',
    'priority' => 30,
    'class' => \Kitodo\Dlf\Hooks\Form\FieldInformation\EditInProductionWarning::class
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][] = [
    'nodeName' => 'solrCoreStatus',
    'priority' => 30,
    'class' => \Kitodo\Dlf\Hooks\Form\FieldInformation\SolrCoreStatus::class
];


// Add migration wizards
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\Kitodo\Dlf\Updates\MigrateSettings::class]
    = \Kitodo\Dlf\Updates\MigrateSettings::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\Kitodo\Dlf\Updates\FileLocationUpdater::class]
    = \Kitodo\Dlf\Updates\FileLocationUpdater::class;

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Search',
    [
        Search::class => 'main, search'
    ],
    // non-cacheable actions
    [
        Search::class => 'main, search'
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Feeds',
    [
        Feeds::class => 'main',
    ],
    // non-cacheable actions
    [
        Feeds::class => 'main',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Statistics',
    [
        Statistics::class => 'main',
    ],
    // non-cacheable actions
    [
        Statistics::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'TableOfContents',
    [
        TableOfContents::class => 'main',
    ],
    // non-cacheable actions
    [
        TableOfContents::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'PageGrid',
    [
        PageGrid::class => 'main',
    ],
    // non-cacheable actions
    [
        PageGrid::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Navigation',
    [
        Navigation::class => 'main',
    ],
    // non-cacheable actions
    [
        Navigation::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'AudioPlayer',
    [
        AudioPlayer::class => 'main',
    ],
    // non-cacheable actions
    [
        AudioPlayer::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Calendar',
    [
        Calendar::class => 'main, years, calendar',
    ],
    // non-cacheable actions
    [
        Calendar::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'PageView',
    [
        PageView::class => 'main',
    ],
    // non-cacheable actions
    [
        PageView::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Basket',
    [
        Basket::class => 'main, add, basket',
    ],
    // non-cacheable actions
    [
        Basket::class => 'main, add, basket',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Toolbox',
    [
        Toolbox::class => 'main',
    ],
    // non-cacheable actions
    [
        Toolbox::class => '',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'OaiPmh',
    [
        OaiPmh::class => 'main',
    ],
    // non-cacheable actions
    [
        OaiPmh::class => 'main',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'ListView',
    [
        ListView::class => 'main',
    ],
    // non-cacheable actions
    [
        ListView::class => 'main',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Collection',
    [
        Collection::class => 'list, show, showSorted'
    ],
    // non-cacheable actions
    [
        Collection::class => 'showSorted',
    ]
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'Metadata',
    [
        Metadata::class => 'main',
    ],
    // non-cacheable actions
    [
        Metadata::class => '',
    ]
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Kitodo.Dlf',
    'View3D',
    [
        View3D::class => 'main',
    ],
    // non-cacheable actions
    [
        View3D::class => '',
    ]
);


// Register a node in ext_localconf.php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1638809996] = [
    'nodeName' => 'thumbnailCustomElement',
    'priority' => 40,
    'class' => \Kitodo\Dlf\Hooks\ThumbnailCustomElement::class
];
