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
// Define constants.
if (!defined('DEVLOG_SEVERITY_OK')) {
    define ('DEVLOG_SEVERITY_OK', -1);
}
if (!defined('DEVLOG_SEVERITY_INFO')) {
    define ('DEVLOG_SEVERITY_INFO', 0);
}
if (!defined('DEVLOG_SEVERITY_NOTICE')) {
    define ('DEVLOG_SEVERITY_NOTICE', 1);
}
if (!defined('DEVLOG_SEVERITY_WARNING')) {
    define ('DEVLOG_SEVERITY_WARNING', 2);
}
if (!defined('DEVLOG_SEVERITY_ERROR')) {
    define ('DEVLOG_SEVERITY_ERROR', 3);
}
// Register plugins.
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\AudioPlayer::class, '_audioplayer', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Basket::class, '_basket', 'list_type', FALSE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Calendar::class, '_calendar', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Collection::class, '_collection', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Feeds::class, '_feeds', 'list_type', FALSE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\ListView::class, '_listview', 'list_type', FALSE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Metadata::class, '_metadata', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Navigation::class, '_navigation', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\OaiPmh::class, '_oaipmh', 'list_type', FALSE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\PageGrid::class, '_pagegrid', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\PageView::class, '_pageview', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Search::class, '_search', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Statistics::class, '_statistics', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\TableOfContents::class, '_tableofcontents', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Toolbox::class, '_toolbox', 'list_type', TRUE);
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Validator::class, '_validator', 'list_type', FALSE);
// Register tools for toolbox plugin.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'] = [];
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Tools\FulltextTool::class, '_fulltexttool', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_fulltexttool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.fulltexttool';
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Tools\AnnotationTool::class, '_annotationtool', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_annotationtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.annotationtool';
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Tools\ImageDownloadTool::class, '_imagedownloadtool', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_imagedownloadtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.imagedownloadtool';
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Tools\ImageManipulationTool::class, '_imagemanipulationtool', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_imagemanipulationtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.imagemanipulationtool';
\Kitodo\Dlf\Hooks\ExtensionManagementUtility::addPItoST43($_EXTKEY, \Kitodo\Dlf\Plugin\Tools\PdfDownloadTool::class, '_pdfdownloadtool', '', TRUE);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'][\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY).'_pdfdownloadtool'] = 'LLL:EXT:dlf/Resources/Private/Language/Labels.xml:tx_dlf_toolbox.pdfdownloadtool';
// Register hooks.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \Kitodo\Dlf\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Common/MetsDocument.php']['hookClass'][] = \Kitodo\Dlf\Hooks\KitodoProductionHacks::class;
// Register AJAX eID handlers.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_search_suggest'] = \Kitodo\Dlf\Plugin\Eid\SearchSuggest::class.'::main';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_dlf_pageview_proxy'] = \Kitodo\Dlf\Plugin\Eid\PageViewProxy::class.'::main';
// Register Typoscript user function.
if (TYPO3_MODE === 'FE') {
    /**
     * docTypeCheck user function to use in Typoscript
     * @example [userFunc = user_dlf_docTypeCheck($type)]
     *
     * @access public
     *
     * @param string $type: document type string to test for
     *
     * @return boolean TRUE if document type matches, FALSE if not
     */
    function user_dlf_docTypeCheck($type) {
        $hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Kitodo\Dlf\Common\DocumentTypeCheck::class);
        return ($hook->getDocType() === $type);
    }
}
