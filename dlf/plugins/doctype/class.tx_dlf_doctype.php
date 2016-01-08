<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
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
 * Plugin 'DLF: Doctype Plugin' for the 'dlf' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_doctype extends tx_dlf_plugin {

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

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if required variables are not set.
			return $content;

		}

		$toc = $this->doc->tableOfContents;

		/*
		 * Get the document type
		 *
		 * 1. newspaper
		 *    case 1) - type=newspaper
		 *    		  - children array([0], [1], [2], ...) -> type = year --> Newspaper Anchor File
		 *    case 2) - type=newspaper
		 *    		  - children array([0]) --> type = year
		 * 			  - children array([0], [1], [2], ...) --> type = month --> Year Anchor File
		 *    case 3) - type=newspaper
		 *    		  - children array([0]) --> type = year
		 * 			  - children array([0]) --> type = month
		 * 			  - children array([0], [1], [2], ...) --> type = day --> Issue
		 *
		 * 2. volume
		 *    case 1) - type=periodical
		 *			  - children array([0], [1], [2], ...) --> type = volume
		 * 			  - children array([0], [1], [2], ...) --> type = issue | front_cover
		 *
		 */

		switch ($toc[0]['type']) {
			case 'newspaper':
				if (count($toc[0]['children']) > 1) {
					$ret = 'newspaper_global_anchor';
				} else {
					if (count($toc[0]['children']) == 1 && count($toc[0]['children'][0]['children']) == 0) {

						// it's possible to have only one year in the global anchor file
						$ret = 'newspaper_global_anchor';

					} else if (count($toc[0]['children']) == 1 && count($toc[0]['children'][0]['children']) > 1) {

						// one year, multiple month
						$ret = 'newspaper_year_anchor';

					} else if (count($toc[0]['children']) == 1 && count($toc[0]['children'][0]['children']) == 1 && count($toc[0]['children'][0]['children'][0]['children']) > 1) {

						// one year, one month, multiple days
						$ret = 'newspaper_year_anchor';

					} else if (count($toc[0]['children']) == 1 && count($toc[0]['children'][0]['children']) == 1 && count($toc[0]['children'][0]['children'][0]['children']) == 1 && empty($toc[0]['children'][0]['children'][0]['children'][0]['children'][0]['dmdId'])) {

						// one year, one month, single month
						$ret = 'newspaper_year_anchor';

					} else {

						$ret = 'newspaper_issue';

					}
				}
				break;
			case 'periodical':
					$ret = 'periodical';
				break;
			default:
				$ret = $toc[0]['type'];
		}

		return $ret;

	}


	/**
	 * All the needed configuration values are stored in class variables
	 *
	 * @access	protected
	 *
	 * @param	array	$conf: configuration array from TS-Template
	 *
	 * @return	void
	 */
	protected function init(array $conf) {

		// in fact, we only need this line :-)
		$this->conf = $conf;

		// Set default plugin variables.
		//~ $this->pi_setPiVarDefaults();

		// Load translation files.
		//~ $this->pi_loadLL();

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/doctype/class.tx_dlf_doctype.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/doctype/class.tx_dlf_doctype.php']);
}
