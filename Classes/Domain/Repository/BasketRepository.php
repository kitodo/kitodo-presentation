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

use Kitodo\Dlf\Domain\Model\Basket;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * (Basket Plugin) Basket repository.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 *
 * @method Basket|null findOneByFeUserId(int $feUserId) Get a basket by frontend user ID
 * @method Basket|null findOneBySessionId(string $sessionId) Get a document by session id
 */
class BasketRepository extends Repository
{

}
