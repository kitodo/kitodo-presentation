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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for accessing and processing the configuration of USE groups.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class UseGroupsConfiguration
{

    /**
     * @access private
     * @var ?UseGroupsConfiguration The instance of singleton.
     */
    private static $instance = null;

    /**
     * @access private
     * @var array The array of configured USE groups.
     */
    private array $useGroups = [];

    /**
     * Constructor for singleton.
     *
     * @access private
     *
     * @return void
     */
    private function __construct()
    {
        // Get configured USE attributes.
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf', 'files');

        $configKeys = [
            'useGroupsImage',
            'useGroupsThumbnail',
            'useGroupsDownload',
            'useGroupsFulltext',
            'useGroupsAudio',
            'useGroupsScore',
            'useGroupsVideo',
            'useGroupsWaveform',
            'useGroupsModel'
        ];

        foreach ($configKeys as $key) {
            if (!empty($extConf[$key])) {
                $this->useGroups[$key] = GeneralUtility::trimExplode(',', $extConf[$key]);
            }
        }
    }

    /**
     * Get the instance of singleton.
     *
     * @access public
     *
     * @return UseGroupsConfiguration
     */
    public static function getInstance(): UseGroupsConfiguration
    {
        if (self::$instance == null) {
            self::$instance = new UseGroupsConfiguration();
        }

        return self::$instance;
    }

    /**
     * Get the configuration for 'Audio' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getAudio(): array
    {
        return $this->getByType('Audio');
    }

    /**
     * Get the configuration for 'Download' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getDownload(): array
    {
        return $this->getByType('Download');
    }

    /**
     * Get the configuration for 'Fulltext' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getFulltext(): array
    {
        return $this->getByType('Fulltext');
    }

    /**
     * Get the configuration for 'Image' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getImage(): array
    {
        return $this->getByType('Image');
    }

    /**
     * Get the configuration for 'Model' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getModel(): array
    {
        return $this->getByType('Model');
    }

    /**
     * Get the configuration for 'Score' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getScore(): array
    {
        return $this->getByType('Score');
    }

    /**
     * Get the configuration for 'Thumbnail' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getThumbnail(): array
    {
        return $this->getByType('Thumbnail');
    }

    /**
     * Get the configuration for 'Video' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getVideo(): array
    {
        return $this->getByType('Video');
    }

    /**
     * Get the configuration for 'Waveform' use groups type.
     *
     * @access public
     *
     * @return array
     */
    public function getWaveform(): array
    {
        return $this->getByType('Waveform');
    }

    /**
     * Get the configuration for use groups.
     *
     * @access public
     *
     * @return array
     */
    public function get(): array
    {
        return $this->useGroups;
    }

    /**
     * Get the configuration for given use groups type.
     *
     * @access private
     *
     * @param string $useType possible values: 'Audio', 'Download', 'Fulltext', 'Image', 'Score', 'Thumbnail', 'Video', 'Waveform', 'Model'
     *
     * @return array
     */
    //TODO: replace $useType with enum after dropping PHP 7.x support
    private function getByType(string $useType): array
    {
        $useType = 'useGroups' . ucfirst($useType);
        return array_key_exists($useType, $this->useGroups) ? $this->useGroups[$useType] : [];
    }
}
