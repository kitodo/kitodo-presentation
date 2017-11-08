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
 * Tool 'Image manipulation' for the plugin 'DLF: Toolbox' of the 'dlf' extension.
 *
 * @author	Jacob Mendt <Jacob.Mendt@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_toolsImagemanipulation extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/toolbox/tools/imagemanipulation/class.tx_dlf_toolsImagemanipulation.php';

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

		// Merge configuration with conf array of toolbox.
		$this->conf = tx_dlf_helper::array_merge_recursive_overrule($this->cObj->data['conf'], $this->conf);

		// Load current document.
		$this->loadDocument();

		// Load template file.
		if (!empty($this->conf['toolTemplateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['toolTemplateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/toolbox/tools/imagemanipulation/template.tmpl'), '###TEMPLATE###');

		}

		$markerArray['###IMAGEMANIPULATION_SELECT###'] = '<span class="tx-dlf-tools-imagetools" id="tx-dlf-tools-imagetools" data-dic="imagemanipulation-on:'
			.$this->pi_getLL('imagemanipulation-on', '', TRUE).';imagemanipulation-off:'
			.$this->pi_getLL('imagemanipulation-off', '', TRUE).';reset:'
			.$this->pi_getLL('reset', '', TRUE).';saturation:'
			.$this->pi_getLL('saturation', '', TRUE).';hue:'
			.$this->pi_getLL('hue', '', TRUE).';contrast:'
			.$this->pi_getLL('contrast', '', TRUE).';brightness:'
			.$this->pi_getLL('brightness', '', TRUE).';invert:'
			.$this->pi_getLL('invert', '', TRUE).'" title="'
			.$this->pi_getLL('no-support', '', TRUE).'"></span>';

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}
