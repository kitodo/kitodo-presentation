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

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

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
     * Sets deleted records to be not returned by the find methods in the repository.
     *
     * @access public
     *
     * @return void
     */
    public function ignoreDeleted(): void
    {
        $querySettings = $this->getDefaultQuerySettings();
        $querySettings->setIncludeDeleted(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Sets hidden records to be not returned by the find methods in the repository.
     *
     * @access public
     *
     * @return void
     */
    public function ignoreHidden(): void
    {
        $querySettings = $this->getDefaultQuerySettings();
        $querySettings->setIgnoreEnableFields(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Sets the storage pid to be ignored in the repository.
     *
     * @access public
     *
     * @return void
     */
    public function ignoreStoragePid(): void
    {
        $querySettings = $this->getDefaultQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Sets the storage pid for use in the repository.
     *
     * @access public
     *
     * @param int $storagePid
     *
     * @return void
     */
    public function useStoragePid(int $storagePid): void
    {
        $querySettings = $this->getDefaultQuerySettings();
        $querySettings->setRespectStoragePage(true);
        $querySettings->setStoragePageIds([$storagePid]);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Sets deleted records to be returned by the find methods in the repository.
     *
     * @access public
     *
     * @return void
     */
    public function showDeleted(): void
    {
        $querySettings = $this->getDefaultQuerySettings();
        $querySettings->setIncludeDeleted(true);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Sets hidden records to be not returned by the find methods in the repository.
     *
     * @access public
     *
     * @return void
     */
    public function showHidden(): void
    {
        $querySettings = $this->getDefaultQuerySettings();
        $querySettings->setIgnoreEnableFields(true);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Persist objects.
     *
     * @access public
     *
     * @return void
     */
    public function persistAll(): void
    {
        $this->persistenceManager->persistAll();
    }

    /**
     * Get default settings for repository, if not set create the new one.
     *
     * @return QuerySettingsInterface
     */
    protected function getDefaultQuerySettings(): QuerySettingsInterface
    {
        // @phpstan-ignore-next-line (defaultQuerySettings can be null)
        if (empty($this->defaultQuerySettings)) {
            return $this->createQuery()->getQuerySettings();
        }
        return $this->defaultQuerySettings;
    }
}
