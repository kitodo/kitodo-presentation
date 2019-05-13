<?php
namespace Kitodo\Dlf\Hooks;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Helper;

/**
 * Hooks and helper for \TYPO3\CMS\Backend\Form\FormEngine
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class FormEngine {
    /**
     * Helper to display document's thumbnail for table "tx_dlf_documents"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
     *
     * @return string HTML <img> tag for thumbnail
     */
    public function displayThumbnail(&$params, &$pObj) {
        // Simulate TCA field type "passthrough".
        $output = '<input type="hidden" name="'.$params['itemFormElName'].'" value="'.$params['itemFormElValue'].'" />';
        if (!empty($params['itemFormElValue'])) {
            $output .= '<img alt="" src="'.$params['itemFormElValue'].'" />';
        }
        return $output;
    }

    /**
     * Helper to get flexform's items array for plugin "tx_dlf_collection"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
     *
     * @return void
     */
    public function itemsProcFunc_collectionList(&$params, &$pObj) {
        $this->itemsProcFunc_generateList(
            $params,
            'label,uid',
            'tx_dlf_collections',
            'label'
        );
    }

    /**
     * Helper to get flexform's items array for plugin "Search"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
     *
     * @return void
     */
    public function itemsProcFunc_extendedSearchList(&$params, &$pObj) {
        $this->itemsProcFunc_generateList(
            $params,
            'label,index_name',
            'tx_dlf_metadata',
            'sorting',
            'AND index_indexed=1'
        );
    }

    /**
     * Helper to get flexform's items array for plugin "Search"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
     *
     * @return void
     */
    public function itemsProcFunc_facetsList(&$params, &$pObj) {
        $this->itemsProcFunc_generateList(
            $params,
            'label,index_name',
            'tx_dlf_metadata',
            'sorting',
            'AND is_facet=1'
        );
    }

    /**
     * Get list items from database
     *
     * @access protected
     *
     * @param array &$params: An array with parameters
     * @param string $fields: Comma-separated list of fields to fetch
     * @param string $table: Table name to fetch the items from
     * @param string $sorting: Field to sort items by (optionally appended by 'ASC' or 'DESC')
     * @param string $where: Additional WHERE clause
     * @param boolean $localize: Add check for localized records?
     *
     * @return void
     */
    protected function itemsProcFunc_generateList(&$params, $fields, $table, $sorting, $where = '', $localize = TRUE) {
        $pages = $params['row']['pages'];
        if (!empty($pages)) {
            foreach ($pages as $page) {
                if ($page['uid'] > 0) {
                    $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        $fields,
                        $table,
                        '(pid='.intval($page['uid']).' '.$where.')'
                            .($localize ? ' AND (sys_language_uid IN (-1,0) OR l18n_parent=0)' : '')
                            .Helper::whereClause($table),
                        '',
                        $sorting,
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
    }

    /**
     * Helper to get flexform's items array for plugin "OaiPmh"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
     *
     * @return void
     */
    public function itemsProcFunc_libraryList(&$params, &$pObj) {
        $this->itemsProcFunc_generateList(
            $params,
            'label,uid',
            'tx_dlf_libraries',
            'label'
        );
    }

    /**
     * Helper to get flexform's items array for plugin "Search"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
     *
     * @return void
     */
    public function itemsProcFunc_solrList(&$params, &$pObj) {
        $this->itemsProcFunc_generateList(
            $params,
            'label,uid',
            'tx_dlf_solrcores',
            'label',
            'OR pid=0',
            FALSE
        );
    }

    /**
     * Helper to get flexform's items array for plugin "Toolbox"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Backend\Form\FormEngine &$pObj: The parent object
     *
     * @return void
     */
    public function itemsProcFunc_toolList(&$params, &$pObj) {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'] as $class => $label) {
            $params['items'][] = [$GLOBALS['LANG']->sL($label), $class];
        }
    }
}
