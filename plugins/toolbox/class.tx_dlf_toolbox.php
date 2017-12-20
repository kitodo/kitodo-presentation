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

/**
 * Plugin 'DLF: Toolbox' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_toolbox extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toolbox/class.tx_dlf_toolbox.php';

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// Quit without doing anything if required variable is not set.
		if (empty($this->piVars['id'])) {

			return $content;

		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toolbox/template.tmpl'), '###TEMPLATE###');

		}

		// Build data array.
		$data = array (
			'conf' => $this->conf,
			'piVars' => $this->piVars,
		);

		// Get template subpart for tools.
		$subpart = $this->cObj->getSubpart($this->template, '###TOOLS###');

		$tools = explode(',', $this->conf['tools']);

		// Add the tools to the toolbox.
		foreach ($tools as $tool) {

			$tool = trim($tool);

			$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

			$cObj->data = $data;

			$content .= $this->cObj->substituteMarkerArray($subpart, array ('###TOOL###' => $cObj->cObjGetSingle($GLOBALS['TSFE']->tmpl->setup['plugin.'][$tool], $GLOBALS['TSFE']->tmpl->setup['plugin.'][$tool.'.'])));

		}

		return $this->pi_wrapInBaseClass($this->cObj->substituteSubpart($this->template, '###TOOLS###', $content, TRUE));

	}

}
