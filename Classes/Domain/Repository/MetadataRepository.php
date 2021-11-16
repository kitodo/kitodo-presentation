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
use TYPO3\CMS\Core\Database\ConnectionPool;
use Kitodo\Dlf\Common\Helper;

class MetadataRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function getMetadataForListview($pages) {
        $query = $this->createQuery();

        $query->matching($query->logicalOr([
            $query->equals('is_listed', 1),
            $query->equals('is_sortable', 1)
        ]));
        $query->matching($query->equals('pid', $pages));

        $query->setOrderings([
            'sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
        ]);

        return $query->execute();
    }

    public function getMetadata($pages, $sysLangUid) {
        $query = $this->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid($sysLangUid);
        $querySettings->setLanguageOverlayMode('strict');

        $query->matching($query->equals('pid', $pages));

        return $query->execute();
    }

}