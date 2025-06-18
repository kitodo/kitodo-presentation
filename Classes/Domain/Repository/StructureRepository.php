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

use Kitodo\Dlf\Domain\Model\Structure;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Structure repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @method int countByPid(int $uid) Count amount of structures for given PID
 * @method Structure|null findOneByIndexName(string $indexName) Get a structure by its index name
 */
class StructureRepository extends Repository
{

}
