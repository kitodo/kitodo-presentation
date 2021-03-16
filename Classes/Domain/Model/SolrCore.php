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
 * SOLR core entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class SolrCore extends AbstractEntity
{
    /**
     * The SOLR core label
     *
     * @var string
     * @access protected
     */
    protected $label = '';

    /**
     * The index name
     *
     * @var string
     * @access protected
     */
    protected $indexName = '';

    /**
     * Initializes the SOLR core entity.
     *
     * @access public
     * 
     * @param string $label: The SOLR core label
     * @param string $indexName: The index name
     *
     * @return void
     */
    public function __construct(
        string $label = '',
        string $indexName = '')
    {
        $this->setLabel($label);
        $this->setIndexName($indexName);
    }

    /**
     * Get the SOLR core label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the SOLR core label
     *
     * @param string $label The SOLR core label
     *
     * @return void
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the index name
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * Set the index name
     *
     * @param string $indexName The index name
     *
     * @return void
     */
    public function setIndexName(string $indexName)
    {
        $this->indexName = $indexName;
    }
}
