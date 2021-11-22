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


class TokenRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Delete all expired token
     *
     * @param int $expireTime
     *
     * @return void
     */
    public function deleteExpiredTokens($expireTime)
    {
        $query = $this->createQuery();

        $constraints = [];

        $constraints[] = $query->lessThan('tstamp', (int) (time() - $expireTime));

        if (count($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        $tokensToBeRemoved = $query->execute();

        foreach ($tokensToBeRemoved as $token) {
            $this->remove($token);
        }
    }
}
