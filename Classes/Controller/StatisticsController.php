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

namespace Kitodo\Dlf\Controller;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the plugin 'Statistics' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class StatisticsController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $countTitles = $this->documentRepository->countAllTitles($this->settings);
        $countVolumes = $this->documentRepository->countAllVolumes($this->settings);

        // Set replacements.
        $args['###TITLES###'] = $countTitles . ' ' . htmlspecialchars(
            LocalizationUtility::translate(
                ($countTitles > 1 ? 'titles' : 'title'), 'dlf'
            )
        );

        $args['###VOLUMES###'] = $countVolumes . ' ' . htmlspecialchars(
            LocalizationUtility::translate(
                ($countTitles > 1 ? 'volumes' : 'volume'), 'dlf'
            )
        );

        // Apply replacements.
        $content = $this->settings['description'];
        foreach ($args as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        $this->view->assign('content', $content);
    }
}
