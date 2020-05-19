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

namespace Kitodo\Dlf\Plugin;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Statistics' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Statistics extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Statistics.php';

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Turn cache on.
        $this->setCache(true);
        // Quit without doing anything if required configuration variables are not set.
        if (empty($this->conf['pages'])) {
            Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
            return $content;
        }
        // Get description.
        $content .= $this->pi_RTEcssText($this->conf['description']);
        // Check for selected collections.
        if ($this->conf['collections']) {
            // Include only selected collections.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

            $countTitles = $queryBuilder
                ->count('tx_dlf_documents.uid')
                ->from('tx_dlf_documents')
                ->innerJoin(
                    'tx_dlf_documents',
                    'tx_dlf_relations',
                    'tx_dlf_relations_joins',
                    $queryBuilder->expr()->eq(
                        'tx_dlf_relations_joins.uid_local',
                        'tx_dlf_documents.uid'
                    )
                )
                ->innerJoin(
                    'tx_dlf_relations_joins',
                    'tx_dlf_collections',
                    'tx_dlf_collections_join',
                    $queryBuilder->expr()->eq(
                        'tx_dlf_relations_joins.uid_foreign',
                        'tx_dlf_collections_join.uid'
                    )
                )
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->conf['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($this->conf['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_documents.partof', 0),
                    $queryBuilder->expr()->in('tx_dlf_collections_join.uid', $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $this->conf['collections']), Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident', $queryBuilder->createNamedParameter('docs_colls'))
                )
                ->execute()
                ->fetchColumn(0);

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('tx_dlf_documents');
                $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('tx_dlf_documents');

                $subQuery = $subQueryBuilder
                    ->select('tx_dlf_documents.partof')
                    ->from('tx_dlf_documents')
                    ->where(
                        $subQueryBuilder->expr()->neq('tx_dlf_documents.partof', 0)
                    )
                    ->groupBy('tx_dlf_documents.partof')
                    ->getSQL();

                $countVolumes = $queryBuilder
                    ->count('tx_dlf_documents.uid')
                    ->from('tx_dlf_documents')
                    ->innerJoin(
                        'tx_dlf_documents',
                        'tx_dlf_relations',
                        'tx_dlf_relations_joins',
                        $queryBuilder->expr()->eq(
                            'tx_dlf_relations_joins.uid_local',
                            'tx_dlf_documents.uid'
                        )
                    )
                    ->innerJoin(
                        'tx_dlf_relations_joins',
                        'tx_dlf_collections',
                        'tx_dlf_collections_join',
                        $queryBuilder->expr()->eq(
                            'tx_dlf_relations_joins.uid_foreign',
                            'tx_dlf_collections_join.uid'
                        )
                    )
                    ->where(
                        $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->conf['pages'])),
                        $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($this->conf['pages'])),
                        $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery),
                        $queryBuilder->expr()->in('tx_dlf_collections_join.uid', $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',', $this->conf['collections']), Connection::PARAM_INT_ARRAY)),
                        $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident', $queryBuilder->createNamedParameter('docs_colls'))
                    )
                    ->execute()
                    ->fetchColumn(0);
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Include all collections.
            $countTitles = $queryBuilder
                ->count('tx_dlf_documents.uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->conf['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_documents.partof', 0),
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->execute()
                ->fetchColumn(0);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');
            $subQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $subQuery = $subQueryBuilder
                ->select('tx_dlf_documents.partof')
                ->from('tx_dlf_documents')
                ->where(
                    $subQueryBuilder->expr()->neq('tx_dlf_documents.partof', 0)
                )
                ->groupBy('tx_dlf_documents.partof')
                ->getSQL();

            $countVolumes = $queryBuilder
                ->count('tx_dlf_documents.uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->conf['pages'])),
                    $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery)
                )
                ->execute()
                ->fetchColumn(0);
        }

        // Set replacements.
        $replace = [
            'key' => [
                '###TITLES###',
                '###VOLUMES###'
            ],
            'value' => [
                $countTitles . ($countTitles > 1 ? htmlspecialchars($this->pi_getLL('titles', '')) : htmlspecialchars($this->pi_getLL('title', ''))),
                $countVolumes . ($countVolumes > 1 ? htmlspecialchars($this->pi_getLL('volumes', '')) : htmlspecialchars($this->pi_getLL('volume', '')))
            ]
        ];
        // Apply replacements.
        $content = str_replace($replace['key'], $replace['value'], $content);
        return $this->pi_wrapInBaseClass($content);
    }
}
