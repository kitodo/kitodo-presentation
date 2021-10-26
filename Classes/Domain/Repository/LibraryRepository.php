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

class LibraryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    // Feeds.php line 63
    public function getLibrary($uid, $pid) {
        $query = $this->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([$pid]);

        $query->equals('uid', $uid);

        return $query->execute();
    }


}