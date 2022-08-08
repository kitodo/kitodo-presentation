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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Helper for Flexform's custom "itemsProcFunc"
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ItemsProcFunc implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var int
     */
    protected $storagePid;

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
            $params['items'][] = [Helper::getLanguageService()->sL($label), $class];
        }
    }

    /**
     * Extract typoscript configuration from site root of the plugin
     *
     * @access public
     *
     * @param $params
     *
     * @return void
     */
    public function getTyposcriptConfigFromPluginSiteRoot($params) {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $pid = $params['flexParentDatabaseRow']['pid'];
        $rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pid);
        $siterootRow = [];
        foreach ($rootline as $_row) {
            if ($_row['is_siteroot'] == '1') {
                $siterootRow = $_row;
                break;
            }
        }

        try {
            $ts = $objectManager->get(\TYPO3\CMS\Core\TypoScript\TemplateService::class, [$siterootRow['uid']]);
            $ts->rootLine = $rootline;
            $ts->runThroughTemplates($rootline, 0);
            $ts->generateConfig();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $typoscriptConfig = $ts->setup;
        $this->storagePid = $typoscriptConfig['plugin.']['tx_dlf.']['persistence.']['storagePid'];

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
            'label',
            'index_indexed=1'
        );
    }

    /**
     * Helper to get flexform's items array for plugin "Search"
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     */
    public function getFacetsList(array &$params): void
    {
        $this->generateList(
            $params,
            'label,index_name',
            'tx_dlf_metadata',
            'label',
            'is_facet=1'
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
     * @param string $andWhere: Additional AND WHERE clause
     *
     * @return void
     */
    protected function generateList(&$params, $fields, $table, $sorting, $andWhere = '')
    {
        $this->getTyposcriptConfigFromPluginSiteRoot($params);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        // Get $fields from $table on given pid.
        $result = $queryBuilder
            ->select(...explode(',', $fields))
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($table . '.pid', intval($this->storagePid)),
                $queryBuilder->expr()->in($table . '.sys_language_uid', [-1, 0]),
                $andWhere
            )
            ->orderBy($sorting)
            ->execute();

        while ($resArray = $result->fetch(\PDO::FETCH_NUM)) {
            if ($resArray) {
                $params['items'][] = $resArray;
            }
        }
    }
}
