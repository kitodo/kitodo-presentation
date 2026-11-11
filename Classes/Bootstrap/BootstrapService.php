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

namespace Kitodo\Dlf\Bootstrap;

use Kitodo\Dlf\Domain\Model\SolrCore;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Service\TenantService;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates a demo site (page tree, site configuration, tenant records and plugin content elements)
 * from a scenario file in Resources/Private/Bootstrap/Scenarios/.
 *
 * Running a scenario again replaces the demo site previously created by it.
 *
 * Requires a backend user with admin privileges, since all records are created via DataHandler.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class BootstrapService
{
    protected const SCENARIO_PATH = 'EXT:dlf/Resources/Private/Bootstrap/Scenarios/';

    protected const SITE_IDENTIFIER_PREFIX = 'kitodo-demo-';

    protected const DOCUMENTS_PATH = 'EXT:dlf/Resources/Private/Bootstrap/Documents/';

    /**
     * Placeholder in the METS files of the demo documents, which is replaced with the public URL of the document's directory.
     */
    protected const DOCUMENT_URL_PLACEHOLDER = '{documentUrl}';

    /**
     * SVG of a placeholder image with the arguments: drawing width and height, image width and height, page number, title
     */
    protected const PLACEHOLDER_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" width="%3$d" height="%4$d">'
        . '<rect width="100%%" height="100%%" fill="#fff"/>'
        . '<g fill="#333" font-family="sans-serif" text-anchor="middle">'
        . '<text x="50%%" y="%5$d" font-size="140">Page %6$s</text>'
        . '<text x="50%%" y="%7$d" font-size="44">%8$s</text>'
        . '</g></svg>';

    /**
     * @access protected
     * @var array<string, positive-int> UIDs of the created pages indexed by their scenario key
     */
    protected array $pageUids = [];

    /**
     * @access protected
     * @var array<string, mixed[]> Content elements indexed by the scenario key of their page
     */
    protected array $contentByPage = [];

    public function __construct(
        protected readonly TenantService $tenantService,
        protected readonly SolrCoreRepository $solrCoreRepository,
        protected readonly SiteFinder $siteFinder,
        protected readonly SiteWriter $siteWriter,
        protected readonly FlexFormTools $flexFormTools,
        protected readonly YamlFileLoader $yamlFileLoader
    ) {
    }

    /**
     * Get all available scenarios.
     *
     * @access public
     *
     * @return array<string, string> Scenario labels indexed by scenario name
     */
    public function getScenarios(): array
    {
        $scenarios = [];
        foreach (glob(GeneralUtility::getFileAbsFileName(self::SCENARIO_PATH) . '*.yaml') ?: [] as $file) {
            $name = basename($file, '.yaml');
            $scenarios[$name] = $this->loadScenario($name)['label'] ?? $name;
        }
        return $scenarios;
    }

    /**
     * Create the demo site of the given scenario, replacing a previously created one.
     *
     * @access public
     *
     * @param string $scenarioName Name of the scenario file without extension
     * @param string $baseUrl Scheme and host prepended to the base path of the scenario, e.g. "http://localhost:1380"
     *
     * @return array<string, positive-int> UIDs of the created pages indexed by their scenario key, including "root" and "storage"
     */
    public function bootstrap(string $scenarioName, string $baseUrl = ''): array
    {
        if (!array_key_exists($scenarioName, $this->getScenarios())) {
            throw new \InvalidArgumentException('Unknown demo scenario "' . $scenarioName . '"', 1762870001);
        }
        $scenario = $this->loadScenario($scenarioName);
        $siteIdentifier = self::SITE_IDENTIFIER_PREFIX . $scenarioName;

        $this->removeSite($siteIdentifier);

        $this->pageUids = [];
        $this->contentByPage = [];
        $root = $scenario['root'] ?? [];
        $root['is_siteroot'] = 1;
        $root['children'] = $scenario['pages'] ?? [];
        $this->createPages(['root' => $root], 0);
        if (!isset($this->pageUids['storage'])) {
            throw new \InvalidArgumentException('Demo scenario "' . $scenarioName . '" has no page with key "storage"', 1762870002);
        }

        $this->siteWriter->write($siteIdentifier, [
            'rootPageId' => $this->pageUids['root'],
            'base' => rtrim($baseUrl, '/') . $scenario['basePath'],
            'dependencies' => [$scenario['siteSet']],
            'languages' => [
                [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'locale' => 'en_US.UTF-8',
                    'navigationTitle' => 'English',
                    'flag' => 'us',
                ],
            ],
        ]);

        $this->tenantService->addAll($this->pageUids['storage']);
        $this->solrCoreRepository->useStoragePid($this->pageUids['storage']);
        /** @var SolrCore|null $solrCore */
        $solrCore = $this->solrCoreRepository->findAll()->getFirst();
        $solrCoreUid = $solrCore?->getUid() ?? 0;

        $this->siteWriter->writeSettings($siteIdentifier, [
            'plugin' => [
                'tx_dlf' => [
                    'persistence' => [
                        'storagePid' => $this->pageUids['storage'],
                        'solrCoreUid' => $solrCoreUid,
                    ],
                ],
            ],
        ]);

        $this->createContent($solrCoreUid);

        $this->createDocuments($scenarioName, $scenario, $baseUrl);

        return $this->pageUids;
    }

    /**
     * Copy the documents of the scenario to the public "kitodo-demo" directory of the fileadmin,
     * replace the document URL placeholder in their METS files and generate placeholder images if configured.
     *
     * @access protected
     *
     * @param string $scenarioName
     * @param mixed[] $scenario
     * @param string $baseUrl
     *
     * @return string[] URLs of the METS files of the copied documents
     */
    protected function createDocuments(string $scenarioName, array $scenario, string $baseUrl): array
    {
        $relativeDir = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . 'kitodo-demo/' . $scenarioName . '/';
        $targetDir = Environment::getPublicPath() . '/' . $relativeDir;
        GeneralUtility::rmdir($targetDir, true);

        $metsUrls = [];
        foreach ($scenario['documents'] ?? [] as $document) {
            if ($baseUrl === '') {
                // The METS files are loaded via HTTP, which requires an absolute URL.
                throw new \InvalidArgumentException('Demo scenario "' . $scenarioName . '" with documents requires a base URL', 1762870005);
            }
            $documentDir = $targetDir . $document['name'] . '/';
            $documentUrl = rtrim($baseUrl, '/') . '/' . $relativeDir . $document['name'];
            GeneralUtility::copyDirectory(GeneralUtility::getFileAbsFileName(self::DOCUMENTS_PATH . $document['name']), $documentDir);

            $mets = (string) file_get_contents($documentDir . 'mets.xml');
            if (!empty($scenario['generatePlaceholderImages'])) {
                $this->generatePlaceholderImages($mets, $documentDir, $document['pageSize'] ?? [1000, 1400]);
            }
            GeneralUtility::writeFile($documentDir . 'mets.xml', str_replace(self::DOCUMENT_URL_PLACEHOLDER, $documentUrl, $mets), true);
            $metsUrls[] = $documentUrl . '/mets.xml';
        }
        return $metsUrls;
    }

    /**
     * Generate a SVG placeholder image for every SVG file of the given METS file, labeled with the page number
     * and the title of the document. Images of the file group "THUMBS" are scaled down to thumbnail size.
     *
     * @access protected
     *
     * @param string $mets Content of the METS file
     * @param string $documentDir Directory the image paths relative to the document URL placeholder are resolved against
     * @param int[] $pageSize Width and height of the page images
     *
     * @return void
     */
    protected function generatePlaceholderImages(string $mets, string $documentDir, array $pageSize): void
    {
        [$width, $height] = $pageSize;
        $thumbnailScale = 150 / max($width, $height);

        $dom = new \DOMDocument();
        $dom->loadXML($mets);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('mets', 'http://www.loc.gov/METS/');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
        $title = htmlspecialchars($xpath->evaluate('string(//mets:structMap[@TYPE="LOGICAL"]/mets:div/@LABEL)'));

        foreach ($xpath->query('//mets:structMap[@TYPE="PHYSICAL"]//mets:div[@TYPE="page"]') ?: [] as $page) {
            if (!$page instanceof \DOMElement) {
                continue;
            }
            foreach ($xpath->query('mets:fptr/@FILEID', $page) ?: [] as $fileId) {
                $file = ($xpath->query('//mets:file[@ID="' . $fileId->nodeValue . '"][@MIMETYPE="image/svg+xml"]') ?: null)?->item(0);
                if (!$file instanceof \DOMElement || !$file->parentNode instanceof \DOMElement) {
                    continue;
                }
                $href = (string) $xpath->evaluate('string(mets:FLocat/@xlink:href)', $file);
                if (!str_starts_with($href, self::DOCUMENT_URL_PLACEHOLDER . '/')) {
                    continue;
                }
                $scale = $file->parentNode->getAttribute('USE') === 'THUMBS' ? $thumbnailScale : 1;
                $path = $documentDir . substr($href, strlen(self::DOCUMENT_URL_PLACEHOLDER . '/'));
                GeneralUtility::mkdir_deep(dirname($path));
                GeneralUtility::writeFile($path, sprintf(
                    self::PLACEHOLDER_SVG,
                    $width,
                    $height,
                    (int) round($width * $scale),
                    (int) round($height * $scale),
                    intdiv($height, 2),
                    $page->getAttribute('ORDER'),
                    intdiv($height, 2) + 100,
                    $title
                ), true);
            }
        }
    }

    /**
     * Load the scenario with the given name.
     *
     * @access protected
     *
     * @param string $scenarioName
     *
     * @return mixed[]
     */
    protected function loadScenario(string $scenarioName): array
    {
        return $this->yamlFileLoader->load(self::SCENARIO_PATH . $scenarioName . '.yaml');
    }

    /**
     * Delete the site with the given identifier including its page tree and all records on it.
     *
     * @access protected
     *
     * @param string $siteIdentifier
     *
     * @return void
     */
    protected function removeSite(string $siteIdentifier): void
    {
        try {
            $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        } catch (SiteNotFoundException $e) {
            return;
        }
        $this->process([], ['pages' => [$site->getRootPageId() => ['delete' => 1]]]);
        $this->siteWriter->delete($siteIdentifier);
    }

    /**
     * Create the given pages and their subpages recursively, and collect their content elements.
     *
     * @access protected
     *
     * @param mixed[] $pages Page definitions indexed by their scenario key
     * @param int $pid UID of the parent page
     *
     * @return void
     */
    protected function createPages(array $pages, int $pid): void
    {
        $data = [];
        foreach ($pages as $key => $page) {
            $this->contentByPage[$key] = $page['content'] ?? [];
            unset($page['content'], $page['children']);
            $data['pages']['NEW' . $key] = array_merge(['hidden' => 0], $page, ['pid' => $pid]);
        }
        // Process in reverse order, since every new page is inserted as first page of its parent.
        $newIds = $this->process($data, [], true);

        foreach ($pages as $key => $page) {
            $uid = (int) ($newIds['NEW' . $key] ?? 0);
            if ($uid <= 0) {
                throw new \RuntimeException('Creating demo page "' . $key . '" failed', 1762870004);
            }
            $this->pageUids[$key] = $uid;
            if (!empty($page['children'])) {
                $this->createPages($page['children'], $this->pageUids[$key]);
            }
        }
    }

    /**
     * Create the collected content elements with resolved placeholders.
     *
     * @access protected
     *
     * @param int $solrCoreUid
     *
     * @return void
     */
    protected function createContent(int $solrCoreUid): void
    {
        $data = [];
        $count = 0;
        foreach ($this->contentByPage as $pageKey => $contentElements) {
            foreach ($contentElements as $contentElement) {
                $settings = $contentElement['settings'] ?? [];
                unset($contentElement['settings']);
                $contentElement['pid'] = $this->pageUids[$pageKey];
                $flexFormValues = [];
                foreach (array_merge($this->getFlexFormDefaults($contentElement['CType']), $settings) as $name => $value) {
                    $flexFormValues['settings.' . $name] = ['vDEF' => $value];
                }
                if (!empty($flexFormValues)) {
                    $contentElement['pi_flexform'] = ['data' => ['sDEF' => ['lDEF' => $flexFormValues]]];
                }
                $data['tt_content']['NEW' . ++$count] = $contentElement;
            }
        }
        $replacements = ['{solrCore}' => (string) $solrCoreUid];
        foreach ($this->pageUids as $key => $uid) {
            $replacements['{page:' . $key . '}'] = (string) $uid;
        }
        array_walk_recursive($data, function (&$value) use ($replacements) {
            if (is_string($value)) {
                $value = strtr($value, $replacements);
            }
        });
        // Process in reverse order, since every new content element is inserted as first element of its page.
        $this->process($data, [], true);
    }

    /**
     * Get the FlexForm default values of the given plugin as set by the backend when adding a plugin manually.
     *
     * @access protected
     *
     * @param string $cType
     *
     * @return array<string, mixed> Default values indexed by setting name without "settings." prefix
     */
    protected function getFlexFormDefaults(string $cType): array
    {
        $fieldTca = $GLOBALS['TCA']['tt_content']['columns']['pi_flexform'];
        $fieldTca['config'] = array_replace_recursive(
            $fieldTca['config'],
            $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['pi_flexform']['config'] ?? []
        );
        try {
            // Typo3 v13 resolves the data structure by the pointer fields "list_type,CType".
            $identifier = $this->flexFormTools->getDataStructureIdentifier($fieldTca, 'tt_content', 'pi_flexform', ['CType' => $cType, 'list_type' => '']);
            $dataStructure = $this->flexFormTools->parseDataStructureByIdentifier($identifier);
        } catch (\Exception $e) {
            // Plugin has no FlexForm.
            return [];
        }
        $defaults = [];
        foreach ($dataStructure['sheets']['sDEF']['ROOT']['el'] ?? [] as $name => $field) {
            $defaults[substr($name, strlen('settings.'))] = $field['config']['default'] ?? '';
        }
        return $defaults;
    }

    /**
     * Process the given data and command map with DataHandler.
     *
     * @access protected
     *
     * @param mixed[] $data
     * @param mixed[] $cmd
     * @param bool $reverseOrder
     *
     * @return array<string, int|string> UIDs of new records indexed by their NEW identifier
     *
     * @throws \RuntimeException if DataHandler reports errors
     */
    protected function process(array $data, array $cmd, bool $reverseOrder = false): array
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        // Prevent the core from creating an additional site configuration for the new root page.
        $dataHandler->isImporting = true;
        $dataHandler->reverseOrder = $reverseOrder;
        $dataHandler->start($data, $cmd);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        if (!empty($dataHandler->errorLog)) {
            throw new \RuntimeException('Creating demo records failed: ' . implode(' ', $dataHandler->errorLog), 1762870003);
        }
        return $dataHandler->substNEWwithIDs;
    }
}
