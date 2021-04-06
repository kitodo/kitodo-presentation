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

namespace Kitodo\Dlf\Plugin;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;

/**
 * Plugin 'Statistics' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Statistics extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Statistics.php';

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Turn cache on.
        $this->setCache(true);
        // Quit without doing anything if required configuration variables are not set.
        if (empty($this->conf['pages'])) {
            Helper::devLog('Incomplete plugin configuration', DEVLOG_SEVERITY_WARNING);
            return $content;
        }
        // Get description.
        $content .= $this->pi_RTEcssText($this->conf['description']);
        // Check for selected collections.
        if ($this->conf['collections']) {
            // Include only selected collections.
            $countTitles = DocumentRepository::countTitlesWithSelectedCollections(
                $this->conf['pages'],
                $this->conf['collections']
            );

            $countVolumes = DocumentRepository::countVolumesWithSelectedCollections(
                $this->conf['pages'],
                $this->conf['collections']
            );
        } else {
            // Include all collections.
            $countTitles = DocumentRepository::countTitles($this->conf['pages']);

            $countVolumes = DocumentRepository::countVolumes($this->conf['pages']);
        }

        // Set replacements.
        $replace = [
            'key' => [
                '###TITLES###',
                '###VOLUMES###'
            ],
            'value' => [
                $countTitles . ($countTitles > 1 ? htmlspecialchars($this->pi_getLL('titles', '')) : htmlspecialchars($this->pi_getLL('title', ''))),
                $countVolumes . ($countVolumes > 1 ? htmlspecialchars($this->pi_getLL('volumes', '')) : htmlspecialchars($this->pi_getLL('volume', '')))
            ]
        ];
        // Apply replacements.
        $content = str_replace($replace['key'], $replace['value'], $content);
        return $this->pi_wrapInBaseClass($content);
    }
}
