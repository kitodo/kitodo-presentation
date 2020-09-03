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

namespace Kitodo\Dlf\Common;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Abstract plugin class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
abstract class AbstractPlugin extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    public $extKey = 'dlf';
    public $prefixId = 'tx_dlf';
    public $scriptRelPath = 'Classes/Common/AbstractPlugin.php';
    // Plugins are cached by default (@see setCache()).
    public $pi_USER_INT_obj = false;
    public $pi_checkCHash = true;

    /**
     * This holds the current document
     *
     * @var \Kitodo\Dlf\Common\Document
     * @access protected
     */
    protected $doc;

    /**
     * This holds the plugin's parsed template
     *
     * @var string
     * @access protected
     */
    protected $template = '';

    /**
     * This holds the plugin's service for template
     *
     * @var \TYPO3\CMS\Core\Service\MarkerBasedTemplateService
     * @access protected
     */
    protected $templateService;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * Read and parse the template file
     *
     * @access protected
     *
     * @param string $part: Name of the subpart to load
     *
     * @return void
     */
    protected function getTemplate($part = '###TEMPLATE###')
    {
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        if (!empty($this->conf['templateFile'])) {
            // Load template file from configuration.
            $templateFile = $this->conf['templateFile'];
        } else {
            // Load default template from extension.
            $templateFile = 'EXT:' . $this->extKey . '/Resources/Private/Templates/' . Helper::getUnqualifiedClassName(get_class($this)) . '.tmpl';
        }
        // Substitute strings like "EXT:" in given template file location.
        $fileResource = $GLOBALS['TSFE']->tmpl->getFileName($templateFile);
        $this->template = $this->templateService->getSubpart(file_get_contents($fileResource), $part);
    }

    /**
     * Generate Path to Fluid Standalone Templates
     * @access protected
     *
     * @return string
     */
    protected function getFluidStandaloneTemplate() {
        return 'EXT:' . $this->extKey . '/Resources/Private/Templates/' . Helper::getUnqualifiedClassName(get_class($this)) . '.html';
    }

    /**
     * All the needed configuration values are stored in class variables
     * Priority: Flexforms > TS-Templates > Extension Configuration > ext_localconf.php
     *
     * @access protected
     *
     * @param array $conf: Configuration array from TS-Template
     *
     * @return void
     */
    protected function init(array $conf)
    {
        // Read FlexForm configuration.
        $flexFormConf = [];
        $this->cObj->readFlexformIntoConf($this->cObj->data['pi_flexform'], $flexFormConf);
        if (!empty($flexFormConf)) {
            $conf = Helper::mergeRecursiveWithOverrule($flexFormConf, $conf);
        }
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
        // Set default plugin variables.
        $this->pi_setPiVarDefaults();
        // Load translation files.
        $this->pi_loadLL('EXT:' . $this->extKey . '/Resources/Private/Language/' . Helper::getUnqualifiedClassName(get_class($this)) . '.xml');

        /** @var objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
    }


    /**
     * Automatically loads and renders html content from Fluid templates in template folder. The file have to be the plugin name.
     * @param array $data
     * @return mixed
     */
    protected function generateContentWithFluidStandaloneView($data = []) {
        $standaloneView = $this->objectManager->get(StandaloneView::class);
        $templatePath = GeneralUtility::getFileAbsFileName($this->getFluidStandaloneTemplate());

        $standaloneView->setFormat('html');
        $standaloneView->setTemplatePathAndFilename($templatePath);
        $standaloneView->assignMultiple($data);

        return $standaloneView->render();
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

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @abstract
     *
     * @return string The content that is displayed on the website
     */
    abstract public function main($content, $conf);

    /**
     * Parses a string into a Typoscript array
     *
     * @access protected
     *
     * @param string $string: The string to parse
     *
     * @return array The resulting typoscript array
     */
    protected function parseTS($string = '')
    {
        $parser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $parser->parse($string);
        return $parser->setup;
    }

    /**
     * Link string to the current page.
     * @see \TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_linkTP()
     *
     * @access public
     *
     * @param string $str: The content string to wrap in <a> tags
     * @param array $urlParameters: Array with URL parameters as key/value pairs
     * @param bool $cache: Should the "no_cache" parameter be added?
     * @param int $altPageId: Alternative page ID for the link.
     *
     * @return string The input string wrapped in <a> tags
     */
    public function pi_linkTP($str, $urlParameters = [], $cache = false, $altPageId = 0)
    {
        // Remove when we don't need to support TYPO3 8.7 anymore.
        if (version_compare(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version(), '9.0.0', '<')) {
            return $this->pi_linkTP_fallback($str, $urlParameters, $cache, $altPageId);
        }
        // -->
        $conf = [];
        if (!$cache) {
            $conf['no_cache'] = true;
        }
        $conf['parameter'] = $altPageId ?: ($this->pi_tmpPageId ?: 'current');
        $conf['additionalParams'] = $this->conf['parent.']['addParams'] . HttpUtility::buildQueryString($urlParameters, '&', true) . $this->pi_moreParams;
        // Add additional configuration for absolute URLs.
        $conf['forceAbsoluteUrl'] = !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0;
        $conf['forceAbsoluteUrl.']['scheme'] = !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http';
        return $this->cObj->typoLink($str, $conf);
    }

    /**
     * Link string to the current page (fallback for TYPO3 8.7)
     * @see $this->pi_linkTP()
     *
     * @deprecated
     *
     * @access public
     *
     * @param string $str: The content string to wrap in <a> tags
     * @param array $urlParameters: Array with URL parameters as key/value pairs
     * @param bool $cache: Should the "no_cache" parameter be added?
     * @param int $altPageId: Alternative page ID for the link.
     *
     * @return string The input string wrapped in <a> tags
     */
    public function pi_linkTP_fallback($str, $urlParameters = [], $cache = false, $altPageId = 0)
    {
        $conf = [];
        $conf['useCacheHash'] = $this->pi_USER_INT_obj ? 0 : $cache;
        $conf['no_cache'] = $this->pi_USER_INT_obj ? 0 : !$cache;
        $conf['parameter'] = $altPageId ? $altPageId : ($this->pi_tmpPageId ? $this->pi_tmpPageId : $this->frontendController->id);
        $conf['additionalParams'] = $this->conf['parent.']['addParams'] . GeneralUtility::implodeArrayForUrl('', $urlParameters, '', true) . $this->pi_moreParams;
        // Add additional configuration for absolute URLs.
        $conf['forceAbsoluteUrl'] = !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0;
        $conf['forceAbsoluteUrl.']['scheme'] = !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http';
        return $this->cObj->typoLink($str, $conf);
    }

    /**
     * Wraps the input string in a <div> tag with the class attribute set to the class name
     * @see \TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_wrapInBaseClass()
     *
     * @access public
     *
     * @param string $content: HTML content to wrap in the div-tags with the class of the plugin
     *
     * @return string HTML content wrapped, ready to return to the parent object.
     */
    public function pi_wrapInBaseClass($content)
    {
        if (!$this->frontendController->config['config']['disableWrapInBaseClass']) {
            // Use class name instead of $this->prefixId for content wrapping because $this->prefixId is the same for all plugins.
            $content = '<div class="tx-dlf-' . strtolower(Helper::getUnqualifiedClassName(get_class($this))) . '">' . $content . '</div>';
            if (!$this->frontendController->config['config']['disablePrefixComment']) {
                $content = "\n\n<!-- BEGIN: Content of extension '" . $this->extKey . "', plugin '" . Helper::getUnqualifiedClassName(get_class($this)) . "' -->\n\n" . $content . "\n\n<!-- END: Content of extension '" . $this->extKey . "', plugin '" . Helper::getUnqualifiedClassName(get_class($this)) . "' -->\n\n";
            }
        }
        return $content;
    }

    /**
     * Sets some configuration variables if the plugin is cached.
     *
     * @access protected
     *
     * @param bool $cache: Should the plugin be cached?
     *
     * @return void
     */
    protected function setCache($cache = true)
    {
        if ($cache) {
            // Set cObject type to "USER" (default).
            $this->pi_USER_INT_obj = false;
            $this->pi_checkCHash = true;
            if (count($this->piVars)) {
                // Check cHash or disable caching.
                $GLOBALS['TSFE']->reqCHash();
            }
        } else {
            // Set cObject type to "USER_INT".
            $this->pi_USER_INT_obj = true;
            $this->pi_checkCHash = false;
            // Plugins are of type "USER" by default, so convert it to "USER_INT".
            $this->cObj->convertToUserIntObject();
        }
    }
}
