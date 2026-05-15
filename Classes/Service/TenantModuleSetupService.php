<?php

declare(strict_types=1);

namespace Kitodo\Dlf\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Service class for running the tenant module setup on an existing configuration folder.
 */
final class TenantModuleSetupService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly CacheManager $cacheManager,
        private readonly TenantDefaultsSetupService $tenantDefaultsSetupService,
    ) {}

    /**
     * Runs selected tenant setup steps for an existing configuration folder.
     *
     * @param array{formats:bool,structures:bool,metadata:bool,solrCore:bool} $steps
     * @return array{configurationPageId:int,steps:array{formats:bool,structures:bool,metadata:bool,solrCore:bool},results:array{formats:int,structures:int,metadata:int,solrCore:?int}}
     */
    public function runSetup(int $configurationPageId, array $steps): array
    {
        $configurationPage = $this->findByUid('pages', $configurationPageId);
        if ($configurationPage === null) {
            throw new \RuntimeException(sprintf('The configuration page %d could not be found.', $configurationPageId));
        }
        if ((int)($configurationPage['doktype'] ?? 0) !== 254) {
            throw new \RuntimeException(sprintf('Page %d is not a configuration folder.', $configurationPageId));
        }

        $rootPageId = (int)($configurationPage['pid'] ?? 0);
        if ($rootPageId <= 0) {
            throw new \RuntimeException(sprintf('Configuration page %d has no valid root page parent.', $configurationPageId));
        }

        $results = $this->tenantDefaultsSetupService->run($configurationPageId, $steps);
        $this->cacheManager->flushCaches();

        return [
            'configurationPageId' => $configurationPageId,
            'steps' => $steps,
            'results' => $results,
        ];
    }

    private function findByUid(string $table, int $uid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $row = $queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? $row : null;
    }
}
