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
use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Updates\AddDefaultFormats;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Make sure the essential namespaces are defined.
     *
     * @access public
     *
     * @return string Message informing the user of success or failure
     */
    public function checkMetadataFormats()
    {
        $formatAdder = GeneralUtility::makeInstance(AddDefaultFormats::class);
        $messageQueue = uniqid();

        if ($formatAdder->updateNecessary()) {
            Helper::addMessage(
                Helper::getLanguageService()->getLL('metadataFormats.nsNotOkayMsg'),
                Helper::getLanguageService()->getLL('metadataFormats.nsNotOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                false,
                $messageQueue
            );
        } else {
            Helper::addMessage(
                '',
                Helper::getLanguageService()->getLL('metadataFormats.nsOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                false,
                $messageQueue
            );
        }

        return Helper::renderFlashMessages($messageQueue);
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
