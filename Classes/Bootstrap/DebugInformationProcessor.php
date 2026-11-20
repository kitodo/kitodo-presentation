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

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Data processor providing debug information for the footer of the demo site sets:
 * versions of Typo3, PHP and Kitodo.Presentation, and the git branch and commit of Kitodo.Presentation.
 *
 * Usage in TypoScript: dataProcessing.20 = Kitodo\Dlf\Bootstrap\DebugInformationProcessor
 * The information is available as variable "debugInformation", or as the variable given by the option "as".
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class DebugInformationProcessor implements DataProcessorInterface
{
    /**
     * Add the debug information to the processed data.
     *
     * @access public
     *
     * @param ContentObjectRenderer $cObj
     * @param mixed[] $contentObjectConfiguration
     * @param mixed[] $processorConfiguration
     * @param mixed[] $processedData
     *
     * @return mixed[]
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $git = $this->getGitInformation(ExtensionManagementUtility::extPath('dlf') . '.git');
        $processedData[$processorConfiguration['as'] ?? 'debugInformation'] = [
            'typo3Version' => (new Typo3Version())->getVersion(),
            'phpVersion' => PHP_VERSION,
            'extensionVersion' => ExtensionManagementUtility::getExtensionVersion('dlf'),
            'gitBranch' => $git['branch'],
            'gitCommit' => $git['commit'],
        ];
        return $processedData;
    }

    /**
     * Get the current branch and commit of the given git directory without running git.
     *
     * @access protected
     *
     * @param string $gitDir Path of the ".git" directory
     *
     * @return array{branch: string, commit: string} Branch and commit, empty if not available (e.g. no git repository
     *         or detached HEAD without branch)
     */
    protected function getGitInformation(string $gitDir): array
    {
        $git = ['branch' => '', 'commit' => ''];
        $head = is_file($gitDir . '/HEAD') ? trim((string) file_get_contents($gitDir . '/HEAD')) : '';
        if (!str_starts_with($head, 'ref: ')) {
            // No git repository, or detached HEAD containing the commit itself
            $git['commit'] = $head;
            return $git;
        }
        $ref = substr($head, strlen('ref: '));
        $git['branch'] = preg_replace('#^refs/heads/#', '', $ref) ?? $ref;
        if (is_file($gitDir . '/' . $ref)) {
            $git['commit'] = trim((string) file_get_contents($gitDir . '/' . $ref));
        } elseif (is_file($gitDir . '/packed-refs')) {
            // Refs may be packed into a single file with lines "<commit> <ref>"
            foreach (file($gitDir . '/packed-refs', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                if (str_ends_with($line, ' ' . $ref)) {
                    $git['commit'] = substr($line, 0, 40);
                }
            }
        }
        return $git;
    }
}
