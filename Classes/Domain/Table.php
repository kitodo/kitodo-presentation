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

namespace Kitodo\Dlf\Domain;

/**
 * Enum for the database tables names.
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
 //TODO: in PHP 8.1 convert to Enum
class Table
{
    /**
     * The table name: 'tx_dlf_domain_model_actionlog'
     *
     * @var string
     * @static
     * @access public
     */
    public static $actionLog = 'tx_dlf_domain_model_actionlog';

    /**
     * The table name: 'tx_dlf_domain_model_basket'
     *
     * @var string
     * @static
     * @access public
     */
    public static $basket = 'tx_dlf_domain_model_basket';

    /**
     * The table name: 'tx_dlf_domain_model_collection'
     *
     * @var string
     * @static
     * @access public
     */
    public static $collection = 'tx_dlf_domain_model_collection';

    /**
     * The table name: 'tx_dlf_domain_model_document'
     *
     * @var string
     * @static
     * @access public
     */
    public static $document = 'tx_dlf_domain_model_document';

    /**
     * The table name: 'tx_dlf_domain_model_format'
     *
     * @var string
     * @static
     * @access public
     */
    public static $format = 'tx_dlf_domain_model_format';

    /**
     * The table name: 'tx_dlf_domain_model_library'
     *
     * @var string
     * @static
     * @access public
     */
    public static $library = 'tx_dlf_domain_model_library';

    /**
     * The table name: 'tx_dlf_domain_model_mail'
     *
     * @var string
     * @static
     * @access public
     */
    public static $mail = 'tx_dlf_domain_model_mail';

    /**
     * The table name: 'tx_dlf_domain_model_metadata'
     *
     * @var string
     * @static
     * @access public
     */
    public static $metadata = 'tx_dlf_domain_model_metadata';

    /**
     * The table name: 'tx_dlf_domain_model_metadataformat'
     *
     * @var string
     * @static
     * @access public
     */
    public static $metadataFormat = 'tx_dlf_domain_model_metadataformat';

    /**
     * The table name: 'tx_dlf_domain_model_printer'
     *
     * @var string
     * @static
     * @access public
     */
    public static $printer = 'tx_dlf_domain_model_printer';

    /**
     * The table name: 'tx_dlf_domain_model_relation'
     *
     * @var string
     * @static
     * @access public
     */
    public static $relation = 'tx_dlf_domain_model_relation';

    /**
     * The table name: 'tx_dlf_domain_model_solrcore'
     *
     * @var string
     * @static
     * @access public
     */
    public static $solrCore = 'tx_dlf_domain_model_solrcore';

    /**
     * The table name: 'tx_dlf_domain_model_token'
     *
     * @var string
     * @static
     * @access public
     */
    public static $token = 'tx_dlf_domain_model_token';

    /**
     * The table name: 'tx_dlf_domain_model_structure'
     *
     * @var string
     * @static
     * @access public
     */
    public static $structure = 'tx_dlf_domain_model_structure';
}