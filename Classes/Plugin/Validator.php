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

namespace Kitodo\Dlf\Plugin;

/**
 * Plugin 'Validator' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Validator extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Validator.php';

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Disable caching for this plugin.
        $this->setCache(false);
        // Load template file.
        $this->getTemplate();
        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Document could not be loaded.
            // Check:
            // - if document location is valid URL
            // - if document location is reachable
            // - if document is well-formed XML
            // - if document has METS node
        } else {
            // Document loaded.
            // Check:
            // - if document is valid METS document
            // - if document contains supported metadata schema
            // - if document's metadata are valid
            // - if document provides configured mandatory fields
        }
        return $this->pi_wrapInBaseClass($content);
    }
}
