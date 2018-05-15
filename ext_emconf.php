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

$EM_CONF[$_EXTKEY] = [
    'title' => 'Kitodo.Presentation',
    'description' => 'Base plugins, modules, services and API of the Digital Library Framework. It is part of the community-based Kitodo Digitization Suite.',
    'version' => '3.0.0',
    'category' => 'misc',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-',
            'typo3' => '7.6.0-8.9.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ],
    'state' => 'stable',
    'uploadfolder' => TRUE,
    'createDirs' => '',
    'clearCacheOnLoad' => FALSE,
    'author' => 'Sebastian Meyer',
    'author_email' => 'sebastian.meyer@slub-dresden.de',
    'author_company' => 'Kitodo. Key to digital objects e. V.',
    'autoload' => [
        'classmap' => [
            "Classes/Cli/class.tx_dlf_cli.php",
            "Classes/Common/class.tx_dlf_document.php",
            "Classes/Common/class.tx_dlf_format.php",
            "Classes/Common/class.tx_dlf_fulltext.php",
            "Classes/Common/class.tx_dlf_helper.php",
            "Classes/Common/class.tx_dlf_indexing.php",
            "Classes/Common/class.tx_dlf_list.php",
            "Classes/Common/class.tx_dlf_module.php",
            "Classes/Common/class.tx_dlf_plugin.php",
            "Classes/Common/class.tx_dlf_solr.php",
            "Classes/Formats/class.tx_dlf_alto.php",
            "Classes/Formats/class.tx_dlf_mods.php",
            "Classes/Formats/class.tx_dlf_teihdr.php",
            "Classes/Hooks/class.tx_dlf_doctype.php",
            "Classes/Hooks/class.tx_dlf_em.php",
            "Classes/Hooks/class.tx_dlf_hacks.php",
            "Classes/Hooks/class.tx_dlf_tceforms.php",
            "Classes/Hooks/class.tx_dlf_tcemain.php",
            "modules/indexing/index.php",
            "modules/newclient/index.php",
            "plugins/audioplayer/class.tx_dlf_audioplayer.php",
            "plugins/basket/class.tx_dlf_basket.php",
            "plugins/collection/class.tx_dlf_collection.php",
            "plugins/feeds/class.tx_dlf_feeds.php",
            "plugins/listview/class.tx_dlf_listview.php",
            "plugins/metadata/class.tx_dlf_metadata.php",
            "plugins/navigation/class.tx_dlf_navigation.php",
            "plugins/newspaper/class.tx_dlf_newspaper.php",
            "plugins/oai/class.tx_dlf_oai.php",
            "plugins/pagegrid/class.tx_dlf_pagegrid.php",
            "plugins/pageview/class.tx_dlf_pageview.php",
            "plugins/search/class.tx_dlf_search.php",
            "plugins/search/class.tx_dlf_search_suggest.php",
            "plugins/statistics/class.tx_dlf_statistics.php",
            "plugins/toc/class.tx_dlf_toc.php",
            "plugins/toolbox/class.tx_dlf_toolbox.php",
            "plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php",
            "plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php",
            "plugins/toolbox/tools/imagemanipulation/class.tx_dlf_toolsImagemanipulation.php",
            "plugins/toolbox/tools/imagedownload/class.tx_dlf_toolsImagedownload.php",
            "plugins/validator/class.tx_dlf_validator.php"
        ]
    ]
];
