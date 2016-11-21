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
 * Plugin 'DLF: Interaction' for the 'dlf' extension.
 *
 * @author  Christopher Timm <timm@effective-webwork.de>
 * @package TYPO3
 * @subpackage  tx_dlf
 * @access  public
 */
class tx_dlf_interaction extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/interaction/class.tx_dlf_interaction.php';

	/**
	 * The main method of the PlugIn
	 *
	 * @access  public
	 *
	 * @param   string	  $content: The PlugIn content
	 * @param   array	   $conf: The PlugIn configuration
	 *
	 * @return  string	  The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// Don't cache the output.
		$this->setCache(FALSE);

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/interaction/template.tmpl'), '###TEMPLATE###');

		}

		if ($this->conf['targetBasket'] && $this->conf['basketGoToButton'] && $this->piVars['id']) {

			$label = $this->pi_getLL('goBasket', '', TRUE);

			$basketConf = array (
				'parameter' => $this->conf['targetBasket'],
				'title' => $label
			);

			$markerArray['###BASKET###'] = $this->cObj->typoLink($label, $basketConf);

		} else {

			$markerArray['###BASKET###'] = '';

		}

		// Add basket button
		if ($this->conf['basketButton'] && $this->conf['targetBasket'] && $this->piVars['id']) {

			$label = $this->pi_getLL('addBasket', '', TRUE);

			$params = array(
				'id' => $this->piVars['id'],
				'addToBasket' => true
			);

			if (empty($this->piVars['page'])) {

				$params['page'] = 1;

			}

			$basketConf = array (
				'parameter' => $this->conf['targetBasket'],
				'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, $params, '', TRUE, FALSE),
				'title' => $label
			);

			$output = '<form id="addToBasketForm" action="'.$this->cObj->typoLink_URL($basketConf).'" method="post">';

			$output .= '<input type="hidden" name="tx_dlf[startpage]" id="startpage" value="'.$this->piVars['page'].'">';

			$output .= '<input type="hidden" name="tx_dlf[endpage]" id="endpage" value="'.$this->piVars['page'].'">';

			$output .= '<input type="hidden" name="tx_dlf[startX]" id="startX">';

			$output .= '<input type="hidden" name="tx_dlf[startY]" id="startY">';

			$output .= '<input type="hidden" name="tx_dlf[endX]" id="endX">';

			$output .= '<input type="hidden" name="tx_dlf[endY]" id="endY">';

			$output .= '<input type="hidden" name="tx_dlf[rotation]" id="rotation">';

			$output .= '<button id="submitBasketForm" onclick="this.form.submit()">'.$label.'</button>';

			$output .= '</form>';

			$output .= '<script>';

			$output .= '
			$(document).ready(function() {
				$("#submitBasketForm").click(function() {
					$("#addToBasketForm").submit();
				});
			});';

			$output .= '</script>';

			$markerArray['###BASKETBUTTON###'] = $output;

			$markerArray['###EDITBUTTON###'] = '<a href="javascript: tx_dlf_viewer.activateSelection();">'.$this->pi_getLL('editMode', '', TRUE).'</a>';

			$markerArray['###EDITREMOVE###'] = '<a href="javascript: tx_dlf_viewer.resetCropSelection();">'.$this->pi_getLL('editRemove', '', TRUE).'</a>';

			$markerArray['###MAGNIFIER###'] = '<a href="javascript: tx_dlf_viewer.activateMagnifier();">'.$this->pi_getLL('magnifier', '', TRUE).'</a>';

			$markerArray['###ROTATELEFT###'] = '<a href="javascript: tx_dlf_viewer.map.rotate(90);">'.$this->pi_getLL('rotateleft', '', TRUE).'</a>';

			$markerArray['###ROTATERIGHT###'] = '<a href="javascript: tx_dlf_viewer.map.rotate(-90);">'.$this->pi_getLL('rotateright', '', TRUE).'</a>';

		} else {

			$markerArray['###BASKETBUTTON###'] = '';

		}

		$content .= $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/interaction/class.tx_dlf_interaction.php'])  {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/interaction/class.tx_dlf_interaction.php']);
}
