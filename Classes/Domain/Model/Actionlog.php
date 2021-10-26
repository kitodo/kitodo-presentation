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

class Actionlog extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var int
     */
    protected $count_pages;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->file_name;
    }

    /**
     * @param string $file_name
     */
    public function setFileName(string $file_name): void
    {
        $this->file_name = $file_name;
    }

    /**
     * @return int
     */
    public function getCountPages(): int
    {
        return $this->count_pages;
    }

    /**
     * @param int $count_pages
     */
    public function setCountPages(int $count_pages): void
    {
        $this->count_pages = $count_pages;
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