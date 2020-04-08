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

namespace Kitodo\Dlf\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ViewHelper to get page info
 *
 * # Example: Basic example
 * <code>
 * <si:pageInfo page="123">
 *	<span>123</span>
 * </code>
 * <output>
 * Will output the page record
 * </output>
 *
 * @package TYPO3
 */
abstract class AbstractViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * This holds the current document
     *
     * @var \Kitodo\Dlf\Common\Document
     * @access protected
     */
    protected $doc;


    /**
     * All the needed configuration values are stored in class variables
     * Priority: Flexforms > TS-Templates > Extension Configuration > ext_localconf.php
     *
     * @access protected
     *
     * @param array $conf: configuration array from TS-Template
     *
     * @return void
     */
    protected function init(array $conf)
    {
        // Read plugin TS configuration.
        $pluginConf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dlf_' . strtolower(Helper::getUnqualifiedClassName(get_class($this))) . '.'];
        if (is_array($pluginConf)) {
            $conf = Helper::mergeRecursiveWithOverrule($pluginConf, $conf);
        }
        // Read general TS configuration.
        $generalConf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.'];
        if (is_array($generalConf)) {
            $conf = Helper::mergeRecursiveWithOverrule($generalConf, $conf);
        }
        // Read extension configuration.
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
        if (is_array($extConf)) {
            $conf = Helper::mergeRecursiveWithOverrule($extConf, $conf);
        }
        // Read TYPO3_CONF_VARS configuration.
        $varsConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey];
        if (is_array($varsConf)) {
            $conf = Helper::mergeRecursiveWithOverrule($varsConf, $conf);
        }
        $this->conf = $conf;
        // Load translation files.
        $this->pi_loadLL('EXT:' . $this->extKey . '/Resources/Private/Language/' . Helper::getUnqualifiedClassName(get_class($this)) . '.xml');
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
        if (
            !empty($this->piVars['id'])
            && !empty($this->conf['pages'])
        ) {
            // Should we exclude documents from other pages than $this->conf['pages']?
            $pid = (!empty($this->conf['excludeOther']) ? intval($this->conf['pages']) : 0);
            // Get instance of \Kitodo\Dlf\Common\Document.
            $this->doc = Document::getInstance($this->piVars['id'], $pid);
            if (!$this->doc->ready) {
                // Destroy the incomplete object.
                $this->doc = null;
                Helper::devLog('Failed to load document with UID ' . $this->piVars['id'], DEVLOG_SEVERITY_ERROR);
            } else {
                // Set configuration PID.
                $this->doc->cPid = $this->conf['pages'];
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
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                $this->piVars['id'] = $resArray['uid'];
                // Set superglobal $_GET array and unset variables to avoid infinite looping.
                $_GET[$this->prefixId]['id'] = $this->piVars['id'];
                unset($this->piVars['recordId'], $_GET[$this->prefixId]['recordId']);
                // Try to load document.
                $this->loadDocument();
            } else {
                Helper::devLog('Failed to load document with record ID "' . $this->piVars['recordId'] . '"', DEVLOG_SEVERITY_ERROR);
            }
        } else {
            Helper::devLog('Invalid UID ' . $this->piVars['id'] . ' or PID ' . $this->conf['pages'] . ' for document loading', DEVLOG_SEVERITY_ERROR);
        }
    }
}
