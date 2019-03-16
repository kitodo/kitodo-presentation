<?php
namespace Kitodo\Dlf\Module;

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

/**
 * Module 'New Tenant' for the 'dlf' extension.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class NewTenant extends \Kitodo\Dlf\Common\AbstractModule {
    protected $markerArray = [
        'CSH' => '',
        'MOD_MENU' => '',
        'CONTENT' => '',
    ];

    /**
     * Add access rights
     *
     * @access protected
     *
     * @return void
     */
    protected function cmdAddAccessRights() {
        // Get command line indexer's usergroup.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid,db_mountpoints',
            'be_groups',
            'title='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups')
                .' AND '.$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'].'=0'
                .\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_groups')
        );
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
            $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
            // Add current page to mountpoints.
            if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($resArray['db_mountpoints'], $this->id)) {
                $data['be_groups'][$resArray['uid']]['db_mountpoints'] = $resArray['db_mountpoints'].','.$this->id;
                Helper::processDBasAdmin($data);
                // Fine.
                Helper::addMessage(
                    Helper::getMessage('flash.usergroupAddedMsg'),
                    Helper::getMessage('flash.usergroupAdded', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                );
            }
        }
    }

    /**
     * Add metadata configuration
     *
     * @access protected
     *
     * @return void
     */
    protected function cmdAddMetadata() {
        // Include metadata definition file.
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->extKey).'Resources/Private/Data/MetadataDefaults.php');
        $i = 0;
        // Build data array.
        foreach ($metadataDefaults as $index_name => $values) {
            $formatIds = [];
            foreach ($values['format'] as $format) {
                $formatIds[] = uniqid('NEW');
                $data['tx_dlf_metadataformat'][end($formatIds)] = $format;
                $data['tx_dlf_metadataformat'][end($formatIds)]['pid'] = intval($this->id);
                $i++;
            }
            $data['tx_dlf_metadata'][uniqid('NEW')] = [
                'pid' => intval($this->id),
                'label' => $GLOBALS['LANG']->getLL($index_name),
                'index_name' => $index_name,
                'format' => implode(',', $formatIds),
                'default_value' => $values['default_value'],
                'wrap' => (!empty($values['wrap']) ? $values['wrap'] : $GLOBALS['TCA']['tx_dlf_metadata']['columns']['wrap']['config']['default']),
                'index_tokenized' => $values['index_tokenized'],
                'index_stored' => $values['index_stored'],
                'index_indexed' => $values['index_indexed'],
                'index_boost' => $values['index_boost'],
                'is_sortable' => $values['is_sortable'],
                'is_facet' => $values['is_facet'],
                'is_listed' => $values['is_listed'],
                'index_autocomplete' => $values['index_autocomplete'],
            ];
            $i++;
        }
        $_ids = Helper::processDBasAdmin($data);
        // Check for failed inserts.
        if (count($_ids) == $i) {
            // Fine.
            Helper::addMessage(
                Helper::getMessage('flash.metadataAddedMsg'),
                Helper::getMessage('flash.metadataAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            Helper::addMessage(
                Helper::getMessage('flash.metadataNotAddedMsg'),
                Helper::getMessage('flash.metadataNotAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }
    }

    /**
     * Add Solr core
     *
     * @access protected
     *
     * @return void
     */
    protected function cmdAddSolrCore() {
        // Build data array.
        $data['tx_dlf_solrcores'][uniqid('NEW')] = [
            'pid' => intval($this->id),
            'label' => $GLOBALS['LANG']->getLL('solrcore').' (PID '.$this->id.')',
            'index_name' => '',
        ];
        $_ids = Helper::processDBasAdmin($data);
        // Check for failed inserts.
        if (count($_ids) == 1) {
            // Fine.
            Helper::addMessage(
                Helper::getMessage('flash.solrcoreAddedMsg'),
                Helper::getMessage('flash.solrcoreAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            Helper::addMessage(
                Helper::getMessage('flash.solrcoreNotAddedMsg'),
                Helper::getMessage('flash.solrcoreNotAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }
    }

    /**
     * Add structure configuration
     *
     * @access protected
     *
     * @return void
     */
    protected function cmdAddStructure() {
        // Include structure definition file.
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->extKey).'Resources/Private/Data/StructureDefaults.php');
        // Build data array.
        foreach ($structureDefaults as $index_name => $values) {
            $data['tx_dlf_structures'][uniqid('NEW')] = [
                'pid' => intval($this->id),
                'toplevel' => $values['toplevel'],
                'label' => $GLOBALS['LANG']->getLL($index_name),
                'index_name' => $index_name,
                'oai_name' => $values['oai_name'],
                'thumbnail' => 0,
            ];
        }
        $_ids = Helper::processDBasAdmin($data);
        // Check for failed inserts.
        if (count($_ids) == count($structureDefaults)) {
            // Fine.
            Helper::addMessage(
                Helper::getMessage('flash.structureAddedMsg'),
                Helper::getMessage('flash.structureAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            Helper::addMessage(
                Helper::getMessage('flash.structureNotAddedMsg'),
                Helper::getMessage('flash.structureNotAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }
    }

    /**
     * Main function of the module
     *
     * @access public
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request: The request object
     * @param \Psr\Http\Message\ResponseInterface $response: The response object
     *
     * @return \Psr\Http\Message\ResponseInterface The response object
     */
    public function main(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) {
        $this->response = $response;
        // Initialize module.
        $this->MCONF = [
            'name' => 'tools_dlfNewTenantModule',
            'access' => 'admin'
        ];
        $GLOBALS['BE_USER']->modAccess($this->MCONF, 1);
        parent::init();
        $this->pageInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
        // Is the user allowed to access this page?
        $access = is_array($this->pageInfo) && $GLOBALS['BE_USER']->isAdmin();
        if ($this->id && $access) {
            // Check if page is sysfolder.
            if ($this->pageInfo['doktype'] != 254) {
                Helper::addMessage(
                    Helper::getMessage('flash.wrongPageTypeMsg'),
                    Helper::getMessage('flash.wrongPageType', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
                $this->markerArray['CONTENT'] .= Helper::renderFlashMessages();
                $this->printContent();
                return $this->response;
            }
            // Should we do something?
            if (!empty($this->CMD)) {
                // Sanitize input...
                $_method = 'cmd'.ucfirst($this->CMD);
                // ...and unset to prevent infinite looping.
                unset ($this->CMD);
                if (method_exists($this, $_method)) {
                    $this->$_method();
                }
            }
            // Check for existing structure configuration.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid',
                'tx_dlf_structures',
                'pid='.intval($this->id)
                    .Helper::whereClause('tx_dlf_structures')
            );
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
                // Fine.
                Helper::addMessage(
                    Helper::getMessage('flash.structureOkayMsg'),
                    Helper::getMessage('flash.structureOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                );
            } else {
                // Configuration missing.
                $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addStructure']));
                Helper::addMessage(
                    sprintf(Helper::getMessage('flash.structureNotOkayMsg'), $_url),
                    Helper::getMessage('flash.structureNotOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }
            // Check for existing metadata configuration.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid',
                'tx_dlf_metadata',
                'pid='.intval($this->id)
                    .Helper::whereClause('tx_dlf_metadata')
            );
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
                // Fine.
                Helper::addMessage(
                    Helper::getMessage('flash.metadataOkayMsg'),
                    Helper::getMessage('flash.metadataOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                );
            } else {
                // Configuration missing.
                $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addMetadata']));
                Helper::addMessage(
                    sprintf(Helper::getMessage('flash.metadataNotOkayMsg'), $_url),
                    Helper::getMessage('flash.metadataNotOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }
            // Check the access conditions for the command line indexer's user.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid,db_mountpoints',
                'be_groups',
                'title='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups')
                    .' AND '.$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'].'=0'
                    .\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_groups')
            );
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
                $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
                if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($resArray['db_mountpoints'], $this->id)) {
                    // Fine.
                    Helper::addMessage(
                        Helper::getMessage('flash.usergroupOkayMsg'),
                        Helper::getMessage('flash.usergroupOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );
                } else {
                    // Configuration missing.
                    $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addAccessRights']));
                    Helper::addMessage(
                        sprintf(Helper::getMessage('flash.usergroupNotOkayMsg'), $_url),
                        Helper::getMessage('flash.usergroupNotOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                    );
                }
            } else {
                // Usergoup missing.
                Helper::addMessage(
                    Helper::getMessage('flash.usergroupMissingMsg'),
                    Helper::getMessage('flash.usergroupMissing', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }
            // Check for existing Solr core.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid,pid',
                'tx_dlf_solrcores',
                'pid IN ('.intval($this->id).',0)'
                    .Helper::whereClause('tx_dlf_solrcores')
            );
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
                $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
                if ($resArray['pid']) {
                    // Fine.
                    Helper::addMessage(
                        Helper::getMessage('flash.solrcoreOkayMsg'),
                        Helper::getMessage('flash.solrcoreOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );
                } else {
                    // Default core available, but this is deprecated.
                    $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addSolrcore']));
                    Helper::addMessage(
                        sprintf(Helper::getMessage('flash.solrcoreDeprecatedMsg'), $_url),
                        Helper::getMessage('flash.solrcoreDeprecatedOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE
                    );
                }
            } else {
                // Solr core missing.
                $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addSolrcore']));
                Helper::addMessage(
                    sprintf(Helper::getMessage('flash.solrcoreMissingMsg'), $_url),
                    Helper::getMessage('flash.solrcoreMissing', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                );
            }
            $this->markerArray['CONTENT'] .= Helper::renderFlashMessages();
        } else {
            // TODO: Ändern!
            $this->markerArray['CONTENT'] .= 'You are not allowed to access this page or have not selected a page, yet.';
        }
        $this->printContent();
        return $this->response;
    }
}
