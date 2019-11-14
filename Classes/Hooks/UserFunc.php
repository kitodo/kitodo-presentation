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

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper for custom "userFunc"
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class UserFunc
{
    /**
     * This holds the extension's parameter prefix
     * @see \Kitodo\Dlf\Common\AbstractPlugin
     *
     * @var string
     * @access protected
     */
    protected $prefixId = 'tx_dlf';

    /**
     * Helper to display document's thumbnail
     * @see dlf/Configuration/TCA/tx_dlf_documents.php
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     *
     * @return string HTML <img> tag for thumbnail
     */
    public function displayThumbnail(&$params)
    {
        // Simulate TCA field type "passthrough".
        $output = '<input type="hidden" name="' . $params['itemFormElName'] . '" value="' . $params['itemFormElValue'] . '" />';
        if (!empty($params['itemFormElValue'])) {
            $output .= '<img alt="Thumbnail" title="' . $params['itemFormElValue'] . '" src="' . $params['itemFormElValue'] . '" />';
        }
        return $output;
    }

    /**
     * Helper to check the current document's type in a Typoscript condition
     * @see dlf/ext_localconf.php->user_dlf_docTypeCheck()
     *
     * @access public
     *
     * @param int $cPid The PID for the metadata definitions
     *
     * @return string The type of the current document or 'undefined'
     */
    public function getDocumentType(int $cPid)
    {
        $type = 'undefined';
        // Load document with current plugin parameters.
        $doc = $this->loadDocument(GeneralUtility::_GPmerged($this->prefixId));
        if ($doc === null) {
            return $type;
        }
        $metadata = $doc->getTitledata($cPid);
        if (!empty($metadata['type'][0])) {
            // Calendar plugin does not support IIIF (yet). Abort for all newspaper related types.
            if (
                $doc instanceof IiifManifest
                && array_search($metadata['type'][0], ['newspaper', 'ephemera', 'year', 'issue']) !== false
            ) {
                return $type;
            }
            $type = $metadata['type'][0];
        }
        return $type;
    }

    /**
     * Loads the current document
     * @see \Kitodo\Dlf\Common\AbstractPlugin->loadDocument()
     *
     * @access protected
     *
     * @param array $piVars The current plugin variables containing a document identifier
     *
     * @return \Kitodo\Dlf\Common\Document Instance of the current document
     */
    protected function loadDocument(array $piVars)
    {
        // Check for required variable.
        if (!empty($piVars['id'])) {
            // Get instance of document.
            $doc = Document::getInstance($piVars['id']);
            if ($doc->ready) {
                return $doc;
            } else {
                Helper::devLog('Failed to load document with UID ' . $piVars['id'], DEVLOG_SEVERITY_WARNING);
            }
        } elseif (!empty($piVars['recordId'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Get UID of document with given record identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.uid AS uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($piVars['recordId'])),
                    Helper::whereExpression('tx_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                // Try to load document.
                return $this->loadDocument(['id' => $resArray['uid']]);
            } else {
                Helper::devLog('Failed to load document with record ID "' . $piVars['recordId'] . '"', DEVLOG_SEVERITY_WARNING);
            }
        }
    }
}
