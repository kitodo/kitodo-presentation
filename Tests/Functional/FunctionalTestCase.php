<?php

namespace Kitodo\Dlf\Tests\Functional;

use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 * Base class for functional test cases. This provides some common configuration
 * and collects utility methods for functional tests.
 */
class FunctionalTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/dlf',
    ];

    protected $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'tx_dlf_doc' => [
                        'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
                    ],
                ],
            ],
        ],
        'EXTENSIONS' => [
            'dlf' => [], // = $this->getDlfConfiguration(), set in constructor
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
    protected $disableJsonWrappedResponse = false;

    /** @var ObjectManager */
    protected $objectManager;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var HttpClient
     */
    protected $httpClient;

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

    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->baseUrl = 'http://web:8000/public/typo3temp/var/tests/functional-' . $this->identifier . '/';
        $this->httpClient = new HttpClient([
            'base_uri' => $this->baseUrl,
            'http_errors' => false,
        ]);

        $this->addSiteConfig('dlf-testing', $this->baseUrl);
    }

    protected function getDlfConfiguration()
    {
        return [
            'solrFieldAutocomplete' => 'autocomplete',
            'solrFieldCollection' => 'collection',
            'solrFieldDefault' => 'default',
            'solrFieldFulltext' => 'fulltext',
            'solrFieldGeom' => 'geom',
            'solrFieldId' => 'id',
            'solrFieldLicense' => 'license',
            'solrFieldLocation' => 'location',
            'solrFieldPage' => 'page',
            'solrFieldPartof' => 'partof',
            'solrFieldPid' => 'pid',
            'solrFieldPurl' => 'purl',
            'solrFieldRecordId' => 'record_id',
            'solrFieldRestrictions' => 'restrictions',
            'solrFieldRoot' => 'root',
            'solrFieldSid' => 'sid',
            'solrFieldTerms' => 'terms',
            'solrFieldThumbnail' => 'thumbnail',
            'solrFieldTimestamp' => 'timestamp',
            'solrFieldTitle' => 'title',
            'solrFieldToplevel' => 'toplevel',
            'solrFieldType' => 'type',
            'solrFieldUid' => 'uid',
            'solrFieldUrn' => 'urn',
            'solrFieldVolume' => 'volume',

            'solrHost' => getenv('dlfTestingSolrHost'),
        ];
    }

    protected function addSiteConfig($identifier, $baseUrl)
    {
        $siteConfig = Yaml::parseFile(__DIR__ . '/../Fixtures/siteconfig.yaml');
        $siteConfig['base'] = $baseUrl;
        $siteConfig['languages'][0]['base'] = $baseUrl;

        $siteConfigPath = $this->instancePath . '/typo3conf/sites/' . $identifier;
        @mkdir($siteConfigPath, 0775, true);
        file_put_contents($siteConfigPath . '/config.yaml', Yaml::dump($siteConfig));
    }

    protected function initializeRepository(string $className, int $storagePid)
    {
        $repository = $this->objectManager->get($className);

        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds([$storagePid]);
        $repository->setDefaultQuerySettings($querySettings);

        return $repository;
    }
}
