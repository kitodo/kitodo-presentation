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
use Kitodo\Dlf\Common\TypoScriptHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper for FlexForm's custom "itemsProcFunc"
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ItemsProcFunc implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @access protected
     * @var int
     */
    protected $storagePid;

    /**
     * Helper to get flexform's items array for plugin "Toolbox"
     *
     * @access public
     *
     * @param array &$params An array with parameters
     *
     * @return void
     */
    public function toolList(array &$params): void
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $options = $configurationManager->getLocalConfigurationValueByPath('SC_OPTIONS');
        foreach ($options['dlf/Classes/Plugin/Toolbox.php']['tools'] as $class => $label) {
            $params['items'][] = [Helper::getLanguageService()->sL($label), $class];
        }
    }

    /**
     * Extract typoScript configuration from site root to load storagePid
     *
     * @access public
     *
     * @param array $params
     *
     * @return void
     */
    public function loadStoragePid(array $params): void
    {
        $pid = $params['flexParentDatabaseRow']['pid'];
        $config = TypoScriptHelper::getFrontendTyposcript($pid)->getSetupArray();
        $this->storagePid = $config['plugin.']['tx_dlf.']['persistence.']['storagePid'];
    }

    /**
     * Helper to get flexForm's items array for plugin "Search"
     *
     * @access public
     *
     * @param array &$params An array with parameters
     *
     * @return void
     */
    public function extendedSearchList(array &$params): void
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
     * Helper to get flexForm's items array for plugin "Search"
     *
     * @access public
     *
     * @param array &$params An array with parameters
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
     * @param array &$params An array with parameters
     * @param string $fields Comma-separated list of fields to fetch
     * @param string $table Table name to fetch the items from
     * @param string $sorting Field to sort items by (optionally appended by 'ASC' or 'DESC')
     * @param string $andWhere Additional AND WHERE clause
     *
     * @return void
     */
    protected function generateList(array &$params, string $fields, string $table, string $sorting, string $andWhere = ''): void
    {
        $this->loadStoragePid($params);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        // Get $fields from $table on given pid.
        $result = $queryBuilder
            ->select(...explode(',', $fields))
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($table . '.pid', $this->storagePid),
                $queryBuilder->expr()->in($table . '.sys_language_uid', [-1, 0]),
                $andWhere
            )
            ->orderBy($sorting)
            ->executeQuery();

        while ($resArray = $result->fetchNumeric()) {
            $params['items'][] = $resArray;
        }
    }
}
