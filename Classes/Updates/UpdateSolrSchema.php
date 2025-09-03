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

namespace Kitodo\Dlf\Updates;

use Kitodo\Dlf\Common\Solr\Solr;
use Solarium\Core\Client\Request;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Class UpdateSolrSchema
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @internal
 */
#[UpgradeWizard('updateSolrSchema')]
class UpdateSolrSchema implements UpgradeWizardInterface
{
    /**
     * Return the speaking name of this wizard
     *
     * @access public
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Update Solr schema';
    }

    /**
     * Return the description for this wizard
     *
     * @access public
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This wizard updates the schema of all available Solr cores';
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @access public
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $affectedSolrCores = $this->getAllAffectedSolrCores();

        foreach ($affectedSolrCores as $affectedSolrCore) {

            $solr = Solr::getInstance($affectedSolrCore['uid']);
            if (!$solr->ready) {
                continue;
            }

            $query = $solr->service->createApi(
                [
                    'version' => Request::API_V1,
                    'handler' => $affectedSolrCore['index_name'].'/schema',
                    'method' => Request::METHOD_POST,
                    'rawdata' => json_encode(
                        [
                            'replace-field' => [
                                'name' => 'autocomplete',
                                'type' => 'autocomplete',
                                'indexed' => true,
                                'stored' => true,
                                'multiValued' => true,
                            ],
                        ]
                    ),
                ]
            );
            $result = $solr->service->execute($query);

            if ($result->getResponse()->getStatusCode() == 400) {
                return false;
            }
        }
        return true;
    }

    /**
     * Is an update necessary?
     *
     * Looks for all affected Solr cores
     *
     * @access public
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        if (count($this->getAllAffectedSolrCores())) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @access public
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Returns all affected Solr cores
     *
     * @access private
     *
     * @return array
     */
    private function getAllAffectedSolrCores(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_solrcores');

        $allSolrCores = $queryBuilder->select('uid', 'index_name')
            ->from('tx_dlf_solrcores')
            ->executeQuery()
            ->fetchAllAssociative();

        $affectedSolrCores = [];

        foreach ($allSolrCores as $solrCore) {
            $solr = Solr::getInstance($solrCore['uid']);
            if (!$solr->ready) {
                continue;
            }

            $query = $solr->service->createApi(
                [
                    'version' => Request::API_V1,
                    'handler' => $solrCore['index_name'].'/config/schemaFactory',
                    'method' => Request::METHOD_GET
                ]
            );
            $result = $solr->service->execute($query)->getData();

            if (!isset($result['config']['schemaFactory']['class']) || $result['config']['schemaFactory']['class'] != 'ManagedIndexSchemaFactory') {
                continue;
            }

            $query = $solr->service->createApi(
                [
                    'version' => Request::API_V1,
                    'handler' => $solrCore['index_name'].'/schema/fields/autocomplete',
                    'method' => Request::METHOD_GET
                ]
            );
            $result = $solr->service->execute($query)->getData();

            if (!isset($result['field']['stored']) || $result['field']['stored'] === true) {
                continue;
            }

            $affectedSolrCores[] = $solrCore;
        }
        return $affectedSolrCores;
    }
}
