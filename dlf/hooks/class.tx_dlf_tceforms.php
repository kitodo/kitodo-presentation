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
 * Hooks and helper for the '\TYPO3\CMS\Backend\Form\FormEngine' library.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_tceforms {

	/**
	 * Helper to display document's thumbnail for table "tx_dlf_documents"
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
	 *
	 * @return	string		HTML <img> tag for thumbnail
	 */
	public function displayThumbnail(&$params, &$pObj) {

		$output = '<div style="padding:5px; background-color:#000000;">';

		// Simulate TCA field type "passthrough".
		$output .= '<input type="hidden" name="'.$params['itemFormElName'].'" value="'.$params['itemFormElValue'].'" />';

		if (!empty($params['itemFormElValue'])) {

			$output .= '<img alt="" src="'.$params['itemFormElValue'].'" />';

		}

		$output .= '</div>';

		return $output;

	}

	/**
	 * Helper to get flexform's items array for plugin "tx_dlf_collection"
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
	 *
	 * @return	void
	 */
	public function itemsProcFunc_collectionList(&$params, &$pObj) {

		if ($params['row']['pi_flexform']) {

			$pi_flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($params['row']['pi_flexform']);

			$pages = $pi_flexform['data']['sDEF']['lDEF']['pages']['vDEF'];

			// There is a strange behavior where the uid from the flexform is prepended by the table's name and appended by its title.
			// i.e. instead of "18" it reads "pages_18|Title"
			if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pages)) {

				$parts = explode('|', $pages);

				$pages = array_pop(explode('_', $parts[0]));

			}

			if ($pages > 0) {

				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'label,uid',
					'tx_dlf_collections',
					'pid='.intval($pages).' AND (sys_language_uid IN (-1,0) OR l18n_parent=0)'.tx_dlf_helper::whereClause('tx_dlf_collections'),
					'',
					'label',
					''
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

					while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_row($result)) {

						$params['items'][] = $resArray;

					}

				}

			}

		}

	}

	/**
	 * Helper to get flexform's items array for plugin "tx_dlf_search"
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
	 *
	 * @return	void
	 */
	public function itemsProcFunc_extendedSearchList(&$params, &$pObj) {

		if ($params['row']['pi_flexform']) {

			$pi_flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($params['row']['pi_flexform']);

			$pages = $pi_flexform['data']['sDEF']['lDEF']['pages']['vDEF'];

			// There is a strange behavior where the uid from the flexform is prepended by the table's name and appended by its title.
			// i.e. instead of "18" it reads "pages_18|Title"
			if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pages)) {

				$_parts = explode('|', $pages);

				$pages = array_pop(explode('_', $_parts[0]));

			}

			if ($pages > 0) {

				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'label,index_name',
					'tx_dlf_metadata',
					'indexed=1 AND pid='.intval($pages).' AND (sys_language_uid IN (-1,0) OR l18n_parent=0)'.tx_dlf_helper::whereClause('tx_dlf_metadata'),
					'',
					'sorting',
					''
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

					while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_row($result)) {

						$params['items'][] = $resArray;

					}

				}

			}

		}

	}

	/**
	 * Helper to get flexform's items array for plugin "tx_dlf_search"
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
	 *
	 * @return	void
	 */
	public function itemsProcFunc_facetsList(&$params, &$pObj) {

		if ($params['row']['pi_flexform']) {

			$pi_flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($params['row']['pi_flexform']);

			$pages = $pi_flexform['data']['sDEF']['lDEF']['pages']['vDEF'];

			// There is a strange behavior where the uid from the flexform is prepended by the table's name and appended by its title.
			// i.e. instead of "18" it reads "pages_18|Title"
			if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pages)) {

				$_parts = explode('|', $pages);

				$pages = array_pop(explode('_', $_parts[0]));

			}

			if ($pages > 0) {

				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'label,index_name',
					'tx_dlf_metadata',
					'is_facet=1 AND pid='.intval($pages).' AND (sys_language_uid IN (-1,0) OR l18n_parent=0)'.tx_dlf_helper::whereClause('tx_dlf_metadata'),
					'',
					'sorting',
					''
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

					while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_row($result)) {

						$params['items'][] = $resArray;

					}

				}

			}

		}

	}

	/**
	 * Helper to get flexform's items array for plugin "tx_dlf_oai"
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
	 *
	 * @return	void
	 */
	public function itemsProcFunc_libraryList(&$params, &$pObj) {

		if ($params['row']['pi_flexform']) {

			$pi_flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($params['row']['pi_flexform']);

			$pages = $pi_flexform['data']['sDEF']['lDEF']['pages']['vDEF'];

			// There is a strange behavior where the uid from the flexform is prepended by the table's name and appended by its title.
			// i.e. instead of "18" it reads "pages_18|Title"
			if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pages)) {

				$parts = explode('|', $pages);

				$pages = array_pop(explode('_', $parts[0]));

			}

			if ($pages > 0) {

				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'label,uid',
					'tx_dlf_libraries',
					'pid='.intval($pages).' AND (sys_language_uid IN (-1,0) OR l18n_parent=0)'.tx_dlf_helper::whereClause('tx_dlf_libraries'),
					'',
					'label',
					''
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

					while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_row($result)) {

						$params['items'][] = $resArray;

					}

				}

			}

		}

	}

	/**
	 * Helper to get flexform's items array for plugin "tx_dlf_search"
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
	 *
	 * @return	void
	 */
	public function itemsProcFunc_solrList(&$params, &$pObj) {

		if ($params['row']['pi_flexform']) {

			$pi_flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($params['row']['pi_flexform']);

			$pages = $pi_flexform['data']['sDEF']['lDEF']['pages']['vDEF'];

			// There is a strange behavior where the uid from the flexform is prepended by the table's name and appended by its title.
			// i.e. instead of "18" it reads "pages_18|Title"
			if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pages)) {

				$parts = explode('|', $pages);

				$pages = array_pop(explode('_', $parts[0]));

			}

			if ($pages > 0) {

				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'label,uid',
					'tx_dlf_solrcores',
					'pid IN ('.intval($pages).',0)'.tx_dlf_helper::whereClause('tx_dlf_solrcores'),
					'',
					'label',
					''
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) > 0) {

					while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_row($result)) {

						$params['items'][] = $resArray;

					}

				}

			}

		}

	}

	/**
	 * Helper to get flexform's items array for plugin "tx_dlf_toolbox"
	 *
	 * @access	public
	 *
	 * @param	array		&$params: An array with parameters
	 * @param	\TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
	 *
	 * @return	void
	 */
	public function itemsProcFunc_toolList(&$params, &$pObj) {

		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/plugins/toolbox/tools'] as $class => $label) {

			$params['items'][] = array ($GLOBALS['LANG']->sL($label), $class);

		}

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_tceforms.php']);
}
