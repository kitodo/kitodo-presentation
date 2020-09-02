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
// Register static typoscript.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/',
    'Basic Configuration'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/Search/',
    'Search Facets Configuration'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/TableOfContents/',
    'Table of Contents Menu Configuration'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/Toolbox/',
    'Toolbox Default Tool Templates'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/VideoPlayer/',
    'Default Setup for Media Player Plugin'
);
