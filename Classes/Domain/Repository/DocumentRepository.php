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

class DocumentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    public function findByUidAndPartOf($uid, $partOf)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('uid', $uid));
        $query->matching($query->equals('partof', $partOf));

        return $query->execute();
    }

    public function getChildrenOfYear($structure, $partOf)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('structure', $structure));
        $query->matching($query->equals('partof', $partOf));

        $query->setOrderings([
            'mets_orderlabel' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
        ]);

        return $query->execute();
    }
}