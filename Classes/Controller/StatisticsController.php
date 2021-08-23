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

namespace Kitodo\Dlf\Controller;

use \TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Conrtoller for the plugin 'Statistics' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class StatisticsController extends ActionController
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    protected $logger;

    /**
     * SearchController constructor.
     * @param $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
    }

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     */
    public function mainAction()
    {
        // Quit without doing anything if required configuration variables are not set.
        if (empty($this->settings['pages'])) {
            $this->logger->warning('Incomplete plugin configuration');
        }

        // Check for selected collections.
        if ($this->settings['collections']) {
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
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->settings['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($this->settings['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_documents.partof', 0),
                    $queryBuilder->expr()->in('tx_dlf_collections_join.uid',
                        $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',',
                            $this->settings['collections']), Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident',
                        $queryBuilder->createNamedParameter('docs_colls'))
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
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->settings['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_collections_join.pid', intval($this->settings['pages'])),
                    $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery),
                    $queryBuilder->expr()->in('tx_dlf_collections_join.uid',
                        $queryBuilder->createNamedParameter(GeneralUtility::intExplode(',',
                            $this->settings['collections']), Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->eq('tx_dlf_relations_joins.ident',
                        $queryBuilder->createNamedParameter('docs_colls'))
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
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->settings['pages'])),
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
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', intval($this->settings['pages'])),
                    $queryBuilder->expr()->notIn('tx_dlf_documents.uid', $subQuery)
                )
                ->execute()
                ->fetchColumn(0);
        }

        // Set replacements.
        $args['###TITLES###'] = $countTitles . htmlspecialchars(
            LocalizationUtility::translate(
                'LLL:EXT:dlf/Resources/Private/Language/Statistics.xml:'.($countTitles > 1 ? 'titles': 'title')
            )
        );

        $args['###VOLUMES###'] = $countVolumes . htmlspecialchars(
            LocalizationUtility::translate(
                'LLL:EXT:dlf/Resources/Private/Language/Statistics.xml:'.($countVolumes > 1 ? 'volumes': 'volume')
            )
        );

        // Apply replacements.
        $content = $this->settings['description'];
        foreach ($args as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        $this->view->assign('content', $content);
    }
}
