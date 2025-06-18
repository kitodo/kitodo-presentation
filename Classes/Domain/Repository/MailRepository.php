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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Mail repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class MailRepository extends Repository
{

    /**
     * Find all mails by pid.
     *
     * @access public
     *
     * @param int $pid
     * 
     * @return array|QueryResultInterface
     */
    public function findAllWithPid(int $pid): array|QueryResultInterface
    {
        /** @var Typo3QuerySettings $querySettings */
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);

        $querySettings->setStoragePageIds([$pid]);

        $this->setDefaultQuerySettings($querySettings);

        return $this->findAll();
    }
}
