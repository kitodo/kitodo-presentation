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

/**
 * Abstract module class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
abstract class AbstractModule extends \TYPO3\CMS\Backend\Module\BaseScriptClass
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
        $this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
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
        $GLOBALS['LANG']->includeLLFile('EXT:' . $this->extKey . '/Resources/Private/Language/' . Helper::getUnqualifiedClassName(get_class($this)) . '.xml');
        // Read extension configuration.
        if (isset($‪GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey]) && is_array($‪GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey])) {
            $this->conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);
        }
        $this->data = GeneralUtility::_GPmerged($this->prefixId);
    }
}
