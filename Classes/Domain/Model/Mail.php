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
 * Mail entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Mail extends AbstractEntity
{
    /**
     * The mail label
     *
     * @var string
     * @access protected
     */
    protected $label = '';

    /**
     * The mail name
     *
     * @var string
     * @access protected
     */
    protected $name = '';

    /**
     * The mail name
     *
     * @var string
     * @access protected
     */
    protected $mail = '';

    /**
     * Initializes the mail entity.
     *
     * @access public
     * 
     * @param string $label: The mail label
     * @param string $name: The mail name
     * @param string $mail: The mail name
     *
     * @return void
     */
    public function __construct(
        string $label = '',
        string $name = '',
        string $mail = '')
    {
        $this->setLabel($label);
        $this->setName($name);
        $this->setMail($mail);
    }


    /**
     * Get the mail label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the mail label
     *
     * @param string $label The mail label
     *
     * @return void
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the mail name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the mail name
     *
     * @param string $name The mail name
     *
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the mail name
     *
     * @return string
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set the mail name
     *
     * @param string $mail The mail name
     *
     * @return void
     */
    public function setMail(string $mail)
    {
        $this->mail = $mail;
    }
}
