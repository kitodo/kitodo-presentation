<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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
 * Base class 'tx_dlf_plugin' for the 'dlf' extension.
*
* @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
* @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
* @package	TYPO3
* @subpackage	tx_dlf
* @access	public
* @abstract
*/
abstract class tx_dlf_plugin extends tslib_pibase {

	public $extKey = 'dlf';

	public $prefixId = 'tx_dlf';

	public $scriptRelPath = 'common/class.tx_dlf_plugin.php';

	// Plugins are cached by default (@see setCache()).
	public $pi_USER_INT_obj = FALSE;

	public $pi_checkCHash = TRUE;

	/**
	 * This holds the current document
	 *
	 * @var	tx_dlf_document
	 * @access protected
	 */
	protected $doc;

	/**
	 * This holds the plugin's parsed template
	 *
	 * @var	string
	 * @access protected
	 */
	protected $template = '';

	/**
	 * All the needed configuration values are stored in class variables
	 * Priority: Flexforms > TS-Templates > Extension Configuration > ext_localconf.php
	 *
	 * @access	protected
	 *
	 * @param	array		$conf: configuration array from TS-Template
	 *
	 * @return	void
	 */
	protected function init(array $conf) {

		// Read FlexForm configuration.
		$flexFormConf = array ();

		$this->cObj->readFlexformIntoConf($this->cObj->data['pi_flexform'], $flexFormConf);

		if (!empty($flexFormConf)) {

			$conf = t3lib_div::array_merge_recursive_overrule($flexFormConf, $conf);

		}

		// Read plugin TS configuration.
		$pluginConf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][get_class($this).'.'];

		if (is_array($pluginConf)) {

			$conf = t3lib_div::array_merge_recursive_overrule($pluginConf, $conf);

		}

		// Read general TS configuration.
		$generalConf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'];

		if (is_array($generalConf)) {

			$conf = t3lib_div::array_merge_recursive_overrule($generalConf, $conf);

		}

		// Read extension configuration.
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

		if (is_array($extConf)) {

			$conf = t3lib_div::array_merge_recursive_overrule($extConf, $conf);

		}

		// Read TYPO3_CONF_VARS configuration.
		$varsConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey];

		if (is_array($varsConf)) {

			$conf = t3lib_div::array_merge_recursive_overrule($varsConf, $conf);

		}

		$this->conf = $conf;

		// Set default plugin variables.
		$this->pi_setPiVarDefaults();

		// Load translation files.
		$this->pi_loadLL();

	}

	/**
	 * Loads the current document into $this->doc
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function loadDocument() {

		// Check for required variable.
		if (!empty($this->piVars['id']) && !empty($this->conf['pages'])) {

			// Should we exclude documents from other pages than $this->conf['pages']?
			$pid = (!empty($this->conf['excludeOther']) ? intval($this->conf['pages']) : 0);

			// Get instance of tx_dlf_document.
			$this->doc = tx_dlf_document::getInstance($this->piVars['id'], $pid);

			if (!$this->doc->ready) {

				// Destroy the incomplete object.
				trigger_error('Failed to load document with UID '.$this->piVars['id'], E_USER_ERROR);

				$this->doc = NULL;

			} else {

				// Set configuration PID.
				$this->doc->cPid = $this->conf['pages'];

			}

		} elseif (!empty($this->piVars['recordId'])) {

			// Get UID of document with given record identifier.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tx_dlf_documents.uid',
					'tx_dlf_documents',
					'tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['recordId'], 'tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_documents'),
					'',
					'',
					'1'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 1) {

				list ($this->piVars['id']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

				// Set superglobal $_GET array.
				$_GET[$this->prefixId]['id'] = $this->piVars['id'];

				// Unset variable to avoid infinite looping.
				unset ($this->piVars['recordId'], $_GET[$this->prefixId]['recordId']);

				// Try to load document.
				$this->loadDocument();

			} else {

				trigger_error('Failed to load document with record ID '.$this->piVars['recordId'], E_USER_ERROR);

			}

		} else {

			trigger_error('No UID or PID given for document', E_USER_ERROR);

		}

	}

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @abstract
	 *
	 * @return	string		The content that is displayed on the website
	 */
	abstract public function main($content, $conf);

	/**
	 * Wraps the input string in a tag with the class attribute set to the class name
	 *
	 * @access	public
	 *
	 * @param	string		$content: HTML content to wrap in the div-tags with the "main class" of the plugin
	 *
	 * @return	string		HTML content wrapped, ready to return to the parent object.
	 */
	public function pi_wrapInBaseClass($content) {

		// Use get_class($this) instead of $this->prefixId for content wrapping because $this->prefixId is the same for all plugins.
		$content = '<div class="'.str_replace('_', '-', get_class($this)).'">'.$content.'</div>';

		if (!$GLOBALS['TSFE']->config['config']['disablePrefixComment']) {

			$content = "\n\n<!-- BEGIN: Content of extension '".$this->extKey."', plugin '".get_class($this)."'	-->\n\n".$content."\n\n<!-- END: Content of extension '".$this->extKey."', plugin '".get_class($this)."' -->\n\n";

		}

		return $content;

	}

	/**
	 * Parses a string into a typoscript array
	 *
	 * @access	protected
	 *
	 * @param	string		$string: The string to parse
	 *
	 * @return	array		The resulting typoscript array
	 */
	protected function parseTS($string = '') {

		$parser = t3lib_div::makeInstance('t3lib_TSparser');

		$parser->parse($string);

		return $parser->setup;

	}

	/**
	 * Sets some configuration variables if the plugin is cached.
	 *
	 * @access	protected
	 *
	 * @param	boolean		$cache: Should the plugin be cached?
	 *
	 * @return	void
	 */
	protected function setCache($cache = TRUE) {

		if ($cache) {

			// Set cObject type to "USER" (default).
			$this->pi_USER_INT_obj = FALSE;

			$this->pi_checkCHash = TRUE;

			if (count($this->piVars)) {

				// Check cHash or disable caching.
				$GLOBALS['TSFE']->reqCHash();

			}

		} else {

			// Set cObject type to "USER_INT".
			$this->pi_USER_INT_obj = TRUE;

			$this->pi_checkCHash = FALSE;

			// Plugins are of type "USER" by default, so convert it to "USER_INT".
			$this->cObj->convertToUserIntObject();

		}

	}

}

/* No xclasses for abstract classes!
 if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_plugin.php'])	{
include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_plugin.php']);
}
*/

?>