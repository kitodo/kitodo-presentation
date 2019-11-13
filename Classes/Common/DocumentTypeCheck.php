<?php

namespace Kitodo\Dlf\Common;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Document Type Checker for usage as Typoscript Condition
 * @see dlf/ext_localconf.php->user_dlf_docTypeCheck()
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class DocumentTypeCheck
{
    /**
     * This holds the current document
     *
     * @var \Kitodo\Dlf\Common\Document
     * @access protected
     */
    protected $doc;

    /**
     * This holds the extension key
     *
     * @var string
     * @access protected
     */
    protected $extKey = 'dlf';

    /**
     * This holds the current DLF plugin parameters
     * @see __contruct()
     *
     * @var array
     * @access protected
     */
    protected $piVars = [];

    /**
     * This holds the DLF parameter prefix
     *
     * @var string
     * @access protected
     */
    protected $prefixId = 'tx_dlf';

    /**
     * Check the current document's type.
     *
     * @access public
     *
     * @return string The type of the current document
     */
    public function getDocType()
    {
        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Quit without doing anything if document not available.
            return '';
        }
        $toc = $this->doc->tableOfContents;
        if ($this->doc instanceof IiifManifest && (!isset($toc[0]['type']) || array_search($toc[0]['type'], ['newspaper', 'year', 'issue']) !== false)) {
            // Calendar plugin does not support IIIF (yet). Abort for all newspaper related types or if type is missing.
            return '';
        }
        /*
         * Get the document type
         *
         * 1. newspaper
         *    case 1) - type=newspaper|ephemera
         *            - children array ([0], [1], [2], ...) -> type = year --> Newspaper Anchor File
         *    case 2) - type=newspaper|ephemera
         *            - children array ([0]) --> type = year
         *            - children array ([0], [1], [2], ...) --> type = month --> Year Anchor File
         *    case 3) - type=newspaper|ephemera
         *            - children array ([0]) --> type = year
         *            - children array ([0]) --> type = month
         *            - children array ([0], [1], [2], ...) --> type = day --> Issue
         */
        switch ($toc[0]['type']) {
            case 'newspaper':
            case 'ephemera':
                $nodes_year = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper" or @TYPE="ephemera"]/mets:div[@TYPE="year"]');
                if (count($nodes_year) > 1) {
                    // Multiple years means this is a newspaper's anchor file.
                    return 'newspaper';
                } else {
                    $nodes_month = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper" or @TYPE="ephemera"]/mets:div[@TYPE="year"]/mets:div[@TYPE="month"]');
                    $nodes_day = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper" or @TYPE="ephemera"]/mets:div[@TYPE="year"]/mets:div[@TYPE="month"]/mets:div[@TYPE="day"]');
                    $nodes_issue = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper" or @TYPE="ephemera"]/mets:div[@TYPE="year"]//mets:div[@TYPE="issue"]');
                    $nodes_issue_current = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper" or @TYPE="ephemera"]/mets:div[@TYPE="year"]//mets:div[@TYPE="issue"]/@DMDID');
                    if (
                        count($nodes_year) == 1
                        && count($nodes_issue) == 0
                    ) {
                        // It's possible to have only one year in the newspaper's anchor file.
                        return 'newspaper';
                    } elseif (
                        count($nodes_year) == 1
                        && count($nodes_month) > 1
                    ) {
                        // One year, multiple months means this is a year's anchor file.
                        return 'year';
                    } elseif (
                        count($nodes_year) == 1
                        && count($nodes_month) == 1
                        && count($nodes_day) > 1
                    ) {
                        // One year, one month, one or more days means this is a year's anchor file.
                        return 'year';
                    } elseif (
                        count($nodes_year) == 1
                        && count($nodes_month) == 1
                        && count($nodes_day) == 1
                        && count($nodes_issue_current) == 0
                    ) {
                        // One year, one month, a single day, one or more issues (but not the current one) means this is a year's anchor file.
                        return 'year';
                    } else {
                        // In all other cases we assume it's a newspaper's issue.
                        return 'issue';
                    }
                }
                break;
            default:
                return $toc[0]['type'];
        }
    }

    /**
     * Loads the current document into $this->doc
     *
     * @access protected
     *
     * @return void
     */
    protected function loadDocument()
    {
        // Check for required variable.
        if (!empty($this->piVars['id'])) {
            // Get instance of \Kitodo\Dlf\Common\Document.
            $this->doc = Document::getInstance($this->piVars['id']);
            if (!$this->doc->ready) {
                // Destroy the incomplete object.
                $this->doc = null;
                Helper::devLog('Failed to load document with UID ' . $this->piVars['id'], DEVLOG_SEVERITY_WARNING);
            }
        } elseif (!empty($this->piVars['recordId'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Get UID of document with given record identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.uid AS uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($this->piVars['recordId'])),
                    Helper::whereExpression('tx_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                $this->piVars['id'] = $resArray['uid'];
                // Set superglobal $_GET array.
                $_GET[$this->prefixId]['id'] = $this->piVars['id'];
                // Unset variable to avoid infinite looping.
                unset($this->piVars['recordId'], $_GET[$this->prefixId]['recordId']);
                // Try to load document.
                $this->loadDocument();
            } else {
                Helper::devLog('Failed to load document with record ID "' . $this->piVars['recordId'] . '"', DEVLOG_SEVERITY_WARNING);
            }
        }
    }

    /**
     * Initializes the hook by setting initial variables.
     *
     * @access public
     *
     * @return void
     */
    public function __construct()
    {
        // Load current plugin parameters.
        $this->piVars = GeneralUtility::_GPmerged($this->prefixId);
    }
}
