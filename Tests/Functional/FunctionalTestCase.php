<?php

namespace Kitodo\Dlf\Tests\Functional;

use GuzzleHttp\Client as HttpClient;
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
    }

    protected function getDlfConfiguration()
    {
        return [];
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
