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

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Abstract Repository for allowing setting the storage pid.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @template T of DomainObjectInterface
 * @extends Repository<T>
 */
class AbstractRepository extends Repository
{

    /**
     * @access protected
     * @var bool allow debugging for queries
     */
    protected bool $debug = false;

    /**
     * Activate debug mode for the repository,
     * which allows to output the generated SQL queries in the debug log.
     *
     * @access public
     *
     * @return void
     */
    public function activateDebugMode(): void
    {
        $this->debug = true;
    }

    /**
     * Sets the storage pid for the repository.
     *
     * @access public
     *
     * @param int $storagePid
     *
     * @return void
     */
    public function setStoragePid(int $storagePid): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setStoragePageIds([$storagePid]);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Debug query.
     *
     * @access protected
     *
     * @param QueryInterface<T> $query
     *
     * @return void
     */
    protected function debugQuery(QueryInterface $query): void
    {
        if ($this->debug) {
            $typo3DbQueryParser = GeneralUtility::makeInstance(Typo3DbQueryParser::class);
            $queryBuilder = $typo3DbQueryParser->convertQueryToDoctrineQueryBuilder($query);
            DebuggerUtility::var_dump($queryBuilder->getSQL());
            DebuggerUtility::var_dump($queryBuilder->getParameters());
        }
    }

    /**
     * Debug query builder.
     *
     * @access protected
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return void
     */
    protected function debugQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->debug) {
            DebuggerUtility::var_dump($queryBuilder->getSQL());
            DebuggerUtility::var_dump($queryBuilder->getParameters());
        }
    }
}
