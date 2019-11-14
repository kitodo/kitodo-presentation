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

namespace Kitodo\Dlf\Hooks;

use Kitodo\Dlf\Common\Helper;

/**
 * Helper for Flexform's custom "itemsProcFunc"
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ItemsProcFunc
{
    /**
     * Helper to get flexform's items array for plugin "Collection"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     *
     * @return void
     */
    public function collectionList(&$params)
    {
        $this->generateList(
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
     *
     * @return void
     */
    public function extendedSearchList(&$params)
    {
        $this->generateList(
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
     *
     * @return void
     */
    public function facetsList(&$params)
    {
        $this->generateList(
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
     * @param bool $localize: Add check for localized records?
     *
     * @return void
     */
    protected function generateList(&$params, $fields, $table, $sorting, $where = '', $localize = true)
    {
        $pages = $params['row']['pages'];
        if (!empty($pages)) {
            if (!is_array($pages)) {
                $pages = [['uid' => $pages]];
            }
            foreach ($pages as $page) {
                if ($page['uid'] > 0) {
                    $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        $fields,
                        $table,
                        '(' . $table . '.pid=' . intval($page['uid']) . ' ' . $where . ')'
                            . ($localize ? ' AND (' . $table . '.sys_language_uid IN (-1,0) OR ' . $table . '.l18n_parent=0)' : '')
                            . Helper::whereClause($table),
                        '',
                        $sorting
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
     *
     * @return void
     */
    public function libraryList(&$params)
    {
        $this->generateList(
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
     *
     * @return void
     */
    public function solrList(&$params)
    {
        $this->generateList(
            $params,
            'label,uid',
            'tx_dlf_solrcores',
            'label',
            'OR pid=0',
            false
        );
    }

    /**
     * Helper to get flexform's items array for plugin "Toolbox"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     *
     * @return void
     */
    public function toolList(&$params)
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['dlf/Classes/Plugin/Toolbox.php']['tools'] as $class => $label) {
            $params['items'][] = [$GLOBALS['LANG']->sL($label), $class];
        }
    }
}
