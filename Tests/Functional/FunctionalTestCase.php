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

namespace Kitodo\Dlf\Tests\Functional;

use Dotenv\Dotenv;
use GuzzleHttp\Client as HttpClient;
use Kitodo\Dlf\Common\Solr\Solr;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Base class for functional test cases. This provides some common configuration
 * and collects utility methods for functional tests.
 */
class FunctionalTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/dlf',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'tx_dlf_doc' => [
                        'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
                    ],
                ],
            ],
            'displayErrors' => '1'
        ],
        'SC_OPTIONS' => [
            'dlf/Classes/Plugin/Toolbox.php' => []
        ],
        'EXTENSIONS' => [
            'dlf' => [], // = $this->getDlfConfiguration(), set in constructor
        ],
        'FE' => [
            'cacheHash' => [
                'enforceValidation' => false,
            ],
        ],
        'DB' => [
            'Connections' => [
                'Default' => [
                    // TODO: This is taken from the base class, minus "ONLY_FULL_GROUP_BY"; should probably rather be changed in DocumentRepository::getOaiDocumentList
                    'initCommands' => 'SET SESSION sql_mode = \'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION,NO_ZERO_DATE,NO_ZERO_IN_DATE\';',
                ],
            ],
        ],
    ];

    /**
     * By default, the testing framework wraps responses into a JSON object
     * that contains status code etc. as fields. Set this field to true to avoid
     * this behavior by not loading the json_response extension.
     *
     * @var bool
     */
    protected bool $disableJsonWrappedResponse = false;

    /**
     * @var PersistenceManager
     */
    protected PersistenceManager $persistenceManager;

    /**
     * @var string
     */
    protected string $baseUrl;

    /**
     * @var HttpClient
     */
    protected HttpClient $httpClient;

    public function __construct()
    {
        parent::__construct();

        $this->configurationToUseInTestInstance['EXTENSIONS']['dlf'] = $this->getDlfConfiguration();

        if ($this->disableJsonWrappedResponse) {
            $this->frameworkExtensionsToLoad = array_filter($this->frameworkExtensionsToLoad, function ($ext) {
                return $ext !== 'Resources/Core/Functional/Extensions/json_response';
            });
        }
    }

    /**
     * Sets up the test case environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $this->baseUrl = 'http://web:8000/public/typo3temp/var/tests/functional-' . $this->identifier . '/';
        $this->httpClient = new HttpClient([
            'base_uri' => $this->baseUrl . 'index.php',
            'http_errors' => false,
        ]);

        $this->addSiteConfig('dlf-testing');
    }

    /**
     * Returns the DLF configuration for the test instance.
     *
     * This configuration is loaded from a .env file in the test directory.
     * It includes general settings, file groups, and Solr settings.
     *
     * @return array The DLF configuration
     */
    protected function getDlfConfiguration(): array
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../Build/Test/', 'test.env');
        $dotenv->load();

        return [
            'general' => [
                'useExternalApisForMetadata' => 0,
                'requiredMetadataFields' => 'document_format'
            ],
            'files' => [
                'useGroupsImage' => 'DEFAULT,MAX',
                'useGroupsThumbnail' => 'THUMBS',
                'useGroupsDownload' => 'DOWNLOAD',
                'useGroupsFulltext' => 'FULLTEXT',
                'useGroupsAudio' => 'AUDIO',
                'useGroupsVideo' => 'VIDEO,DEFAULT',
                'useGroupsWaveform' => 'WAVEFORM'
            ],
            'solr' => [
                'host' => getenv('dlfTestingSolrHost'),
                'fields' => [
                    'autocomplete' => 'autocomplete',
                    'collection' => 'collection',
                    'default' => 'default',
                    'fulltext' => 'fulltext',
                    'geom' => 'geom',
                    'id' => 'id',
                    'license' => 'license',
                    'location' => 'location',
                    'page' => 'page',
                    'partof' => 'partof',
                    'pid' => 'pid',
                    'purl' => 'purl',
                    'recordId' => 'record_id',
                    'restrictions' => 'restrictions',
                    'root' => 'root',
                    'sid' => 'sid',
                    'terms' => 'terms',
                    'thumbnail' => 'thumbnail',
                    'timestamp' => 'timestamp',
                    'title' => 'title',
                    'toplevel' => 'toplevel',
                    'type' => 'type',
                    'uid' => 'uid',
                    'urn' => 'urn',
                    'volume' => 'volume'
                ]
            ]
        ];
    }

    /**
     * Adds a site configuration for the given identifier.
     *
     * This method creates a site configuration file in the
     * typo3conf/sites directory with the specified identifier.
     * The configuration is loaded from a YAML file and includes
     * the base URL and language settings.
     *
     * @param string $identifier The identifier for the site configuration
     *
     * @return void
     */
    protected function addSiteConfig(string $identifier): void
    {
        $siteConfig = Yaml::parseFile(__DIR__ . '/../Fixtures/siteconfig.yaml');
        $siteConfig['base'] = $this->baseUrl;
        $siteConfig['languages'][0]['base'] = $this->baseUrl;

        $siteConfigPath = $this->instancePath . '/typo3conf/sites/' . $identifier;
        @mkdir($siteConfigPath, 0775, true);
        file_put_contents($siteConfigPath . '/config.yaml', Yaml::dump($siteConfig));

        // refresh site cache (otherwise site config is not found)
        $finder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\SiteFinder::class);
        $finder->getAllSites(false); // useCache = false
    }

    protected function initializeRepository(string $className, int $storagePid)
    {
        $repository = GeneralUtility::makeInstance($className);
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds([$storagePid]);
        $repository->setDefaultQuerySettings($querySettings);

        return $repository;
    }

    /**
     * Imports Solr documents from a JSON file into the specified Solr instance.
     *
     * This method reads a JSON file containing an array of documents,
     * creates Solr documents from them, and adds them to the Solr index.
     *
     * @param Solr $solr The Solr instance to import documents into
     * @param string $path The path to the JSON file containing the documents
     *
     * @return void
     */
    protected function importSolrDocuments(Solr $solr, string $path): void
    {
        $jsonDocuments = json_decode(file_get_contents($path), true);

        $updateQuery = $solr->service->createUpdate();
        $documents = array_map(function ($jsonDoc) use ($updateQuery) {
            $document = $updateQuery->createDocument();
            foreach ($jsonDoc as $key => $value) {
                $document->setField($key, $value);
            }
            if (isset($jsonDoc['collection'])) {
                $document->setField('collection_faceting', $jsonDoc['collection']);
            }
            return $document;
        }, $jsonDocuments);
        $updateQuery->addDocuments($documents);
        $updateQuery->addCommit();
        $solr->service->update($updateQuery);
    }

    /**
     * Initializes the language service with the given locale.
     *
     * This method sets up a mock backend user with the specified locale,
     * which is then used by the LanguageServiceFactory to load the language
     * in backend mode.
     *
     * @param string $locale The locale to set for the backend user
     *
     * @return void
     */
    protected function initLanguageService(string $locale): void
    {
        // create mock backend user and set language
        // which is loaded by LanguageServiceFactory as default value in backend mode
        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->user["lang"] = $locale;
        $GLOBALS['BE_USER'] = $backendUser;
    }

    /**
     * Assert that $sub is recursively contained within $super.
     *
     * @param array $sub
     * @param array $super
     * @param string $message
     *
     * @return void
     */
    protected function assertArrayMatches(array $sub, array $super, string $message = ''): void
    {
        self::assertEquals($sub, ArrayUtility::intersectRecursive($super, $sub), $message);
    }
}
