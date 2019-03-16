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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;

/**
 * Update class 'ext_update' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ext_update {
    /**
     * This holds the output ready to return
     *
     * @var string
     * @access protected
     */
    protected $content = '';

    /**
     * Triggers the update option in the extension manager
     *
     * @access public
     *
     * @return boolean Should the update option be shown?
     */
    public function access() {
        if (count($this->getMetadataConfig())) {
            return TRUE;
        } elseif ($this->oldIndexRelatedTableNames()) {
            return TRUE;
        } elseif ($this->solariumSolrUpdateRequired()) {
            return TRUE;
        } elseif (count($this->oldFormatClasses())) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Get all outdated metadata configuration records
     *
     * @access protected
     *
     * @return array Array of UIDs of outdated records
     */
    protected function getMetadataConfig() {
        $uids = [];
        // check if tx_dlf_metadata.xpath exists anyhow
        $fieldsInDatabase = $GLOBALS['TYPO3_DB']->admin_get_fields('tx_dlf_metadata');
        if (!in_array('xpath', array_keys($fieldsInDatabase))) {
            return $uids;
        }
        // Get all records with outdated configuration.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_metadata.uid AS uid',
            'tx_dlf_metadata',
            'tx_dlf_metadata.format=0'
                .' AND NOT tx_dlf_metadata.xpath=""'
                .Helper::whereClause('tx_dlf_metadata'),
            '',
            '',
            ''
        );
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                $uids[] = intval($resArray['uid']);
            }
        }
        return $uids;
    }

    /**
     * The main method of the class
     *
     * @access public
     *
     * @return string The content that is displayed on the website
     */
    public function main() {
        // Load localization file.
        $GLOBALS['LANG']->includeLLFile('EXT:dlf/Resources/Private/Language/FlashMessages.xml');
        // Update the metadata configuration.
        if (count($this->getMetadataConfig())) {
            $this->updateMetadataConfig();
        }
        if ($this->oldIndexRelatedTableNames()) {
            $this->renameIndexRelatedColumns();
        }
        if ($this->solariumSolrUpdateRequired()) {
            $this->doSolariumSolrUpdate();
        }
        if (count($this->oldFormatClasses())) {
            $this->updateFormatClasses();
        }
        return $this->content;
    }

    /**
     * Check for old format classes
     *
     * @access protected
     *
     * @return boolean true if old format classes exist
     */
    protected function oldFormatClasses() {
        $oldRecords = [];
        // Get all records with outdated configuration.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_formats.uid AS uid,tx_dlf_formats.type AS type',
            'tx_dlf_formats',
            'tx_dlf_formats.class NOT LIKE "%\\%"'
                .Helper::whereClause('tx_dlf_formats'),
            '',
            '',
            ''
        );
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $oldRecords[$resArray['uid']] = $resArray['type'];
        }
        return $oldRecords;
    }

    /**
     * Check for old index related colums
     *
     * @access protected
     *
     * @return boolean TRUE if old index related columns exist
     */
    protected function oldIndexRelatedTableNames() {
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'column_name',
            'INFORMATION_SCHEMA.COLUMNS',
            'TABLE_NAME = "tx_dlf_metadata"',
            '',
            '',
            ''
        );
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            if ($resArray['column_name'] == 'tokenized'
                || $resArray['column_name'] == 'stored'
                || $resArray['column_name'] == 'indexed'
                || $resArray['column_name'] == 'boost'
                || $resArray['column_name'] == 'autocomplete') {
                    return TRUE;
            }
        }
    }

    /**
     * Copy the data of the old index related columns to the new columns
     *
     * @access protected
     *
     * @return void
     */
    protected function renameIndexRelatedColumns() {
        $sqlQuery = 'UPDATE tx_dlf_metadata'
            .' SET `index_tokenized` = `tokenized`'
            .', `index_stored` = `stored`'
            .', `index_indexed` = `indexed`'
            .', `index_boost` = `boost`'
            .', `index_autocomplete` = `autocomplete`';
        // Copy the content of the old tables to the new ones
        $result = $GLOBALS['TYPO3_DB']->sql_query($sqlQuery);
        if ($result) {
            $messages = Helper::addMessage(
                $GLOBALS['LANG']->getLL('update.copyIndexRelatedColumnsOkay', TRUE),
                $GLOBALS['LANG']->getLL('update.copyIndexRelatedColumns', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            $messages = Helper::addMessage(
                $GLOBALS['LANG']->getLL('update.copyIndexRelatedColumnsNotOkay', TRUE),
                $GLOBALS['LANG']->getLL('update.copyIndexRelatedColumns', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
        }
        $this->content .= $messages->renderFlashMessages();
    }

    /**
     * Update all outdated format records
     *
     * @access protected
     *
     * @return void
     */
    protected function updateFormatClasses() {
        $oldRecords = $this->oldFormatClasses();
        $newValues = [
            'ALTO' => 'Kitodo\\Dlf\\Formats\\Alto',
            'MODS' => 'Kitodo\\Dlf\\Formats\\Mods',
            'TEIHDR' => 'Kitodo\\Dlf\\Formats\\TeiHeader'
        ];
        foreach ($oldRecords as $uid => $type) {
            $sqlQuery = 'UPDATE tx_dlf_formats SET class="'.$newValues[$type].'" WHERE uid='.$uid;
            $GLOBALS['TYPO3_DB']->sql_query($sqlQuery);
        }
        $messages = Helper::addMessage(
            $GLOBALS['LANG']->getLL('update.FormatClassesOkay', TRUE),
            $GLOBALS['LANG']->getLL('update.FormatClasses', TRUE),
            \TYPO3\CMS\Core\Messaging\FlashMessage::OK
        );
        $this->content .= $messages->renderFlashMessages();
    }

    /**
     * Update all outdated metadata configuration records
     *
     * @access protected
     *
     * @return void
     */
    protected function updateMetadataConfig() {
        $metadataUids = $this->getMetadataConfig();
        if (!empty($metadataUids)) {
            $data = [];
            // Get all old metadata configuration records.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_metadata.uid AS uid,tx_dlf_metadata.pid AS pid,tx_dlf_metadata.cruser_id AS cruser_id,tx_dlf_metadata.encoded AS encoded,tx_dlf_metadata.xpath AS xpath,tx_dlf_metadata.xpath_sorting AS xpath_sorting',
                'tx_dlf_metadata',
                'tx_dlf_metadata.uid IN ('.implode(',', $metadataUids).')'
                    .Helper::whereClause('tx_dlf_metadata'),
                '',
                '',
                ''
            );
            while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                $newId = uniqid('NEW');
                // Copy record to new table.
                $data['tx_dlf_metadataformat'][$newId] = [
                    'pid' => $resArray['pid'],
                    'cruser_id' => $resArray['cruser_id'],
                    'parent_id' => $resArray['uid'],
                    'encoded' => $resArray['encoded'],
                    'xpath' => $resArray['xpath'],
                    'xpath_sorting' => $resArray['xpath_sorting']
                ];
                // Add reference to old table.
                $data['tx_dlf_metadata'][$resArray['uid']]['format'] = $newId;
            }
            if (!empty($data)) {
                // Process datamap.
                $substUids = Helper::processDBasAdmin($data);
                unset ($data);
                if (!empty($substUids)) {
                    $messages = Helper::addMessage(
                        $GLOBALS['LANG']->getLL('update.metadataConfigOkay', TRUE),
                        $GLOBALS['LANG']->getLL('update.metadataConfig', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );
                } else {
                    $messages = Helper::addMessage(
                        $GLOBALS['LANG']->getLL('update.metadataConfigNotOkay', TRUE),
                        $GLOBALS['LANG']->getLL('update.metadataConfig', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                    );
                }
                $this->content .= $messages->renderFlashMessages();
            }
        }
    }

    /**
     * Check all configured Solr cores
     *
     * @access protected
     *
     * @return boolean
     */
    protected function solariumSolrUpdateRequired() {
        // Get all Solr cores that were not deleted.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'index_name',
            'tx_dlf_solrcores',
            'deleted=0',
            '',
            '',
            ''
        );
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            // Instantiate search object.
            $solr = Solr::getInstance($resArray['index_name']);
            if (!$solr->ready) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Create all configured Solr cores
     *
     * @access protected
     *
     * @return void
     */
    protected function doSolariumSolrUpdate() {
        // Get all Solr cores that were not deleted.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'index_name',
            'tx_dlf_solrcores',
            'deleted=0',
            '',
            '',
            ''
        );
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            // Instantiate search object.
            $solr = Solr::getInstance($resArray['index_name']);
            if (!$solr->ready) {
                $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);
                $solrInfo = Solr::getSolrConnectionInfo();
                // Prepend username and password to hostname.
                if ($solrInfo['username']
                    && $solrInfo['password']) {
                    $host = $solrInfo['username'].':'.$solrInfo['password'].'@'.$solrInfo['host'];
                } else {
                    $host = $solrInfo['host'];
                }
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'user_agent' => ($conf['useragent'] ? $conf['useragent'] : ini_get('user_agent'))
                    ]
                ]);
                // Build request for adding new Solr core.
                // @see http://wiki.apache.org/solr/CoreAdmin
                $url = $solrInfo['scheme'].'://'.$host.':'.$solrInfo['port'].'/'.$solrInfo['path'].'/admin/cores?wt=xml&action=CREATE&name='.$resArray['index_name'].'&instanceDir=dlfCore'.$resArray['index_name'].'&dataDir=data&configSet=dlf';
                $response = @simplexml_load_string(file_get_contents($url, FALSE, $context));
                // Process response.
                if ($response) {
                    $status = $response->xpath('//lst[@name="responseHeader"]/int[@name="status"]');
                    if ($status
                        && $status[0] == 0) {
                        continue;
                    }
                }
                $messages = Helper::addMessage(
                    $GLOBALS['LANG']->getLL('update.solariumSolrUpdateNotOkay', TRUE),
                    sprintf($GLOBALS['LANG']->getLL('update.solariumSolrUpdate', TRUE), $resArray['index_name']),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
                $this->content .= $messages->renderFlashMessages();
                return;
            }
        }
        $messages = Helper::addMessage(
            $GLOBALS['LANG']->getLL('update.solariumSolrUpdateOkay', TRUE),
            $GLOBALS['LANG']->getLL('update.solariumSolrUpdate', TRUE),
            \TYPO3\CMS\Core\Messaging\FlashMessage::OK
        );
        $this->content .= $messages->renderFlashMessages();
    }
}
