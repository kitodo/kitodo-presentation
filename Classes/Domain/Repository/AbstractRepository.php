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
     * Sets the storage pid to be ignored in the repository.
     *
     * @access public
     *
     * @return void
     */
    public function ignoreStoragePid(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
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
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(true);
        $querySettings->setStoragePageIds([$storagePid]);
        $this->setDefaultQuerySettings($querySettings);
    }
}
