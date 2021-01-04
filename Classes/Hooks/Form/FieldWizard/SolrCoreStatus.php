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

namespace Kitodo\Dlf\Hooks\Form\FieldWizard;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Backend\Form\AbstractNode;

/**
 * FieldWizard renderType for TYPO3 FormEngine
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class SolrCoreStatus extends AbstractNode
{
    /**
     * Shows Solr core status for given 'index_name'
     *
     * @access public
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        // Show only when editing existing records.
        if ($this->data['command'] !== 'new') {
            $core = $this->data['databaseRow']['index_name'];
            // Load localization file.
            $GLOBALS['LANG']->includeLLFile('EXT:dlf/Resources/Private/Language/FlashMessages.xml');
            // Get Solr instance.
            $solr = Solr::getInstance($core);
            if ($solr->ready) {
                // Get core data.
                $coreAdminQuery = $solr->service->createCoreAdmin();
                $action = $coreAdminQuery->createStatus();
                $action->setCore($core);
                $coreAdminQuery->setAction($action);
                $result = $solr->service->coreAdmin($coreAdminQuery)->getStatusResult();
                $uptime = $result->getUptime();
                $numDocuments = $result->getNumberOfDocuments();
                $startTime = $result->getStartTime() ? date_format($result->getStartTime(), 'c') : 'N/A';
                $lastModified = $result->getLastModified() ? date_format($result->getLastModified(), 'c') : 'N/A';
                // Create flash message.
                Helper::addMessage(
                    nl2br(htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('flash.coreStatusDetails'), $startTime, $uptime, $lastModified, $numDocuments))),
                    htmlspecialchars(sprintf($GLOBALS['LANG']->getLL('flash.coreStatus'), $core)),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                );
            } else {
                // Could not fetch core status.
                Helper::addMessage(
                    htmlspecialchars($GLOBALS['LANG']->getLL('solr.error')),
                    htmlspecialchars($GLOBALS['LANG']->getLL('solr.notConnected')),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }
            // Add message to result array.
            $result['html'] = Helper::renderFlashMessages();
        }
        return $result;
    }
}
