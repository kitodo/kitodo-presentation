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

namespace Kitodo\Dlf\Configuration;

use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlParseException;
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
     * @var PackageManager This holds the package manager
     */
    private PackageManager $packageManager;

    /**
     * @access private
     * @var YamlFileLoader This holds the YAML file loader
     */
    private YamlFileLoader $yamlFileLoader;

    /**
     * Constructs the Reader instance
     *
     * @access public
     *
     * @return void
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $this->yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
    }

    /**
     * @access public
     *
     * @param string $type The type of configuration
     *
     * @return mixed[]
     */
    public function getTenantConfiguration(string $type): array
    {
        $tenantConfiguration = $this->getConfiguration($type, '/Configuration/Kitodo/Tenant/');

        if (!empty($tenantConfiguration)) {
            return $tenantConfiguration;
        }
        return $this->getConfiguration($type, '/Configuration/Tenant/');
    }

    /**
     * @access public
     *
     * @param string $type The type of configuration
     *
     * @return mixed[]
     */
    public function getValidationConfiguration(string $type): array
    {
        return $this->getConfiguration($type, '/Configuration/Kitodo/Validation/');
    }

    /**
     * @access private
     *
     * @param string $type The type of configuration
     * @param string $path The path to the configuration file
     *
     * @return mixed[]
     */
    private function getConfiguration(string $type, string $path): array
    {
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packagePath = rtrim($package->getPackagePath(), '/');
            $configurationFile = $packagePath . $path . $type . '.yaml';
            if (is_file($configurationFile)) {
                try {
                    return $this->yamlFileLoader->load($configurationFile);
                } catch (YamlParseException $e) {
                    $this->logger->error('Invalid configuration file: ' . $configurationFile . ' - ' . $e->getMessage());
                    continue;
                }
            }
        }
        return [];
    }
}
