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
 * Printer entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Printer extends AbstractEntity
{
    /**
     * The printer label
     *
     * @var string
     * @access protected
     */
    protected $label = '';

    /**
     * The printer CLI command
     *
     * @var string
     * @access protected
     */
    protected $print = '';

    /**
     * Initializes the printer entity.
     *
     * @access public
     * 
     * @param string $label: The SOLR core label
     * @param string $print: The printer CLI command
     *
     * @return void
     */
    public function __construct(
        string $label = '',
        string $print = '')
    {
        $this->setLabel($label);
        $this->setIndexName($print);
    }

    /**
     * Get the printer label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the printer label
     *
     * @param string $label The printer label
     *
     * @return void
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the printer CLI command
     *
     * @return string
     */
    public function getPrint()
    {
        return $this->print;
    }

    /**
     * Set the printer CLI command
     *
     * @param string $print The printer CLI command
     *
     * @return void
     */
    public function setPrint(string $print)
    {
        $this->print = $print;
    }
}
