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

class CollectionRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function getCollectionList(array $uids, $showUserDefined = 0)
    {
        $query = $this->createQuery();

        if (!empty($uids)) {
            $constraints = [];
            // selected collections
            foreach ($uids as $uid) {
                $constraints[] = $query->contains('uid', $uid);
            }
            $query->matching($query->logicalOr($constraints));
        }

        $query->matching($query->equals('fe_cruser_id', $showUserDefined));

        $query->setOrderings([
            'label' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
        ]);

    }


}