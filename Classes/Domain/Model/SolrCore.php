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
 * Cores on the application-wide Solr instance that are available for indexing.
 * They may be used, for example, as a parameter to the CLI indexing commands, and are referenced by ``tx_dlf_document.solrcore``.
 * In particular, this holds the index name of the used Solr core.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class SolrCore extends AbstractEntity
{
    /**
     * @access protected
     * @var string Label of the core that is displayed in the backend.
     */
    protected $label;

    /**
     * @access protected
     * @var string The actual name of the Solr core.
     */
    protected $indexName;

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

    /**
     * @return string
     */
    public function getIndexName(): string
    {
        return $this->indexName;
    }

    /**
     * @param string $indexName
     */
    public function setIndexName(string $indexName): void
    {
        $this->indexName = $indexName;
    }
}
