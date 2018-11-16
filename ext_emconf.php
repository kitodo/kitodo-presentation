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

$EM_CONF[$_EXTKEY] = array (
    'title' => 'Kitodo.Presentation',
    'description' => 'Base plugins, modules, services and API of the Digital Library Framework. It is part of the community-based Kitodo Digitization Suite.',
    'category' => 'fe',
    'author' => 'Kitodo. Key to digital objects e.V.',
    'author_email' => 'contact@kitodo.org',
    'author_company' => 'http://www.kitodo.org/',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => TRUE,
    'createDirs' => '',
    'clearCacheOnLoad' => FALSE,
    'version' => '2.2.0',
    'constraints' => array (
        'depends' => array (
            'php' => '7.0.0-',
            'typo3' => '7.6.0-',
        ),
        'conflicts' => array (
        ),
        'suggests' => array (
        ),
    ),
    'autoload' => array (
        'classmap' => array (
            'vendor/solarium',
            'vendor/symfony/event-dispatcher',
            'cli/class.tx_dlf_cli.php',
            'common/class.tx_dlf_alto.php',
            'common/class.tx_dlf_document.php',
            'common/class.tx_dlf_format.php',
            'common/class.tx_dlf_fulltext.php',
            'common/class.tx_dlf_helper.php',
            'common/class.tx_dlf_indexing.php',
            'common/class.tx_dlf_list.php',
            'common/class.tx_dlf_mods.php',
            'common/class.tx_dlf_module.php',
            'common/class.tx_dlf_plugin.php',
            'common/class.tx_dlf_solr.php',
            'common/class.tx_dlf_teihdr.php',
            'hooks/class.tx_dlf_doctype.php',
            'hooks/class.tx_dlf_em.php',
            'hooks/class.tx_dlf_hacks.php',
            'hooks/class.tx_dlf_tceforms.php',
            'hooks/class.tx_dlf_tcemain.php',
            'modules/indexing/index.php',
            'modules/newclient/index.php',
            'plugins/audioplayer/class.tx_dlf_audioplayer.php',
            'plugins/basket/class.tx_dlf_basket.php',
            'plugins/collection/class.tx_dlf_collection.php',
            'plugins/feeds/class.tx_dlf_feeds.php',
            'plugins/listview/class.tx_dlf_listview.php',
            'plugins/metadata/class.tx_dlf_metadata.php',
            'plugins/navigation/class.tx_dlf_navigation.php',
            'plugins/newspaper/class.tx_dlf_newspaper.php',
            'plugins/oai/class.tx_dlf_oai.php',
            'plugins/pagegrid/class.tx_dlf_pagegrid.php',
            'plugins/pageview/class.tx_dlf_pageview.php',
            'plugins/search/class.tx_dlf_search.php',
            'plugins/search/class.tx_dlf_search_suggest.php',
            'plugins/statistics/class.tx_dlf_statistics.php',
            'plugins/toc/class.tx_dlf_toc.php',
            'plugins/toolbox/class.tx_dlf_toolbox.php',
            'plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php',
            'plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php',
            'plugins/toolbox/tools/imagemanipulation/class.tx_dlf_toolsImagemanipulation.php',
            'plugins/toolbox/tools/imagedownload/class.tx_dlf_toolsImagedownload.php',
            'plugins/validator/class.tx_dlf_validator.php',
        ),
    ),
    '_md5_values_when_last_written' => '',
);
