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
    'author_company' => '<br /><a href="http://www.kitodo.org/" target="_blank">Kitodo.org</a><br /><a href="https://github.com/kitodo" target="_blank">Kitodo on GitHub</a>',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => TRUE,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => FALSE,
    'lockType' => '',
    'version' => '2.1.0',
    'constraints' => array (
        'depends' => array (
            'php' => '5.3.7-',
            'typo3' => '6.2.0-7.9.99',
        ),
        'conflicts' => array (
        ),
        'suggests' => array (
        ),
    ),
    '_md5_values_when_last_written' => '',
);
