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
        'psr-4' => [
            'Kitodo\\Dlf\\' => 'Classes/'
        ],
        'classmap' => [
            'modules/indexing/index.php',
            'modules/newclient/index.php',
            'plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php',
            'plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php',
            'plugins/toolbox/tools/imagemanipulation/class.tx_dlf_toolsImagemanipulation.php',
            'plugins/toolbox/tools/imagedownload/class.tx_dlf_toolsImagedownload.php'
        ]
    ]
];
