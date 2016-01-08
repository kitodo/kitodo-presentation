<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Base class 'tx_dlf_module' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 * @abstract
 */
abstract class tx_dlf_module extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	public $extKey = 'dlf';

	public $prefixId = 'tx_dlf';

	/**
	 * Holds the path to this module relative to 'EXT:dlf/modules/' and with trailing slash
	 *
	 * @var	string
	 * @access protected
	 */
	protected $modPath = '';

	/**
	 * Holds the page record if access granted or FALSE if access denied
	 *
	 * @var	mixed
	 * @access protected
	 */
	protected $pageInfo;

	/**
	 * Holds the module's button array
	 *
	 * @var	array
	 * @access protected
	 */
	protected $buttonArray = array ();

	/**
	 * Holds the module's marker array
	 *
	 * @var	array
	 * @access protected
	 */
	protected $markerArray = array ();

	/**
	 * Holds the module's subpart array
	 *
	 * @var	array
	 * @access protected
	 */
	protected $subpartArray = array ();

	/**
	 * Holds the TYPO3_CONF_VARS array of this extension
	 *
	 * @var	array
	 * @access protected
	 */
	protected $conf = array ();

	/**
	 * Holds the submitted form's data
	 *
	 * @var	array
	 * @access protected
	 */
	protected $data;

	/**
	 * Initializes the backend module by setting internal variables, initializing the menu.
	 *
	 * @access public
	 *
	 * @return	void
	 */
	public function __construct() {

		$GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], 1);

		$GLOBALS['LANG']->includeLLFile('EXT:'.$this->extKey.'/modules/'.$this->modPath.'locallang.xml');

		$this->setMOD_MENU();

		parent::init();

		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

		$this->pageInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);

		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');

		$this->doc->setModuleTemplate('EXT:'.$this->extKey.'/modules/'.$this->modPath.'template.tmpl');

		$this->doc->getPageRenderer()->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'res/backend.css');

		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		$this->doc->bodyTagAdditions = 'class="ext-'.$this->extKey.'-modules"';

		$this->doc->form = '<form action="" method="post" enctype="multipart/form-data">';

		$this->data = \TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged($this->prefixId);

	}

	/**
	 * Sets the module's MOD_MENU configuration.
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function setMOD_MENU() {

		// Set $this->MOD_MENU array here or leave empty.

		/* Example code:
		$this->MOD_MENU = array (
			'function' => array (
				'1' => $GLOBALS['LANG']->getLL('function1'),
				'2' => $GLOBALS['LANG']->getLL('function2'),
				'3' => $GLOBALS['LANG']->getLL('function3'),
			)
		); */

	}

	/**
	 * Main function of the module.
	 *
	 * @access	public
	 *
	 * @abstract
	 *
	 * @return	void
	 */
	abstract public function main();

	/**
	 * Outputs all contents.
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function printContent() {

		// Add Javascript for function menu.
		$this->doc->JScode .= '
		<script type="text/javascript">
		script_ended = 0;
		function jumpToUrl(URL)	{
			document.location = URL;
		}
		</script>';

		// Add Javascript for convenient module switch.
		$this->doc->postCode .= '
		<script type="text/javascript">
		script_ended = 1;
		</script>';

		// Render output.
		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));

		// Set defaults for buttons and menu.
		if (empty($this->buttonArray['RELOAD'])) {

			$this->buttonArray['RELOAD'] = '<a href="' . $GLOBALS['MCONF']['_'] . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.reload', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';

		}

		if (empty($this->buttonArray['SHORTCUT'])) {

			$this->buttonArray['SHORTCUT'] = $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);

		}

		if (empty($this->markerArray['CSH'])) {

			$this->markerArray['CSH'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_'.$GLOBALS['MCONF']['name'], 'csh', $GLOBALS['BACK_PATH'], '', TRUE);

		}

		if (empty($this->markerArray['MOD_MENU'])) {

			$this->markerArray['MOD_MENU'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);

		}

		$this->content .= $this->doc->moduleBody($this->pageInfo, $this->buttonArray, $this->markerArray, $this->subpartArray);

		$this->content .= $this->doc->endPage();

		echo $this->content;

	}

}

/* No xclasses for abstract classes!
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_module.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_module.php']);
}
*/
