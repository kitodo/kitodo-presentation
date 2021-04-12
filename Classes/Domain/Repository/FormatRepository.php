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

use Kitodo\Dlf\Domain\Table;
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

    public static function findAll() {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$format);

        // Check existing format specifications.
        $result = $queryBuilder
            ->select(Table::$format . '.type AS type')
            ->from(Table::$format)
            ->where(
                '1=1'
            )
            ->execute();

        return $result;
    }

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
        
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Table::$format);

        // Get available data formats from database.
        $result = $queryBuilder
            ->select(
                Table::$format . '.type AS type',
                Table::$format . '.root AS root',
                Table::$format . '.namespace AS namespace',
                Table::$format . '.class AS class'
            )
            ->from(Table::$format)
            ->where(
                $queryBuilder->expr()->eq(Table::$format . '.pid', 0)
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
