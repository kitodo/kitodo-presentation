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
    die ('Access denied.');
}

// Register modules.
if (TYPO3_MODE == 'BE') {

    // Add modules after "web".
    if (!isset($TBE_MODULES['txdlfmodules'])) {

        $modules = array ();

        foreach ($TBE_MODULES as $key => $val) {

            if ($key == 'web') {

                $modules[$key] = $val;

                $modules['txdlfmodules'] = '';

            } else {

                $modules[$key] = $val;

            }

        }

        $TBE_MODULES = $modules;

        unset($modules);

    }

    // Main "dlf" module.
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', '', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent('txdlfmodules', 'typo3-pagetree');

    // Module "indexing".
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', 'txdlfindexing', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/indexing/');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_txdlfmodules_txdlfindexing', 'EXT:dlf/modules/indexing/locallang_mod.xml');

    // Module "newclient".
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txdlfmodules', 'txdlfnewclient', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'modules/newclient/');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_txdlfmodules_txdlfnewclient', 'EXT:dlf/modules/newclient/locallang_mod.xml');

}
