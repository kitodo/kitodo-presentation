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
// Register static typoscript.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/',
    'Basic Configuration'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/Toolbox/',
    'Toolbox Default Tool Templates'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/Plugins/Feeds/',
    'RSS Feed Plugin Configuration'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'dlf',
    'Configuration/TypoScript/Plugins/OaiPmh/',
    'OAI-PMH Plugin Configuration'
);
