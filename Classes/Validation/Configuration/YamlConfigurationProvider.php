<?php

namespace Kitodo\Dlf\Validation\Configuration;

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class YamlConfigurationProvider
{

    /**
     * @access private
     * @var Logger This holds the logger
     */
    private Logger $logger;

    /**
     * @access private
     * @var ?PackageManager This holds the package manager
     */
    private ?PackageManager $packageManager;

    /**
     * @access private
     * @var ?YamlFileLoader This holds the YAML file loader
     */
    private ?YamlFileLoader $yamlFileLoader;

    /**
     * Constructs the Reader instance
     *
     * @access public
     *
     * @param ?PackageManager $packageManager The package manager
     * @param ?YamlFileLoader $yamlFileLoader The YAML file loader
     *
     * @return void
     */
    public function __construct(?PackageManager $packageManager = null, ?YamlFileLoader $yamlFileLoader = null)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->packageManager = $packageManager ?? GeneralUtility::makeInstance(PackageManager::class);
        $this->yamlFileLoader = $yamlFileLoader ?? GeneralUtility::makeInstance(YamlFileLoader::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConfiguration(string $type): array
    {
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packagePath = rtrim($package->getPackagePath(), '/');
            $configurationFile = $packagePath . '/Configuration/Kitodo/Validation/' . $type . '.yaml';
            if (is_file($configurationFile)) {
                $configuration = $this->yamlFileLoader->load($configurationFile);
                if (is_array($configuration)) {
                    return $configuration;
                } else {
                    $this->logger->error('Invalid configuration file: ' . $configurationFile);
                }
            }
        }
        return [];
    }
}
