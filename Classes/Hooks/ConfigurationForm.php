<?php
namespace Kitodo\Dlf\Hooks;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;

/**
 * Hooks and helper for \TYPO3\CMS\Core\TypoScript\ConfigurationForm
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ConfigurationForm {
    /**
     * This holds the current configuration
     *
     * @var array
     * @access protected
     */
    protected $conf = [];

    /**
     * Check if a connection to a Solr server could be established with the given credentials.
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm &$pObj: The parent object
     *
     * @return string Message informing the user of success or failure
     */
    public function checkSolrConnection(&$params, &$pObj) {
        $solrInfo = Solr::getSolrConnectionInfo();
        // Prepend username and password to hostname.
        if (!empty($solrInfo['username'])
            && !empty($solrInfo['password'])) {
            $host = $solrInfo['username'].':'.$solrInfo['password'].'@'.$solrInfo['host'];
        } else {
            $host = $solrInfo['host'];
        }
        // Build request URI.
        $url = $solrInfo['scheme'].'://'.$host.':'.$solrInfo['port'].'/'.$solrInfo['path'].'/admin/cores?wt=xml';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'user_agent' => (!empty($this->conf['useragent']) ? $this->conf['useragent'] : ini_get('user_agent'))
            ]
        ]);
        // Try to connect to Solr server.
        $response = @simplexml_load_string(file_get_contents($url, FALSE, $context));
        // Check status code.
        if ($response) {
            $status = $response->xpath('//lst[@name="responseHeader"]/int[@name="status"]');
            if (is_array($status)) {
                Helper::addMessage(
                    sprintf($GLOBALS['LANG']->getLL('solr.status'), (string) $status[0]),
                    $GLOBALS['LANG']->getLL('solr.connected'),
                    ($status[0] == 0 ? \TYPO3\CMS\Core\Messaging\FlashMessage::OK : \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING)
                );
                return Helper::renderFlashMessages();
            }
        }
        Helper::addMessage(
            sprintf($GLOBALS['LANG']->getLL('solr.error'), $url),
            $GLOBALS['LANG']->getLL('solr.notConnected'),
            \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
        );
        return Helper::renderFlashMessages();
    }

    /**
     * Make sure a CLI dispatcher is available.
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm &$pObj: The parent object
     *
     * @return string Message informing the user of success or failure
     */
    public function checkCliDispatcher(&$params, &$pObj) {
        // Check if CLI dispatcher is executable.
        if (is_executable(PATH_typo3.'cli_dispatch.phpsh')) {
            Helper::addMessage(
                $GLOBALS['LANG']->getLL('cliDispatcher.cliOkayMsg'),
                $GLOBALS['LANG']->getLL('cliDispatcher.cliOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            Helper::addMessage(
                $GLOBALS['LANG']->getLL('cliDispatcher.cliNotOkayMsg'),
                $GLOBALS['LANG']->getLL('cliDispatcher.cliNotOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }
        return Helper::renderFlashMessages();
    }

    /**
     * Make sure the essential namespaces are defined.
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     * @param \TYPO3\CMS\Core\TypoScript\ConfigurationForm &$pObj: The parent object
     *
     * @return string Message informing the user of success or failure
     */
    public function checkMetadataFormats(&$params, &$pObj) {
        $nsDefined = [
            'MODS' => FALSE,
            'TEIHDR' => FALSE,
            'ALTO' => FALSE,
            'IIIF1' => FALSE,
            'IIIF2' => FALSE,
            'IIIF3' => FALSE
        ];
        // Check existing format specifications.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'type',
            'tx_dlf_formats',
            '1=1'
                .Helper::whereClause('tx_dlf_formats')
        );
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $nsDefined[$resArray['type']] = TRUE;
        }
        // Build data array.
        $data = [];
        // Add MODS namespace.
        if (!$nsDefined['MODS']) {
            $data['tx_dlf_formats'][uniqid('NEW')] = [
                'pid' => 0,
                'type' => 'MODS',
                'root' => 'mods',
                'namespace' => 'http://www.loc.gov/mods/v3',
                'class' => 'Kitodo\\\\Dlf\\\\Format\\\\Mods'
            ];
        }
        // Add TEIHDR namespace.
        if (!$nsDefined['TEIHDR']) {
            $data['tx_dlf_formats'][uniqid('NEW')] = [
                'pid' => 0,
                'type' => 'TEIHDR',
                'root' => 'teiHeader',
                'namespace' => 'http://www.tei-c.org/ns/1.0',
                'class' => 'Kitodo\\\\Dlf\\\\Format\\\\TeiHeader'
            ];
        }
        // Add ALTO namespace.
        if (!$nsDefined['ALTO']) {
            $data['tx_dlf_formats'][uniqid('NEW')] = [
                'pid' => 0,
                'type' => 'ALTO',
                'root' => 'alto',
                'namespace' => 'http://www.loc.gov/standards/alto/ns-v2#',
                'class' => 'Kitodo\\\\Dlf\\\\Format\\\\Alto'
            ];
        }
        // Add IIIF Metadata API 1 context
        if (!$nsDefined['IIIF1']) {
            $data['tx_dlf_formats'][uniqid('NEW')] = array (
                'pid' => 0,
                'type' => 'IIIF1',
                'root' => 'none',
                'namespace' => 'http://www.shared-canvas.org/ns/context.json',
                'class' => 'none'
            );
        }
        // Add IIIF Presentation 2 context
        if (!$nsDefined['IIIF2']) {
            $data['tx_dlf_formats'][uniqid('NEW')] = array (
                'pid' => 0,
                'type' => 'IIIF2',
                'root' => 'none',
                'namespace' => 'http://iiif.io/api/presentation/2/context.json',
                'class' => 'none'
            );
        }
        // Add IIIF Presentation 3 context
        if (!$nsDefined['IIIF3']) {
            $data['tx_dlf_formats'][uniqid('NEW')] = array (
                'pid' => 0,
                'type' => 'IIIF3',
                'root' => 'none',
                'namespace' => 'http://iiif.io/api/presentation/3/context.json',
                'class' => 'none'
            );
        }
        if (!empty($data)) {
            // Process changes.
            $substUid = Helper::processDBasAdmin($data);
            if (!empty($substUid)) {
                Helper::addMessage(
                    $GLOBALS['LANG']->getLL('metadataFormats.nsCreatedMsg'),
                    $GLOBALS['LANG']->getLL('metadataFormats.nsCreated'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
                );
            } else {
                Helper::addMessage(
                    $GLOBALS['LANG']->getLL('metadataFormats.nsNotCreatedMsg'),
                    $GLOBALS['LANG']->getLL('metadataFormats.nsNotCreated'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }
        } else {
            Helper::addMessage(
                $GLOBALS['LANG']->getLL('metadataFormats.nsOkayMsg'),
                $GLOBALS['LANG']->getLL('metadataFormats.nsOkay'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
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
    public function __construct() {
        // Load localization file.
        $GLOBALS['LANG']->includeLLFile('EXT:dlf/Resources/Private/Language/FlashMessages.xml');
        // Get current configuration.
        $this->conf = array_merge((array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']), (array) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('data'));
    }
}
