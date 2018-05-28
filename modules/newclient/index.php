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

/**
 * Module 'newclient' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_modNewclient extends \Kitodo\Dlf\Common\AbstractModule {

    protected $modPath = 'newclient/';

    protected $buttonArray = [
        'SHORTCUT' => '',
    ];

    protected $markerArray = [
        'CSH' => '',
        'MOD_MENU' => '',
        'CONTENT' => '',
    ];

    /**
     * Add access rights
     *
     * @access	protected
     *
     * @return	void
     */
    protected function cmdAddAccessRights() {

        // Get command line indexer's usergroup.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid,db_mountpoints',
                'be_groups',
                'title='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups').' AND '.$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'].'=0'.\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_groups')
        );

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

            $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

            // Add current page to mountpoints.
            if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($resArray['db_mountpoints'], $this->id)) {

                $data['be_groups'][$resArray['uid']]['db_mountpoints'] = $resArray['db_mountpoints'].','.$this->id;

                Helper::processDBasAdmin($data);

                // Fine.
                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    Helper::getLL('flash.usergroupAddedMsg'),
                    Helper::getLL('flash.usergroupAdded', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                    FALSE
                );

                Helper::addMessage($_message);

            }

        }

    }

    /**
     * Add metadata configuration
     *
     * @access	protected
     *
     * @return	void
     */
    protected function cmdAddMetadata() {

        // Include metadata definition file.
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->extKey).'modules/'.$this->modPath.'metadata.inc.php');

        $i = 0;

        // Build data array.
        foreach ($metadata as $index_name => $values) {

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
            $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                Helper::getLL('flash.metadataAddedMsg'),
                Helper::getLL('flash.metadataAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                FALSE
            );

        } else {

            // Something went wrong.
            $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                Helper::getLL('flash.metadataNotAddedMsg'),
                Helper::getLL('flash.metadataNotAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                FALSE
            );

        }

        Helper::addMessage($_message);

    }

    /**
     * Add Solr core
     *
     * @access	protected
     *
     * @return	void
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
            $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                Helper::getLL('flash.solrcoreAddedMsg'),
                Helper::getLL('flash.solrcoreAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                FALSE
            );

        } else {

            // Something went wrong.
            $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                Helper::getLL('flash.solrcoreNotAddedMsg'),
                Helper::getLL('flash.solrcoreNotAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                FALSE
            );

        }

        Helper::addMessage($_message);

    }

    /**
     * Add structure configuration
     *
     * @access	protected
     *
     * @return	void
     */
    protected function cmdAddStructure() {

        // Include structure definition file.
        include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->extKey).'modules/'.$this->modPath.'structures.inc.php');

        // Build data array.
        foreach ($structures as $index_name => $values) {

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
        if (count($_ids) == count($structures)) {

            // Fine.
            $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                Helper::getLL('flash.structureAddedMsg'),
                Helper::getLL('flash.structureAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                FALSE
            );

        } else {

            // Something went wrong.
            $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                Helper::getLL('flash.structureNotAddedMsg'),
                Helper::getLL('flash.structureNotAdded', TRUE),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                FALSE
            );

        }

        Helper::addMessage($_message);

    }

    /**
     * Main function of the module
     *
     * @access	public
     *
     * @return	void
     */
    public function main() {

        // Is the user allowed to access this page?
        $access = is_array($this->pageInfo) && $GLOBALS['BE_USER']->isAdmin();

        if ($this->id && $access) {

            // Check if page is sysfolder.
            if ($this->pageInfo['doktype'] != 254) {

                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    Helper::getLL('flash.wrongPageTypeMsg'),
                    Helper::getLL('flash.wrongPageType', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                    FALSE
                );

                Helper::addMessage($_message);

                $this->markerArray['CONTENT'] .= Helper::renderFlashMessages();

                $this->printContent();

                return;

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
                'pid='.intval($this->id).Helper::whereClause('tx_dlf_structures')
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

                // Fine.
                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    Helper::getLL('flash.structureOkayMsg'),
                    Helper::getLL('flash.structureOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                    FALSE
                );

            } else {

                // Configuration missing.
                $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addStructure']));

                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    sprintf(Helper::getLL('flash.structureNotOkayMsg'), $_url),
                    Helper::getLL('flash.structureNotOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                    FALSE
                );

            }

            Helper::addMessage($_message);

            // Check for existing metadata configuration.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid',
                'tx_dlf_metadata',
                'pid='.intval($this->id).Helper::whereClause('tx_dlf_metadata')
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

                // Fine.
                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    Helper::getLL('flash.metadataOkayMsg'),
                    Helper::getLL('flash.metadataOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                    FALSE
                );

            } else {

                // Configuration missing.
                $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addMetadata']));

                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    sprintf(Helper::getLL('flash.metadataNotOkayMsg'), $_url),
                    Helper::getLL('flash.metadataNotOkay', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                    FALSE
                );

            }

            Helper::addMessage($_message);

            // Check the access conditions for the command line indexer's user.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid,db_mountpoints',
                'be_groups',
                'title='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_cli_dlf', 'be_groups').' AND '.$GLOBALS['TCA']['be_groups']['ctrl']['enablecolumns']['disabled'].'=0'.\TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_groups')
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

                $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

                if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($resArray['db_mountpoints'], $this->id)) {

                    // Fine.
                    $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        Helper::getLL('flash.usergroupOkayMsg'),
                        Helper::getLL('flash.usergroupOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                        FALSE
                    );

                } else {

                    // Configuration missing.
                    $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addAccessRights']));

                    $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        sprintf(Helper::getLL('flash.usergroupNotOkayMsg'), $_url),
                        Helper::getLL('flash.usergroupNotOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                        FALSE
                    );

                }

            } else {

                // Usergoup missing.
                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    Helper::getLL('flash.usergroupMissingMsg'),
                    Helper::getLL('flash.usergroupMissing', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                    FALSE
                );

            }

            Helper::addMessage($_message);

            // Check for existing Solr core.
            $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid,pid',
                'tx_dlf_solrcores',
                'pid IN ('.intval($this->id).',0)'.Helper::whereClause('tx_dlf_solrcores')
            );

            if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

                $resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

                if ($resArray['pid']) {

                    // Fine.
                    $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        Helper::getLL('flash.solrcoreOkayMsg'),
                        Helper::getLL('flash.solrcoreOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                        FALSE
                    );

                } else {

                    // Default core available, but this is deprecated.
                    $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addSolrcore']));

                    $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                        sprintf(Helper::getLL('flash.solrcoreDeprecatedMsg'), $_url),
                        Helper::getLL('flash.solrcoreDeprecatedOkay', TRUE),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE,
                        FALSE
                    );

                }

            } else {

                // Solr core missing.
                $_url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addSolrcore']));

                $_message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    sprintf(Helper::getLL('flash.solrcoreMissingMsg'), $_url),
                    Helper::getLL('flash.solrcoreMissing', TRUE),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
                    FALSE
                );

            }

            Helper::addMessage($_message);

            $this->markerArray['CONTENT'] .= Helper::renderFlashMessages();

        } else {

            // TODO: Ändern!
            $this->markerArray['CONTENT'] .= 'You are not allowed to access this page or have not selected a page, yet.';

        }

        $this->printContent();

    }

}

$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_dlf_modNewclient');

$SOBE->main();
