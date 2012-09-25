<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Henrik Lochmann <dev@mentalmotive.com>
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
 * Plugin 'DLF: Page Grid' for the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_pagegrid extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/pagegrid/class.tx_dlf_pagegrid.php';

	/**
	 * Renders thumbnail and page number for one page of the currently shown document.
	 *
	 * @access	protected
	 *
	 * @param	integer		$number: The page to render
	 * @param	string		$template: Parsed template subpart
	 *
	 * @return	string		The rendered entry ready for output
	 */
	protected function getEntry($number, $template) {
	
		$markerArray = array();

		$thumbnailFile = $this->doc->getFileLocation($this->doc->physicalPagesInfo[$this->doc->physicalPages[$number]]['files'][strtolower($this->conf['fileGrpThumbs'])]);
		 
		$markerArray['###THUMBNAIL###'] = '<a href="'.$this->pi_linkTP_keepPIvars_url(array ('page' => $number), TRUE, FALSE, $this->conf['targetPid']).'"><img src="'.$thumbnailFile.'" /></a>';
	
		$markerArray['###PAGE###'] = sprintf($this->pi_getLL('page'), $number + 1);
	
		return $this->cObj->substituteMarkerArray($template, $markerArray);
	
	}
	
	/**
	 * Renders the page browser, which shows itemCount pages beside the current page.
	 *
	 * @access	protected
	 *
	 * @param	integer		$itemCount: The number of page links to be shown beside the currently selected page
	 * 
	 * @return	string		The rendered page browser ready for output
	 */
	protected function getPagebrowser($itemCount = 3) {

		// Get overall number of pages.
		$maxPages = intval(ceil($this->doc->numPages / $this->conf['limit']));

		// Return empty pagebrowser if there is just one page.
		if ($maxPages < 2) {

			return '';

		}

		// Get separator.
		$separator = $this->pi_getLL('separator');

		// Add link to previous page.
		if ($this->piVars['pointer'] > 0) {

			$output = $this->pi_linkTP_keepPIvars($this->pi_getLL('firstPage'), array ('pointer' => 0), TRUE);
			
			$output .= $this->pi_linkTP_keepPIvars($this->pi_getLL('prevPage'), array ('pointer' => $this->piVars['pointer'] - 1), TRUE).$separator;

		} else {

			$output = $this->pi_getLL('firstPage');

			$output .= $this->pi_getLL('prevPage').$separator;

		}

		$lowerLimit = max($this->piVars['pointer'] - $itemCount, 0);
		
		if ($this->piVars['pointer'] + $itemCount >= $maxPages) {
			
			$lowerLimit -= max(($this->piVars['pointer'] + $itemCount) - ($maxPages - 1), 0);
			
		}
		
		
		for ($i = $lowerLimit; $i < ($lowerLimit + 2 * $itemCount + 1) && $i < $maxPages; $i++) {
			
			if ($this->piVars['pointer'] != $i) {
			
				$output .= $this->pi_linkTP_keepPIvars(sprintf('%d', $i + 1), array ('pointer' => $i), TRUE).$separator;
			
			} else {
			
				$output .= '<strong>'.sprintf('%d', $i + 1).'</strong>'.$separator;
			
			}
			
		}
		
		// Add link to next page.
		if ($this->piVars['pointer'] < $maxPages - 1) {

			$output .= $this->pi_linkTP_keepPIvars($this->pi_getLL('nextPage'), array ('pointer' => $this->piVars['pointer'] + 1), TRUE);
			
			$output .= $this->pi_linkTP_keepPIvars($this->pi_getLL('lastPage'), array ('pointer' => $maxPages - 1), TRUE);

		} else {

			$output .= $this->pi_getLL('nextPage');
			
			$output .= $this->pi_getLL('lastPage');

		}

		return $output;

	}

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

		// Don't cache the output.
		$this->setCache(FALSE);

		$this->loadDocument();
		
		if ($this->doc === NULL || $this->doc->numPages < 1) {
		
			// Quit without doing anything if required variables are not set.
			return $content;
		
		} else {
		
			// Set default values for page if not set.
			$this->piVars['pointer'] = t3lib_div::intInRange($this->piVars['pointer'], 0, $this->doc->numPages, 0);
		
		}
		
		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/pagegrid/template.tmpl'), '###TEMPLATE###');

		}

		$entryTemplate = $this->cObj->getSubpart($this->template, '###ENTRY###');
		
		if (empty($entryTemplate)) {
		
			if (TYPO3_DLOG) {
			
				t3lib_div::devLog('[tx_dlf_pagegrid->main('.$content.', [data])] Incomplete plugin configuration: entry template missing.', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);
			
			}
			
			// Quit without doing anything if required variables are not set.
			return $content;
		
		}
		
		$actEntryTemplate = $this->cObj->getSubpart($this->template, '###ACT_ENTRY###');
		
		// Set some variable defaults.
		if (!empty($this->piVars['pointer']) && (($this->piVars['pointer'] * $this->conf['limit']) + 1) <= $this->doc->numPages) {

			$this->piVars['pointer'] = max(intval($this->piVars['pointer']), 0);

		} else {

			$this->piVars['pointer'] = 0;

		}

		// Iterate through visible page set and display thumbnails.
		for ($i = $this->piVars['pointer'] * $this->conf['limit'], $j = ($this->piVars['pointer'] + 1) * $this->conf['limit']; $i < $j; $i++) {
			
			$content .= $this->getEntry($i, $i == intval($this->piVars['page']) && !empty($actEntryTemplate) ?  $actEntryTemplate : $entryTemplate);

		}

		// Render page browser.
		$markerArray['###PAGEBROWSER###'] = $this->getPageBrowser();

		// Render entries into entry template.
		$content = $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($this->template, '###ENTRY###', $content, TRUE), $markerArray);

		// Remove act-entry template from result.
		$content = $this->cObj->substituteSubpart($content, '###ACT_ENTRY###', '', TRUE);
		
		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pagegrid/class.tx_dlf_pagegrid.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/pagegrid/class.tx_dlf_pagegrid.php']);
}

?>