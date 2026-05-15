<?php

declare(strict_types=1);

namespace Kitodo\Dlf\Service;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class for creating a new bootstrap root page tree.
 */
final class BootstrapRootSetupService
{
    private const SITE_IDENTIFIER_PREFIX = 'kitodo-presentation';
    private const ROOT_PAGE_TITLE_PREFIX = 'Kitodo Presentation';
    private const VIEWER_PAGE_TITLE = 'Viewer';
    private const CONFIGURATION_PAGE_TITLE = 'Kitodo Configuration';
    private const TEMPLATE_TITLE = 'Kitodo Presentation Bootstrap';
    private const BASIC_STATIC_FILE = 'EXT:dlf/Configuration/TypoScript/';
    private const BASIC_VIEWER_STATIC_FILE = 'EXT:dlf/Configuration/TypoScript/BasicViewer/';
    private const SITE_CONFIGURATION_TEMPLATE = 'EXT:dlf/Resources/Private/Data/BootstrapSiteConfig.yaml';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly SiteConfiguration $siteConfiguration,
        private readonly CacheManager $cacheManager,
    ) {}

    /**
     * Runs the setup for a new bootstrap root page tree.
     *
     * @param array{identifier?:mixed,base?:mixed,rootTitle?:mixed,rootSlug?:mixed,viewerSlug?:mixed} $options
     * @return array{siteIdentifier:string,siteBase:string,rootPageId:int,viewerPageId:int,configurationPageId:int,templateId:int}
     */
    public function runSetup(array $options = []): array
    {
        $context = $this->buildSetupContext($options);

        $rootPageId = $this->createRootPage($context);
        $this->writeSiteConfiguration($context, $rootPageId);

        $configurationPageId = $this->createPage($rootPageId, self::CONFIGURATION_PAGE_TITLE, ['doktype' => 254,'sorting' => 256,]);
        $viewerPageId = $this->createPage($rootPageId, self::VIEWER_PAGE_TITLE, ['slug' => $context['viewerPageSlug'], 'sorting' => 512,]);

        $templateId = $this->ensureTemplate($rootPageId);
        $this->updateTemplate(
            $templateId,
            $rootPageId,
            $viewerPageId,
            $configurationPageId,
        );
        $this->cacheManager->flushCaches();

        return [
            'siteIdentifier' => $context['siteIdentifier'],
            'siteBase' => $context['siteBase'],
            'rootPageId' => $rootPageId,
            'viewerPageId' => $viewerPageId,
            'configurationPageId' => $configurationPageId,
            'templateId' => $templateId,
        ];
    }

    /**
     * Determines the next unique bootstrap site identifier, titles and slugs.
     *
     * @param array{identifier?:mixed,base?:mixed,rootTitle?:mixed,rootSlug?:mixed,viewerSlug?:mixed} $options
     * @return array{siteIdentifier:string,siteBase:string,rootPageTitle:string,rootPageSlug:string,viewerPageSlug:string}
     */
    private function buildSetupContext(array $options): array
    {
        $nextIndex = $this->determineNextGroupIndex();
        $customIdentifier = $this->normalizeIdentifierOption($options['identifier'] ?? null);
        $customBase = $this->normalizeBaseOption($options['base'] ?? null);
        $customRootTitle = $this->normalizeTextOption($options['rootTitle'] ?? null);
        $customRootSlug = $this->normalizeSlugOption($options['rootSlug'] ?? null, 'root-slug');
        $customViewerSlug = $this->normalizeSlugOption($options['viewerSlug'] ?? null, 'viewer-slug');

        $siteIdentifier = $customIdentifier ?? ($nextIndex === 1 ? self::SITE_IDENTIFIER_PREFIX : self::SITE_IDENTIFIER_PREFIX . '-' . $nextIndex);
        $defaultBase = ($customIdentifier === null && $nextIndex === 1 && !$this->siteBaseExists('/')) ? '/' : '/' . $siteIdentifier . '/';
        $siteBase = $customBase ?? $defaultBase;
        $rootPageTitle = $customRootTitle ?? ($nextIndex === 1 ? self::ROOT_PAGE_TITLE_PREFIX : self::ROOT_PAGE_TITLE_PREFIX . ' ' . $nextIndex);
        $rootPageSlug = $customRootSlug ?? '/';
        $viewerPageSlug = $customViewerSlug ?? '/viewer';

        $this->assertSiteIdentifierAvailable($siteIdentifier);
        $this->assertRootTitleAvailable($rootPageTitle);
        if ($this->siteBaseExists($siteBase)) {
            throw new \RuntimeException(sprintf('The site base "%s" is already in use.', $siteBase));
        }
        $this->assertBaseCompatibleWithSlugs($siteBase, $rootPageSlug, $viewerPageSlug);

        return [
            'siteIdentifier' => $siteIdentifier,
            'siteBase' => $siteBase,
            'rootPageTitle' => $rootPageTitle,
            'rootPageSlug' => $rootPageSlug,
            'viewerPageSlug' => $viewerPageSlug,
        ];
    }

    /**
     * Finds the next free numeric index for bootstrap groups by inspecting existing site folders and root pages.
     * 
     * @return int The next available index, starting from 1. If no existing groups are found, returns 1.
     */
    private function determineNextGroupIndex(): int
    {
        $maxIndex = 0;
        $sitesPath = Environment::getConfigPath() . '/sites';
        if (is_dir($sitesPath)) {
            // Look for existing site folders with the defined prefix and extract the highest index:
            foreach ((array)scandir($sitesPath) as $entry) {
                // Skip non-string entries and special directories:
                if (!is_string($entry) || $entry === '.' || $entry === '..') {
                    continue;
                }

                // Check for exact prefix match (index 1) or prefix followed by a numeric suffix:
                if ($entry === self::SITE_IDENTIFIER_PREFIX) {
                    $maxIndex = max($maxIndex, 1);
                    continue;
                }

                // Check for entries matching the pattern "prefix-{number}" and extract the number:
                if (preg_match('/^' . preg_quote(self::SITE_IDENTIFIER_PREFIX, '/') . '-(\d+)$/', $entry, $matches) === 1) {
                    $maxIndex = max($maxIndex, (int)$matches[1]);
                }
            }
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $rows = $queryBuilder
            ->select('title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchFirstColumn();

        foreach ($rows as $title) {
            if ($title === self::ROOT_PAGE_TITLE_PREFIX) {
                $maxIndex = max($maxIndex, 1);
                continue;
            }

            if (is_string($title) && preg_match('/^' . preg_quote(self::ROOT_PAGE_TITLE_PREFIX, '/') . ' (\d+)$/', $title, $matches) === 1) {
                $maxIndex = max($maxIndex, (int)$matches[1]);
            }
        }

        return $maxIndex + 1;
    }

    /**
     * Creates a fresh bootstrap root page for the next group.
     *
     * @param array{rootPageTitle:string,rootPageSlug:string} $context
     * @return int The uid of the created root page
     */
    private function createRootPage(array $context): int
    {
        return $this->insertRow('pages', [
            'pid' => 0,
            'tstamp' => time(),
            'crdate' => time(),
            'deleted' => 0,
            'hidden' => 0,
            'doktype' => 1,
            'title' => $context['rootPageTitle'],
            'slug' => $context['rootPageSlug'],
            'is_siteroot' => 1,
            'sorting' => $this->nextSortingForPid(0),
        ]);
    }

    /**
     * Creates a child page below a freshly created bootstrap root page.
     *
     * @param int $pid
     * @param string $title
     * @param array<string, mixed> $data
     * @return int
     */
    private function createPage(int $pid, string $title, array $data): int
    {
        return $this->insertRow('pages', array_merge([
            'pid' => $pid,
            'tstamp' => time(),
            'crdate' => time(),
            'deleted' => 0,
            'hidden' => 0,
            'doktype' => 1,
            'title' => $title,
        ], $data));
    }

    /**
     * Writes a unique bootstrap site configuration for the freshly created root page.
     *
     * @param array{siteIdentifier:string,siteBase:string} $context
     * @param int $rootPageId
     * @return void
     */
    private function writeSiteConfiguration(array $context, int $rootPageId): void
    {
        $templatePath = GeneralUtility::getFileAbsFileName(self::SITE_CONFIGURATION_TEMPLATE);
        $configuration = Yaml::parseFile($templatePath);
        if (!is_array($configuration)) {
            throw new \RuntimeException(sprintf('Bootstrap site template "%s" is invalid.', self::SITE_CONFIGURATION_TEMPLATE));
        }

        $configuration['base'] = $context['siteBase'];
        $configuration['rootPageId'] = $rootPageId;

        $this->siteConfiguration->write($context['siteIdentifier'], $configuration);
    }

    /**
     * Asserts that the given site identifier is available (no existing site folder with the same name).
     * 
     * @param string $siteIdentifier
     * @return void
     */
    private function assertSiteIdentifierAvailable(string $siteIdentifier): void
    {
        $siteDirectory = Environment::getConfigPath() . '/sites/' . $siteIdentifier;
        if (is_dir($siteDirectory)) {
            throw new \RuntimeException(sprintf('The site identifier "%s" already exists.', $siteIdentifier));
        }
    }

    /**
     * Checks if any existing site configuration uses the given site base.
     * 
     * @param string $siteBase
     * @return bool
     */
    private function siteBaseExists(string $siteBase): bool
    {
        foreach ($this->siteConfiguration->getAllExistingSites() as $site) {
            if ($site instanceof Site && (string)$site->getBase() === $siteBase) {
                return true;
            }
        }

        return false;
    }

    /**
     * Asserts that the given root page title is available (no existing root page with the same title).
     * 
     * @param string $rootPageTitle
     * @return void
     */
    private function assertRootTitleAvailable(string $rootPageTitle): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $count = $queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($rootPageTitle)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        if ((int)$count > 0) {
            throw new \RuntimeException(sprintf('The root page title "%s" is already in use.', $rootPageTitle));
        }
    }

    /**
     * Asserts that custom root and viewer slugs are only used together with the root site base "/" to avoid conflicts with existing pages.
     * 
     * @param string $siteBase
     * @param string $rootSlug
     * @param string $viewerSlug
      * @return void
     */
    private function assertBaseCompatibleWithSlugs(string $siteBase, string $rootSlug, string $viewerSlug): void
    {
        if ($siteBase !== '/' && $rootSlug !== '/') {
            throw new \RuntimeException('Custom root slugs are only supported together with the root site base "/".');
        }
        if ($viewerSlug === '/') {
            throw new \RuntimeException('The viewer slug must not be "/".');
        }
    }

    /**
     * Normalizes and validates the custom site identifier option. It must only contain lowercase letters, numbers and hyphens.
     * 
     * @param mixed $value
     * @return string|null The normalized identifier or null if no valid value is provided.
     */
    private function normalizeIdentifierOption(mixed $value): ?string
    {
        $value = $this->normalizeTextOption($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $value) !== 1) {
            throw new \RuntimeException('The --identifier value may only contain lowercase letters, numbers and hyphens.');
        }
        return $value;
    }

    /**
     * Normalizes and validates the custom site base option. It must start with a slash and end with a slash (unless it's just "/").
     * 
     * @param mixed $value
     * @return string|null The normalized base or null if no valid value is provided.
     */
    private function normalizeBaseOption(mixed $value): ?string
    {
        $value = $this->normalizeTextOption($value);
        if ($value === null) {
            return null;
        }
        if (!str_starts_with($value, '/')) {
            throw new \RuntimeException('The --base value must start with "/".');
        }
        if (!str_ends_with($value, '/')) {
            $value .= '/';
        }
        return $value;
    }

    /**
     * Normalizes and validates the custom slug options. They must start with a slash and must not end with a slash (unless it's just "/").
     * 
     * @param mixed $value
     * @param string $optionName The name of the option for error messages.
     * @return string|null The normalized slug or null if no valid value is provided.
     */
    private function normalizeSlugOption(mixed $value, string $optionName): ?string
    {
        $value = $this->normalizeTextOption($value);
        if ($value === null) {
            return null;
        }
        if (!str_starts_with($value, '/')) {
            throw new \RuntimeException(sprintf('The --%s value must start with "/".', $optionName));
        }
        return rtrim($value, '/') ?: '/';
    }

    private function normalizeTextOption(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    /**
     * Creates or updates the root template and ensures the required static TypoScript includes are present.
     * 
     * @param int $rootPageId The UID of the root page.
     * @return int The uid of the created or updated template record.
     */
    private function ensureTemplate(int $rootPageId): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $row = $queryBuilder
            ->select('uid', 'include_static_file')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($rootPageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('sorting')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row !== false) {
            $this->updateRow('sys_template', (int)$row['uid'], [
                'include_static_file' => $this->mergeStaticFiles((string)$row['include_static_file']),
                'title' => self::TEMPLATE_TITLE,
                'root' => 1,
                'clear' => 3,
                'tstamp' => time(),
            ]);

            return (int)$row['uid'];
        }

        return $this->insertRow('sys_template', [
            'pid' => $rootPageId,
            'tstamp' => time(),
            'crdate' => time(),
            'deleted' => 0,
            'hidden' => 0,
            'sorting' => 256,
            'title' => self::TEMPLATE_TITLE,
            'root' => 1,
            'clear' => 3,
            'include_static_file' => $this->mergeStaticFiles(''),
            'constants' => '',
            'config' => '',
        ]);
    }

    /**
     * Rewrites the bootstrap template constants to the imported page IDs.
     * 
     * @param int $templateId The uid of the template record to update.
     * @param int $rootPageId The UID of the root page.
     * @param int $viewerPageId The UID of the viewer page.
     * @param int $configurationPageId The UID of the configuration page.
     * @return void
     */
    private function updateTemplate(int $templateId, int $rootPageId, int $viewerPageId, int $configurationPageId,): void 
    {
        $constants = [
            'plugin.tx_dlf.persistence.storagePid = ' . $configurationPageId,
            'plugin.tx_dlf.basicViewer.rootPid = ' . $rootPageId,
            'plugin.tx_dlf.basicViewer.viewerPid = ' . $viewerPageId,
        ];

        $this->updateRow('sys_template', $templateId, [
            'tstamp' => time(),
            'constants' => implode(PHP_EOL, $constants),
        ]);
    }

    /**
     * Determines the next sorting value for a new page under the given parent page by finding the current maximum sorting value and adding 256.
     * 
     * @param int $pid
     * @return int The next sorting value to use for a new page under the given parent page.
     */
    private function nextSortingForPid(int $pid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $sorting = $queryBuilder
            ->selectLiteral('MAX(sorting)')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        return ((int)$sorting) + 256;
    }

    /**
     * Inserts a new row into the given table with the provided data and returns the uid of the newly created record.
     * 
     * @param string $table The name of the table to insert into.
     * @param array<string, mixed> $data The data to insert.
     * @return int The uid of the newly created record.
     */
    private function insertRow(string $table, array $data): int
    {
        $connection = $this->connectionPool->getConnectionForTable($table);
        $connection->insert($table, $data);
        return (int)$connection->lastInsertId($table);
    }

    /**
     * Updates a row in the given table with the provided data.
     * 
     * @param string $table The name of the table to update.
     * @param int $uid The uid of the row to update.
     * @param array<string, mixed> $data The data to update.
     * @return void
     */
    private function updateRow(string $table, int $uid, array $data): void
    {
        $connection = $this->connectionPool->getConnectionForTable($table);
        $connection->update($table, $data, ['uid' => $uid]);
    }

    /**
     * Merges the required static TypoScript files with any existing includes, ensuring there are no duplicates and that the required files are included.
     * 
     * @param string $includeStaticFile The existing include_static_file value from the template record.
     * @return string The merged include_static_file value with required files included.
     */
    private function mergeStaticFiles(string $includeStaticFile): string
    {
        $files = array_filter(array_map('trim', explode(',', $includeStaticFile)));
        $files[] = self::BASIC_STATIC_FILE;
        $files[] = self::BASIC_VIEWER_STATIC_FILE;
        return implode(',', array_values(array_unique($files)));
    }
}
