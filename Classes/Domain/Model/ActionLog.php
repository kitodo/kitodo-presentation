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
 * (Basket Plugin) Action log for mails and printouts.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ActionLog extends AbstractEntity
{
    /**
     * @access protected
     * @var int
     */
    protected $userId;

    /**
     * @access protected
     * @var string
     */
    protected $fileName;

    /**
     * @access protected
     * @var int
     */
    protected $countPages;

    /**
     * @access protected
     * @var string
     */
    protected $name;

    /**
     * @access protected
     * @var string
     */
    protected $label;

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return int
     */
    public function getCountPages(): int
    {
        return $this->countPages;
    }

    /**
     * @param int $countPages
     */
    public function setCountPages(int $countPages): void
    {
        $this->countPages = $countPages;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

}
