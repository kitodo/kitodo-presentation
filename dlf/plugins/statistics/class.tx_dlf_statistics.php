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
 * Plugin 'DLF: Statistics' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_statistics extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/statistics/class.tx_dlf_statistics.php';

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

		// Turn cache on.
		$this->setCache(TRUE);

		// Quit without doing anything if required configuration variables are not set.
		if (empty($this->conf['pages'])) {

			if (TYPO3_DLOG) {

				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_statistics->main('.$content.', [data])] Incomplete plugin configuration', $this->extKey, SYSLOG_SEVERITY_WARNING, $conf);

			}

			return $content;

		}

		// Get description.
		$content .= $this->pi_RTEcssText($this->conf['description']);

		// Check for selected collections.
		if ($this->conf['collections']) {

			// Include only selected collections.
			$resultTitles = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dlf_documents.uid AS uid',
				'tx_dlf_documents',
				'tx_dlf_relations',
				'tx_dlf_collections',
				'AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_documents.partof=0 AND tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).') AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
				'tx_dlf_documents.uid',
				'',
				''
			);

			$resultVolumes = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dlf_documents.uid AS uid',
				'tx_dlf_documents',
				'tx_dlf_relations',
				'tx_dlf_collections',
				'AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND NOT tx_dlf_documents.uid IN (SELECT DISTINCT tx_dlf_documents.partof FROM tx_dlf_documents WHERE NOT tx_dlf_documents.partof=0'.tx_dlf_helper::whereClause('tx_dlf_documents').') AND tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).') AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
				'tx_dlf_documents.uid',
				'',
				''
			);

		} else {

			// Include all collections.
			$resultTitles = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_documents.uid AS uid',
				'tx_dlf_documents',
				'tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_documents.partof=0'.tx_dlf_helper::whereClause('tx_dlf_documents'),
				'',
				'',
				''
			);

			$resultVolumes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_documents.uid AS uid',
				'tx_dlf_documents',
				'tx_dlf_documents.pid='.intval($this->conf['pages']).' AND NOT tx_dlf_documents.uid IN (SELECT DISTINCT tx_dlf_documents.partof FROM tx_dlf_documents WHERE NOT tx_dlf_documents.partof=0'.tx_dlf_helper::whereClause('tx_dlf_documents').')'.tx_dlf_helper::whereClause('tx_dlf_documents'),
				'',
				'',
				''
			);

		}

		$countTitles = $GLOBALS['TYPO3_DB']->sql_num_rows($resultTitles);

		$countVolumes = $GLOBALS['TYPO3_DB']->sql_num_rows($resultVolumes);

		// Set replacements.
		$replace = array (
			'key' => array (
				'###TITLES###',
				'###VOLUMES###'
			),
			'value' => array (
				$countTitles.($countTitles > 1 ? $this->pi_getLL('titles', '', TRUE) : $this->pi_getLL('title', '', TRUE)),
				$countVolumes.($countVolumes > 1 ? $this->pi_getLL('volumes', '', TRUE) : $this->pi_getLL('volume', '', TRUE))
			)
		);

		// Apply replacements.
		$content = str_replace($replace['key'], $replace['value'], $content);

		return $this->pi_wrapInBaseClass($content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/statistics/class.tx_dlf_statistics.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/statistics/class.tx_dlf_statistics.php']);
}
