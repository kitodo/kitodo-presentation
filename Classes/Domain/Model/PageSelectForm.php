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

namespace Kitodo\Dlf\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * (Basket Plugin) A basket that is bound to a frontend session.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class PageSelectForm extends AbstractEntity
{
    /**
     * @access protected
     * @var integer
     */
    protected $id;

    /**
     * @access protected
     * @var string
     */
    protected $recordId;

    /**
     * @access protected
     * @var string
     */
    protected $double;

    /**
     * @access protected
     * @var integer
     */
    protected $page;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getRecordId(): string
    {
        return $this->recordId;
    }

    /**
     * @param string $recordId
     */
    public function setRecordId(string $recordId): void
    {
        $this->recordId = $recordId;
    }

    /**
     * @return string
     */
    public function getDouble(): ?string
    {
        return $this->double;
    }

    /**
     * @param string $double
     */
    public function setDouble(string $double): void
    {
        $this->double = $double;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
    }



}
