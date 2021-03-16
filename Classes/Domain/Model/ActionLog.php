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
 * Action Log entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ActionLog extends AbstractEntity
{
    /**
     * The action log label
     *
     * @var string
     * @access protected
     */
    protected $label = '';

    /**
     * The user id
     *
     * @var int
     * @access protected
     */
    protected $userId = 0;

    /**
     * The action log file name
     *
     * @var string
     * @access protected
     */
    protected $fileName = '';

    /**
     * The amount of pages in action log
     *
     * @var int
     * @access protected
     */
    protected $countPages = '';

    /**
     * The action log name
     *
     * @var string
     * @access protected
     */
    protected $name = '';

    /**
     * Initializes the action log entity.
     *
     * @access public
     * 
     * @param string $label: The SOLR core label
     * @param int $userId: The user id
     * @param $fileName: The action log file name
     * @param int $countPages: The amount of pages in action log
     * @param string $name: The action log name
     *
     * @return void
     */
    public function __construct(
        string $label = '',
        int $userId = 0,
        string $fileName = '',
        int $countPages = 0,
        string $name = '')
    {
        $this->setLabel($label);
    }

    /**
     * Get the action log label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the action log label
     *
     * @param string $label The action log label
     *
     * @return void
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the user id
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the user id
     *
     * @param int $userId The user id
     *
     * @return void
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Get the action log file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set the action log file name
     *
     * @param string $fileName The action log file name
     *
     * @return void
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Get the amount of pages in action log
     *
     * @return int
     */
    public function getCountPages()
    {
        return $this->countPages;
    }

    /**
     * Set the amount of pages in action log
     *
     * @param int $countPages The amount of pages in action log
     *
     * @return void
     */
    public function setCountPages(int $countPages)
    {
        $this->countPages = $countPages;
    }

    /**
     * Get the action log name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the action log name
     *
     * @param string $name The action log name
     *
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}
