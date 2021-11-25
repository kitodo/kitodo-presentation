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

namespace Kitodo\Dlf\Command;

use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Base class for CLI Command classes.
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class BaseCommand extends Command
{
    /**
     * @var DocumentRepository
     */
    protected $documentRepository;

    /**
     * Initialize the documentRepository based on the given storagePid.
     *
     * @param string|string[]|bool|null $inputPid possible pid
     *
     * @return int|bool validated storagePid
     */
    protected function initializeDocumentRepository($storagePid)
    {
        if (MathUtility::canBeInterpretedAsInteger($storagePid)) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

            $configurationManager = $objectManager->get(ConfigurationManager::class);
            $frameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

            $frameworkConfiguration['persistence']['storagePid'] = MathUtility::forceIntegerInRange((int) $storagePid, 0);
            $configurationManager->setConfiguration($frameworkConfiguration);

            $this->documentRepository = $objectManager->get(
                DocumentRepository::class
            );
        } else {
            return false;
        }
        return MathUtility::forceIntegerInRange((int) $storagePid, 0);
    }

    /**
     * Return matching uid of Solr core depending on the input value.
     *
     * @param array $solrCores array of the valid Solr cores
     * @param string|bool|null $inputSolrId possible uid or name of Solr core
     *
     * @return int matching uid of Solr core
     */
    protected function getSolrCoreUid(array $solrCores, $inputSolrId): int
    {
        if (MathUtility::canBeInterpretedAsInteger($inputSolrId)) {
            $solrCoreUid = MathUtility::forceIntegerInRange((int) $inputSolrId, 0);
        } else {
            $solrCoreUid = $solrCores[$inputSolrId];
        }
        return $solrCoreUid;
    }

    /**
     * Fetches all Solr cores on given page.
     *
     * @param int $pageId The UID of the Solr core or 0 to disable indexing
     *
     * @return array Array of valid Solr cores
     */
    protected function getSolrCores(int $pageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_solrcores');

        $solrCores = [];
        $result = $queryBuilder
            ->select('uid', 'index_name')
            ->from('tx_dlf_solrcores')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter((int) $pageId, Connection::PARAM_INT)
                )
            )
            ->execute();

        while ($record = $result->fetch()) {
            $solrCores[$record['index_name']] = $record['uid'];
        }

        return $solrCores;
    }
}
