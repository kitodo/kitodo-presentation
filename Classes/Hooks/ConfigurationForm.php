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

namespace Kitodo\Dlf\Hooks;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr\Solr;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Hooks and helper for \TYPO3\CMS\Core\TypoScript\ConfigurationForm
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ConfigurationForm
{
    const LANG_PREFIX = 'LLL:EXT:dlf/Resources/Private/Language/locallang_be.xlf:';

    /**
     * Check if a connection to a Solr server could be established with the given credentials.
     *
     * @access public
     *
     * @return string Message informing the user of success or failure
     */
    public function checkSolrConnection(): string
    {
        $solr = Solr::getInstance();
        if ($solr->ready) {
            Helper::addMessage(
                Helper::getLanguageService()->sL(self::LANG_PREFIX . 'solr.status'),
                Helper::getLanguageService()->sL(self::LANG_PREFIX . 'solr.connected'),
                ContextualFeedbackSeverity::OK
            );
        } else {
            Helper::addMessage(
                Helper::getLanguageService()->sL(self::LANG_PREFIX . 'solr.error'),
                Helper::getLanguageService()->sL(self::LANG_PREFIX . 'solr.notConnected'),
                ContextualFeedbackSeverity::WARNING
            );
        }
        return Helper::renderFlashMessages();
    }

}
