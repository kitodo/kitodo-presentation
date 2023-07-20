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
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Hooks and helper for \TYPO3\CMS\Core\TypoScript\ConfigurationForm
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ConfigurationForm
{

    /**
     * Check if a connection to a Solr server could be established with the given credentials.
     *
     * @access public
     *
     * @return string Message informing the user of success or failure
     */
    public function checkSolrConnection()
    {
        $solr = Solr::getInstance();
        if ($solr->ready) {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('solr.status'),
                Helper::getLanguageService()->getLL('solr.connected'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('solr.error'),
                Helper::getLanguageService()->getLL('solr.notConnected'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
        }
        return Helper::renderFlashMessages();
    }

    /**
     * This is the constructor.
     *
     * @access public
     *
     * @return void
     */
    public function __construct()
    {
        // Load backend localization file.
        Helper::getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_be.xlf');
    }

}
