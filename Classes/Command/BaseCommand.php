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

use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;

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
     * Return starting point for indexing command.
     *
     * @param string|string[]|bool|null $inputPid possible pid
     *
     * @return int starting point for indexing command
     */
    protected function getStartingPoint($inputPid): int
    {
        if (MathUtility::canBeInterpretedAsInteger($inputPid)) {
            return MathUtility::forceIntegerInRange((int) $inputPid, 0);
        }
        return 0;
    }

    /**
     * Return matching uid of Solr core depending on the input value.
     *
     * @param array $solrCores array of the valid Solr cores
     * @param string|bool|null $inputSolrId possible uid or name of Solr core
     *
     * @return int matching uid of Solr core
     */
    protected function getSolrCoreUid(array $solrCores, $inputSolr): int
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
