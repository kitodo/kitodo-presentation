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

use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Token repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class TokenRepository extends Repository
{
    /**
     * Delete all expired token
     *
     * @access public
     *
     * @param int $expireTime
     *
     * @return void
     */
    public function deleteExpiredTokens(int $expireTime): void
    {
        $query = $this->createQuery();

        $constraints = [];

        $constraints[] = $query->lessThan('tstamp', time() - $expireTime);

        if (count($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        $tokensToBeRemoved = $query->execute();

        foreach ($tokensToBeRemoved as $token) {
            $this->remove($token);
        }
    }
}
