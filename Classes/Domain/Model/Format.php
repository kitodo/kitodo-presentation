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
 * Format entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Format extends AbstractEntity
{
    /**
     * The format type
     *
     * @var string
     * @access protected
     */
    protected $type = '';

    /**
     * The format root
     *
     * @var string
     * @access protected
     */
    protected $root = '';

    /**
     * The format namespace
     *
     * @var string
     * @access protected
     */
    protected $namespace = '';

    /**
     * The format class
     *
     * @var string
     * @access protected
     */
    protected $class = '';

    /**
     * Initializes the format entity.
     *
     * @access public
     * 
     * @param string $type: The format type
     * @param string $root: The format root
     * @param string $namespace: The format namespace
     * @param string $class: The format class
     *
     * @return void
     */
    public function __construct(
        string $type = '',
        string $root = '',
        string $namespace = '',
        string $class = '')
    {
        $this->setType($type);
        $this->setRoot($root);
        $this->setNamespace($namespace);
        $this->setClass($class);
    }

    /**
     * Get the format type.
     *
     * @access public
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the format type.
     *
     * @access public
     *
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the format root.
     *
     * @access public
     *
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Set the format root.
     *
     * @access public
     *
     * @return void
     */
    public function setRoot(string $root): void
    {
        $this->root = $root;
    }

    /**
     * Get the format namespace.
     *
     * @access public
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Set the format namespace.
     *
     * @access public
     *
     * @return void
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Get the format class.
     *
     * @access public
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set the format class.
     *
     * @access public
     *
     * @return void
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }
}
