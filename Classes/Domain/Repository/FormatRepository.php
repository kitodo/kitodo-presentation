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

namespace Kitodo\Dlf\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Format repository class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @abstract
 */
class FormatRepository extends Repository
{

    //TODO: replace all static methods after real repository is implemented
    /**
     * Load all available data formats
     *
     * @access public
     *
     * @return array
     */
    public static function loadFormats() : array
    {
        $formats = [];
        
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_dlf_domain_model_format');

        // Get available data formats from database.
        $result = $queryBuilder
            ->select(
                'tx_dlf_domain_model_format.type AS type',
                'tx_dlf_domain_model_format.root AS root',
                'tx_dlf_domain_model_format.namespace AS namespace',
                'tx_dlf_domain_model_format.class AS class'
            )
            ->from('tx_dlf_domain_model_format')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_domain_model_format.pid', 0)
            )
            ->execute();

        while ($resArray = $result->fetch()) {
            // Update format registry.
            $formats[$resArray['type']] = [
                'rootElement' => $resArray['root'],
                'namespaceURI' => $resArray['namespace'],
                'class' => $resArray['class']
            ];
        }
        return $formats;
    }
}
