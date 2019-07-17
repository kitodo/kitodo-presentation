<?php
namespace Kitodo\Dlf\Cli;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\Helper;

/**
 * Command Line Indexer script for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class CommandLineIndexer extends \TYPO3\CMS\Core\Controller\CommandLineController {
    /**
     * This is the return code
     *
     * @var integer
     * @access protected
     */
    protected $return = 0;

    /**
     * Main function of the script
     *
     * @access public
     *
     * @return integer Return Code
     */
    public function main() {
        switch ((string) $this->cli_args['_DEFAULT'][1]) {
            // (Re-)Index a single document.
            case 'index':
                // Add command line arguments.
                $this->cli_options[] = ['-doc UID/URL', 'UID or (properly encoded) URL of the document.'];
                $this->cli_options[] = ['-pid UID', 'UID of the page the document should be added to.'];
                $this->cli_options[] = ['-core UID', 'UID of the Solr core the document should be added to.'];
                // Check the command line arguments.
                $this->cli_validateArgs();
                if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->cli_args['-doc'][0])
                    && !\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($this->cli_args['-doc'][0])) {
                    $this->cli_echo('ERROR: "'.$this->cli_args['-doc'][0].'" is not a valid document UID or URL.'.LF, TRUE);
                    $this->return = 1;
                }
                if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->cli_args['-pid'][0])) {
                    $this->cli_echo('ERROR: "'.$this->cli_args['-pid'][0].'" is not a valid page UID.'.LF, TRUE);
                    $this->return = 1;
                }
                if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->cli_args['-core'][0])) {
                    $this->cli_echo('ERROR: "'.$this->cli_args['-core'][0].'" is not a valid core UID.'.LF, TRUE);
                    $this->return = 1;
                }
                if ($this->return > 0) {
                    break;
                }
                // Get the document...
                $doc = Document::getInstance($this->cli_args['-doc'][0], $this->cli_args['-pid'][0], TRUE);
                if ($doc->ready) {
                    // ...and save it to the database...
                    if (!$doc->save(intval($this->cli_args['-pid'][0]), intval($this->cli_args['-core'][0]))) {
                        $this->cli_echo('ERROR: Document "'.$this->cli_args['-doc'][0].'" not saved and indexed.'.LF, TRUE);
                        $this->return = 1;
                    }
                } else {
                    $this->cli_echo('ERROR: Document "'.$this->cli_args['-doc'][0].'" could not be loaded.'.LF, TRUE);
                    $this->return = 1;
                }
                break;
            // Re-index all documents of a collection.
            case 'reindex':
                // Add command line arguments.
                $this->cli_options[] = ['-coll UID', 'UID of the collection.'];
                $this->cli_options[] = ['-pid UID', 'UID of the page the document should be added to.'];
                $this->cli_options[] = ['-core UID', 'UID of the Solr core the document should be added to.'];
                // Check the command line arguments.
                $this->cli_validateArgs();
                if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->cli_args['-coll'][0])) {
                    $this->cli_echo('ERROR: "'.$this->cli_args['-coll'][0].'" is not a valid collection UID.'.LF, TRUE);
                    $this->return = 1;
                }
                if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->cli_args['-pid'][0])) {
                    $this->cli_echo('ERROR: "'.$this->cli_args['-pid'][0].'" is not a valid page UID.'.LF, TRUE);
                    $this->return = 1;
                }
                if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->cli_args['-core'][0])) {
                    $this->cli_echo('ERROR: "'.$this->cli_args['-core'][0].'" is not a valid core UID.'.LF, TRUE);
                    $this->return = 1;
                }
                if ($this->return > 0) {
                    break;
                }
                // Get the collection.
                $result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
                    'tx_dlf_documents.uid AS uid',
                    'tx_dlf_documents',
                    'tx_dlf_relations',
                    'tx_dlf_collections',
                    'AND tx_dlf_collections.uid='.intval($this->cli_args['-coll'][0])
                        .' AND tx_dlf_collections.pid='.intval($this->cli_args['-pid'][0])
                        .' AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations')
                        .Helper::whereClause('tx_dlf_documents')
                        .Helper::whereClause('tx_dlf_collections')
                );
                while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                    // Get the document...
                    $doc = Document::getInstance($resArray['uid'], $this->cli_args['-pid'][0], TRUE);
                    if ($doc->ready) {
                        // ...and save it to the database...
                        if (!$doc->save(intval($this->cli_args['-pid'][0]), intval($this->cli_args['-core'][0]))) {
                            $this->cli_echo('ERROR: Document "'.$resArray['uid'].'" not saved and indexed.'.LF, TRUE);
                            $this->return = 1;
                        }
                    } else {
                        $this->cli_echo('ERROR: Document "'.$resArray['uid'].'" could not be loaded.'.LF, TRUE);
                        $this->return = 1;
                    }
                    // Clear document registry to prevent memory exhaustion.
                    Document::clearRegistry();
                }
                break;
            default:
                $this->cli_help();
                break;
        }
        exit ($this->return);
    }

    /**
     * Constructor for CLI interface
     *
     * @access public
     *
     * @return void
     */
    public function __construct() {
        // Run parent constructor.
        parent::__construct();
        // Set basic information about the script.
        $this->cli_help = [
            'name' => 'Command Line Interface for Kitodo.Presentation',
            'synopsis' => '###OPTIONS###',
            'description' => 'Currently the only tasks available are "index" and "reindex".'.LF.'Try "/PATH/TO/TYPO3/cli_dispatch.phpsh dlf TASK" for more options.',
            'examples' => '/PATH/TO/TYPO3/cli_dispatch.phpsh dlf TASK -ARG1=VALUE1 -ARG2=VALUE2',
            'options' => '',
            'author' => 'Kitodo. Key to digital objects e.V. <contact@kitodo.org>',
        ];
    }
}
