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
            'php' => '7.0.8-',
            'typo3' => '7.0-',
        ),
        'conflicts' => array (
        ),
        'suggests' => array (
        ),
    ),
    '_md5_values_when_last_written' => '',
);
