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
 * Configured data formats and namespaces like MODS, ALTO, IIIF etc.
 * They are referenced by ``tx_dlf_metadataformat.encoded``.
 * The formats OAI, METS and XLINK are pre-defined.
 *
 * Data formats are modeled after XML, though JSON may be used with a pseudo root and namespace.
 *
 * For more information, see the documentation page on metadata.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Format extends AbstractEntity
{
    /**
     * @access protected
     * @var string Name of the type that is used to reference it.
     */
    protected $type;

    /**
     * @access protected
     * @var string The XML root element used by this format.
     */
    protected $root;

    /**
     * @access protected
     * @var string The XML namespace URI used by this format.
     */
    protected $namespace;

    /**
     * @access protected
     * @var string Fully qualified name of the PHP class that handles the format, or the empty string if no such class is configured.
     */
    protected $class;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @param string $root
     */
    public function setRoot(string $root): void
    {
        $this->root = $root;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }

}
