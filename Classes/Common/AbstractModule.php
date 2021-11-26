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

namespace Kitodo\Dlf\Common;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Abstract module class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
abstract class AbstractModule
{
    public $extKey = 'dlf';
    public $prefixId = 'tx_dlf';

    /**
     * Holds the page record if access granted or false if access denied
     *
     * @var mixed
     * @access protected
     */
    protected $pageInfo;

    /**
     * Holds the module's marker array
     *
     * @var array
     * @access protected
     */
    protected $markerArray = [];

    /**
     * Holds the PSR-7 response object
     *
     * @var \Psr\Http\Message\ResponseInterface
     * @access protected
     */
    protected $response;

    /**
     * Holds the module's subpart array
     *
     * @var array
     * @access protected
     */
    protected $subpartArray = [];

    /**
     * Holds the TYPO3_CONF_VARS array of this extension
     *
     * @var array
     * @access protected
     */
    protected $conf = [];

    /**
     * Holds the submitted form's data
     *
     * @var array
     * @access protected
     */
    protected $data;

    /**
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Main function of the module.
     *
     * @access public
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request: The request object
     *
     * @abstract
     *
     * @return \Psr\Http\Message\ResponseInterface The response object
     */
    abstract public function main(\Psr\Http\Message\ServerRequestInterface $request);

    /**
     * Fills the response object with the module's output.
     *
     * @access protected
     *
     * @return string
     */
    protected function printContent()
    {
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:' . $this->extKey . '/Resources/Private/Templates/' . Helper::getUnqualifiedClassName(get_class($this)) . '.tmpl');
        $this->doc->backPath = $GLOBALS['BACK_PATH'];
        $this->doc->bodyTagAdditions = 'class="ext-' . $this->extKey . '-modules"';
        $this->doc->form = '<form action="" method="post" enctype="multipart/form-data">';
        // Add Javascript for function menu.
        $this->doc->JScode .= '<script type="text/javascript">script_ended = 0;function jumpToUrl(URL) { document.location = URL; }</script>';
        // Add Javascript for convenient module switch.
        $this->doc->postCode .= '<script type="text/javascript">script_ended = 1;</script>';
        // Render output.
        $this->content .= $this->doc->startPage($languageService->sL('title'));
        // Set defaults for menu.
        if (empty($this->markerArray['CSH'])) {
            $this->markerArray['CSH'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_' . $GLOBALS['MCONF']['name'], 'csh');
        }
        if (empty($this->markerArray['MOD_MENU'])) {
            $this->markerArray['MOD_MENU'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
        }
        $this->content .= $this->doc->moduleBody($this->pageInfo, [], $this->markerArray, $this->subpartArray);
        $this->content .= $this->doc->endPage();
        return $this->content;
    }

    /**
     * Initializes the backend module.
     *
     * @access public
     *
     * @return void
     */
    public function __construct()
    {
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $languageService->includeLLFile('EXT:' . $this->extKey . '/Resources/Private/Language/' . Helper::getUnqualifiedClassName(get_class($this)) . '.xml');
        // Read extension configuration.
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey]) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey])) {
            $this->conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);
        }
        $this->data = GeneralUtility::_GPmerged($this->prefixId);
    }

    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     *
     * @see menuConfig()
     */
    public function init()
    {
        // Name might be set from outside
        if (!$this->MCONF['name']) {
            $this->MCONF = $GLOBALS['MCONF'];
        }
        $this->id = (int)GeneralUtility::_GP('id');
        $this->CMD = GeneralUtility::_GP('CMD');
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->menuConfig();
        $this->handleExternalFunctionValue();
    }

    /**
     * Returns the Backend User
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Initializes the internal MOD_MENU array setting and unsetting items based on various conditions. It also merges in external menu items from the global array TBE_MODULES_EXT (see mergeExternalItems())
     * Then MOD_SETTINGS array is cleaned up (see \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()) so it contains only valid values. It's also updated with any SET[] values submitted.
     * Also loads the modTSconfig internal variable.
     *
     * @see init(), $MOD_MENU, $MOD_SETTINGS, \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData(), mergeExternalItems()
     */
    public function menuConfig()
    {
        // Page/be_user TSconfig settings and blinding of menu-items
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $this->modTSconfig = $userTsConfig['mod.'][$this->MCONF['name']];
        $this->MOD_MENU['function'] = $this->mergeExternalItems($this->MCONF['name'], 'function', $this->MOD_MENU['function']);
        $this->MOD_MENU['function'] = $this->unsetMenuItems($this->modTSconfig['properties'], $this->MOD_MENU['function'], 'menu.function');
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->MCONF['name'], $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
    }

    /**
     * Removes menu items from $itemArray if they are configured to be removed by TSconfig for the module ($modTSconfig)
     * See Inside TYPO3 about how to program modules and use this API.
     *
     * @param array $modTSconfig Module TS config array
     * @param array $itemArray Array of items from which to remove items.
     * @param string $TSref $TSref points to the "object string" in $modTSconfig
     * @return array The modified $itemArray is returned.
     */
    public function unsetMenuItems($modTSconfig, $itemArray, $TSref)
    {
        // Getting TS-config options for this module for the Backend User:
        if (is_array($modTSconfig)) {
            foreach ($modTSconfig as $key => $val) {
                if (!$val) {
                    unset($itemArray[$key]);
                }
            }
        }
        return $itemArray;
    }

    /**
     * Merges menu items from global array $TBE_MODULES_EXT
     *
     * @param string $modName Module name for which to find value
     * @param string $menuKey Menu key, eg. 'function' for the function menu.
     * @param array $menuArr The part of a MOD_MENU array to work on.
     * @return array Modified array part.
     * @access private
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(), menuConfig()
     */
    public function mergeExternalItems($modName, $menuKey, $menuArr)
    {
        $mergeArray = $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
        if (is_array($mergeArray)) {
            foreach ($mergeArray as $k => $v) {
                if (((string)$v['ws'] === '' || $this->getBackendUser()->workspace === 0 && GeneralUtility::inList($v['ws'], 'online')) || $this->getBackendUser()->workspace === -1 && GeneralUtility::inList($v['ws'], 'offline') || $this->getBackendUser()->workspace > 0 && GeneralUtility::inList($v['ws'], 'custom')) {
                    $menuArr[$k] = $this->getLanguageService()->sL($v['title']);
                }
            }
        }
        return $menuArr;
    }

    /**
     * Loads $this->extClassConf with the configuration for the CURRENT function of the menu.
     *
     * @param string $MM_key The key to MOD_MENU for which to fetch configuration. 'function' is default since it is first and foremost used to get information per "extension object" (I think that is what its called)
     * @param string $MS_value The value-key to fetch from the config array. If NULL (default) MOD_SETTINGS[$MM_key] will be used. This is useful if you want to force another function than the one defined in MOD_SETTINGS[function]. Call this in init() function of your Script Class: handleExternalFunctionValue('function', $forcedSubModKey)
     * @see getExternalItemConfig(), init()
     */
    public function handleExternalFunctionValue($MM_key = 'function', $MS_value = null)
    {
        if ($MS_value === null) {
            $MS_value = $this->MOD_SETTINGS[$MM_key];
        }
        $this->extClassConf = $this->getExternalItemConfig($this->MCONF['name'], $MM_key, $MS_value);
    }

    /**
     * Returns configuration values from the global variable $TBE_MODULES_EXT for the module given.
     * For example if the module is named "web_info" and the "function" key ($menuKey) of MOD_SETTINGS is "stat" ($value) then you will have the values of $TBE_MODULES_EXT['webinfo']['MOD_MENU']['function']['stat'] returned.
     *
     * @param string $modName Module name
     * @param string $menuKey Menu key, eg. "function" for the function menu. See $this->MOD_MENU
     * @param string $value Optionally the value-key to fetch from the array that would otherwise have been returned if this value was not set. Look source...
     * @return mixed The value from the TBE_MODULES_EXT array.
     * @see handleExternalFunctionValue()
     */
    public function getExternalItemConfig($modName, $menuKey, $value = '')
    {
        if (isset($GLOBALS['TBE_MODULES_EXT'][$modName])) {
            return (string)$value !== '' ? $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey][$value] : $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
        }
        return null;
    }
}
