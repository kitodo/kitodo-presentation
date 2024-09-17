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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller class for the plugin 'Statistics'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class StatisticsController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        $foundNumbers = $this->documentRepository->getStatisticsForSelectedCollection($this->settings);

        // Set replacements.
        $args['###TITLES###'] = $foundNumbers['titles'] . ' ' . htmlspecialchars(
            LocalizationUtility::translate(
                ($foundNumbers['titles'] > 1 ? 'titles' : 'title'), 'dlf'
            )
        );

        $args['###VOLUMES###'] = $foundNumbers['volumes'] . ' ' . htmlspecialchars(
            LocalizationUtility::translate(
                ($foundNumbers['volumes'] > 1 ? 'volumes' : 'volume'), 'dlf'
            )
        );

        // Apply replacements.
        $content = $this->settings['description'];
        foreach ($args as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        $this->view->assign('content', $content);

        return $this->htmlResponse();
    }
}
