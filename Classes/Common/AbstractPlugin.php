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

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract plugin class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
abstract class AbstractPlugin extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
    public $extKey = 'dlf';
    public $prefixId = 'tx_dlf';
    public $scriptRelPath = 'Classes/Common/AbstractPlugin.php';
    // Plugins are cached by default (@see setCache()).
    public $pi_USER_INT_obj = FALSE;
    public $pi_checkCHash = TRUE;

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
     * Read and parse the template file
     *
     * @access protected
     *
     * @param string $part: Name of the subpart to load
     *
     * @return void
     */
    protected function getTemplate($part = '###TEMPLATE###', $directory = '', $tool = false) {
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        if (!empty($this->conf['templateFile'])) {
            // Load template file from configuration.
            if ($tool) {
                $templateLocation = 'EXT:'.$this->extKey.'/Resources/Private/Templates/' . Helper::getUnqualifiedClassName(get_class($this)) . '.tmpl';
                $this->template = $this->templateService->getSubpart($this->cObj->fileResource($templateLocation), $part);
            } else {
                $this->template = $this->templateService->getSubpart($this->cObj->fileResource($this->conf['templateFile']), $part);
            }

        } else {
            if (empty($directory)) {
                $templateLocation = 'EXT:'.$this->extKey.'/Resources/Private/Templates/' . Helper::getUnqualifiedClassName(get_class($this)) . '.tmpl';
            } else {
                $templateLocation = 'EXT:'.$this->extKey.'/Resources/Private/Templates/'. $directory . '/' . Helper::getUnqualifiedClassName(get_class($this)) . '.tmpl';
            }

            // Load default template file.
            $this->template = $this->templateService->getSubpart($this->cObj->fileResource($templateLocation), $part);
        }

    }

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
    protected function init(array $conf) {
        // Read FlexForm configuration.
        $flexFormConf = [];
        $this->cObj->readFlexformIntoConf($this->cObj->data['pi_flexform'], $flexFormConf);
        if (!empty($flexFormConf)) {
            $conf = Helper::mergeRecursiveWithOverrule($flexFormConf, $conf);
        }
        // Read plugin TS configuration.
        $pluginConf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_dlf_'.strtolower(Helper::getUnqualifiedClassName(get_class($this))).'.'];
        if (is_array($pluginConf)) {
            $conf = Helper::mergeRecursiveWithOverrule($pluginConf, $conf);
        }
        // Read general TS configuration.
        $generalConf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.'];
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
        $this->pi_loadLL('EXT:'.$this->extKey.'/Resources/Private/Language/'.Helper::getUnqualifiedClassName(get_class($this)).'.xml');
    }

    /**
     * Loads the current document into $this->doc
     *
     * @access protected
     *
     * @return void
     */
    protected function loadDocument() {
        // Check for required variable.
        if (!empty($this->piVars['id'])
            && !empty($this->conf['pages'])) {
            // Should we exclude documents from other pages than $this->conf['pages']?
            $pid = (!empty($this->conf['excludeOther']) ? intval($this->conf['pages']) : 0);
            // Get instance of \Kitodo\Dlf\Common\Document.
            $this->doc = Document::getInstance($this->piVars['id'], $pid);
            if (!$this->doc->ready) {
                // Destroy the incomplete object.
                $this->doc = NULL;
                Helper::devLog('Failed to load document with UID '.$this->piVars['id'], DEVLOG_SEVERITY_ERROR);
            } else {
                // Set configuration PID.
                $this->doc->cPid = $this->conf['pages'];
            }
        } elseif (!empty($this->piVars['recordId'])) {
            // Get UID of document with given record identifier.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_documents.uid',
                'tx_dlf_documents',
                'tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['recordId'], 'tx_dlf_documents')
                    .Helper::whereClause('tx_dlf_documents'),
                '',
                '',
                '1'
            );
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 1) {
                list ($this->piVars['id']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);
                // Set superglobal $_GET array and unset variables to avoid infinite looping.
                $_GET[$this->prefixId]['id'] = $this->piVars['id'];
                unset ($this->piVars['recordId'], $_GET[$this->prefixId]['recordId']);
                // Try to load document.
                $this->loadDocument();
            } else {
                Helper::devLog('Failed to load document with record ID "'.$this->piVars['recordId'].'"', DEVLOG_SEVERITY_ERROR);
            }
        } else {
            Helper::devLog('Invalid UID '.$this->piVars['id'].' or PID '.$this->conf['pages'].' for document loading', DEVLOG_SEVERITY_ERROR);
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
     * Wraps the input string in a tag with the class attribute set to the class name
     *
     * @access public
     *
     * @param string $content: HTML content to wrap in the div-tags with the class of the plugin
     *
     * @return string HTML content wrapped, ready to return to the parent object.
     */
    public function pi_wrapInBaseClass($content) {
        if (!$GLOBALS['TSFE']->config['config']['disableWrapInBaseClass']) {
            // Use class name instead of $this->prefixId for content wrapping because $this->prefixId is the same for all plugins.
            $content = '<div class="tx-dlf-'.Helper::getUnqualifiedClassName(get_class($this)).'">'.$content.'</div>';
            if (!$GLOBALS['TSFE']->config['config']['disablePrefixComment']) {
                $content = "\n\n<!-- BEGIN: Content of extension '".$this->extKey."', plugin '".Helper::getUnqualifiedClassName(get_class($this))."' -->\n\n".$content."\n\n<!-- END: Content of extension '".$this->extKey."', plugin '".Helper::getUnqualifiedClassName(get_class($this))."' -->\n\n";
            }
        }
        return $content;
    }

    /**
     * Parses a string into a Typoscript array
     *
     * @access protected
     *
     * @param string $string: The string to parse
     *
     * @return array The resulting typoscript array
     */
    protected function parseTS($string = '') {
        $parser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $parser->parse($string);
        return $parser->setup;
    }

    /**
     * Sets some configuration variables if the plugin is cached.
     *
     * @access protected
     *
     * @param boolean $cache: Should the plugin be cached?
     *
     * @return void
     */
    protected function setCache($cache = TRUE) {
        if ($cache) {
            // Set cObject type to "USER" (default).
            $this->pi_USER_INT_obj = FALSE;
            $this->pi_checkCHash = TRUE;
            if (count($this->piVars)) {
                // Check cHash or disable caching.
                $GLOBALS['TSFE']->reqCHash();
            }
        } else {
            // Set cObject type to "USER_INT".
            $this->pi_USER_INT_obj = TRUE;
            $this->pi_checkCHash = FALSE;
            // Plugins are of type "USER" by default, so convert it to "USER_INT".
            $this->cObj->convertToUserIntObject();
        }
    }
}
