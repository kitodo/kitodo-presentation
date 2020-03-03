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

namespace Kitodo\Dlf\Module;

use Kitodo\Dlf\Common\Helper;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Module 'New Tenant' for the 'dlf' extension.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class NewTenant extends \Kitodo\Dlf\Common\AbstractModule
{
    protected $markerArray = [
        'CSH' => '',
        'MOD_MENU' => '',
        'CONTENT' => '',
    ];

    /**
     * Add metadata configuration
     *
     * @access protected
     *
     * @return void
     */
    protected function cmdAddMetadata()
    {
        // Include metadata definition file.
        $metadataDefaults = include (ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Private/Data/MetadataDefaults.php');
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
                'label' => $GLOBALS['LANG']->getLL('metadata.' . $index_name),
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
        $_ids = Helper::processDBasAdmin($data, [], true);
        // Check for failed inserts.
        if (count($_ids) == $i) {
            // Fine.
            Helper::addMessage(
                Helper::getMessage('flash.metadataAddedMsg'),
                Helper::getMessage('flash.metadataAdded', true),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            Helper::addMessage(
                Helper::getMessage('flash.metadataNotAddedMsg'),
                Helper::getMessage('flash.metadataNotAdded', true),
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
    protected function cmdAddSolrCore()
    {
        // Build data array.
        $data['tx_dlf_solrcores'][uniqid('NEW')] = [
            'pid' => intval($this->id),
            'label' => $GLOBALS['LANG']->getLL('solrcore') . ' (PID ' . $this->id . ')',
            'index_name' => '',
        ];
        $_ids = Helper::processDBasAdmin($data);
        // Check for failed inserts.
        if (count($_ids) == 1) {
            // Fine.
            Helper::addMessage(
                Helper::getMessage('flash.solrcoreAddedMsg'),
                Helper::getMessage('flash.solrcoreAdded', true),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            Helper::addMessage(
                Helper::getMessage('flash.solrcoreNotAddedMsg'),
                Helper::getMessage('flash.solrcoreNotAdded', true),
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
    protected function cmdAddStructure()
    {
        // Include structure definition file.
        $structureDefaults = include (ExtensionManagementUtility::extPath($this->extKey) . 'Resources/Private/Data/StructureDefaults.php');
        // Build data array.
        foreach ($structureDefaults as $index_name => $values) {
            $data['tx_dlf_structures'][uniqid('NEW')] = [
                'pid' => intval($this->id),
                'toplevel' => $values['toplevel'],
                'label' => $GLOBALS['LANG']->getLL('structure.' . $index_name),
                'index_name' => $index_name,
                'oai_name' => $values['oai_name'],
                'thumbnail' => 0,
            ];
        }
        $_ids = Helper::processDBasAdmin($data, [], true);
        // Check for failed inserts.
        if (count($_ids) == count($structureDefaults)) {
            // Fine.
            Helper::addMessage(
                Helper::getMessage('flash.structureAddedMsg'),
                Helper::getMessage('flash.structureAdded', true),
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK
            );
        } else {
            // Something went wrong.
            Helper::addMessage(
                Helper::getMessage('flash.structureNotAddedMsg'),
                Helper::getMessage('flash.structureNotAdded', true),
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
     *
     * @return ResponseInterface The response object
     */
    public function main(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

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
                    Helper::getMessage('flash.wrongPageType', true),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
                $this->markerArray['CONTENT'] .= Helper::renderFlashMessages();
                $response->getBody()->write($this->printContent());
                return $response;
            }
            // Should we do something?
            if (!empty($this->CMD)) {
                // Sanitize input...
                $_method = 'cmd' . ucfirst($this->CMD);
                // ...and unset to prevent infinite looping.
                unset($this->CMD);
                if (method_exists($this, $_method)) {
                    $this->$_method();
                }
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_structures');

            // Check for existing structure configuration.
            $result = $queryBuilder
                ->select('tx_dlf_structures.uid AS uid')
                ->from('tx_dlf_structures')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_structures.pid', intval($this->id)),
                    Helper::whereExpression('tx_dlf_structures')
                )
                ->execute();

            if ($result->rowCount() > 0) {
                // Fine.
                Helper::addMessage(
                    Helper::getMessage('flash.structureOkayMsg'),
                    Helper::getMessage('flash.structureOkay', true),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                );
            } else {
                // Configuration missing.
                $url = GeneralUtility::locationHeaderUrl(GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addStructure']));
                Helper::addMessage(
                    sprintf(Helper::getMessage('flash.structureNotOkayMsg'), $url),
                    Helper::getMessage('flash.structureNotOkay', true),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_metadata');

            // Check for existing metadata configuration.
            $result = $queryBuilder
                ->select('tx_dlf_metadata.uid AS uid')
                ->from('tx_dlf_metadata')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($this->id)),
                    Helper::whereExpression('tx_dlf_metadata')
                )
                ->execute();

            if ($result->rowCount() > 0) {
                // Fine.
                Helper::addMessage(
                    Helper::getMessage('flash.metadataOkayMsg'),
                    Helper::getMessage('flash.metadataOkay', true),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                );
            } else {
                // Configuration missing.
                $url = GeneralUtility::locationHeaderUrl(GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addMetadata']));
                Helper::addMessage(
                    sprintf(Helper::getMessage('flash.metadataNotOkayMsg'), $url),
                    Helper::getMessage('flash.metadataNotOkay', true),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }

            // Check for existing Solr core.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_solrcores');

                // Check for existing Solr core.
            $result = $queryBuilder
                ->select(
                    'tx_dlf_solrcores.uid AS uid',
                    'tx_dlf_solrcores.pid AS pid'
                )
                ->from('tx_dlf_solrcores')
                ->where(
                    $queryBuilder->expr()->in('tx_dlf_solrcores.pid', [intval($this->id), 0]),
                    Helper::whereExpression('tx_dlf_solrcores')
                )
                ->execute();

            $allResults = $result->fetchAll();

            if (count($allResults) > 0) {
                $resArray = $allResults[0];
                if ($resArray['pid']) {
                    // Fine.
                    Helper::addMessage(
                        Helper::getMessage('flash.solrcoreOkayMsg'),
                        Helper::getMessage('flash.solrcoreOkay', true),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK
                    );
                } else {
                    // Default core available, but this is deprecated.
                    $url = GeneralUtility::locationHeaderUrl(GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addSolrcore']));
                    Helper::addMessage(
                        sprintf(Helper::getMessage('flash.solrcoreDeprecatedMsg'), $url),
                        Helper::getMessage('flash.solrcoreDeprecatedOkay', true),
                        \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE
                    );
                }
            } else {
                // Solr core missing.
                $url = GeneralUtility::locationHeaderUrl(GeneralUtility::linkThisScript(['id' => $this->id, 'CMD' => 'addSolrcore']));
                Helper::addMessage(
                    sprintf(Helper::getMessage('flash.solrcoreMissingMsg'), $url),
                    Helper::getMessage('flash.solrcoreMissing', true),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                );
            }
            $this->markerArray['CONTENT'] .= Helper::renderFlashMessages();
        } else {
            // TODO: Ändern!
            $this->markerArray['CONTENT'] .= 'You are not allowed to access this page or have not selected a page, yet.';
        }
        $response->getBody()->write($this->printContent());
        return $response;
    }
}
