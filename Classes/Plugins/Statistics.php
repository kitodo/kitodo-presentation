<?php
namespace Kitodo\Dlf\Plugins;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Helper;

/**
 * Plugin 'Statistics' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	dlf
 * @access	public
 */
class Statistics extends \Kitodo\Dlf\Common\AbstractPlugin {

    public $scriptRelPath = 'Classes/Plugins/Statistics.php';

    /**
     * The main method of the PlugIn
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	string		The content that is displayed on the website
     */
    public function main($content, $conf) {

        $this->init($conf);

        // Turn cache on.
        $this->setCache(TRUE);

        // Quit without doing anything if required configuration variables are not set.
        if (empty($this->conf['pages'])) {

            Helper::devLog('[\\Kitodo\\Dlf\\Plugins\\Statistics->main('.$content.', [data])] Incomplete plugin configuration', SYSLOG_SEVERITY_WARNING, $conf);

            return $content;

        }

        // Get description.
        $content .= $this->pi_RTEcssText($this->conf['description']);

        // Check for selected collections.
        if ($this->conf['collections']) {

            // Include only selected collections.
            $resultTitles = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_collections',
                'AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND tx_dlf_documents.partof=0 AND tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).') AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').Helper::whereClause('tx_dlf_documents').Helper::whereClause('tx_dlf_collections'),
                'tx_dlf_documents.uid',
                '',
                ''
            );

            $resultVolumes = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents',
                'tx_dlf_relations',
                'tx_dlf_collections',
                'AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_collections.pid='.intval($this->conf['pages']).' AND NOT tx_dlf_documents.uid IN (SELECT DISTINCT tx_dlf_documents.partof FROM tx_dlf_documents WHERE NOT tx_dlf_documents.partof=0'.Helper::whereClause('tx_dlf_documents').') AND tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).') AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').Helper::whereClause('tx_dlf_documents').Helper::whereClause('tx_dlf_collections'),
                'tx_dlf_documents.uid',
                '',
                ''
            );

        } else {

            // Include all collections.
            $resultTitles = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents',
                'tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_documents.partof=0'.Helper::whereClause('tx_dlf_documents'),
                '',
                '',
                ''
            );

            $resultVolumes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents',
                'tx_dlf_documents.pid='.intval($this->conf['pages']).' AND NOT tx_dlf_documents.uid IN (SELECT DISTINCT tx_dlf_documents.partof FROM tx_dlf_documents WHERE NOT tx_dlf_documents.partof=0'.Helper::whereClause('tx_dlf_documents').')'.Helper::whereClause('tx_dlf_documents'),
                '',
                '',
                ''
            );

        }

        $countTitles = $GLOBALS['TYPO3_DB']->sql_num_rows($resultTitles);

        $countVolumes = $GLOBALS['TYPO3_DB']->sql_num_rows($resultVolumes);

        // Set replacements.
        $replace = array (
            'key' => array (
                '###TITLES###',
                '###VOLUMES###'
            ),
            'value' => array (
                $countTitles.($countTitles > 1 ? $this->pi_getLL('titles', '', TRUE) : $this->pi_getLL('title', '', TRUE)),
                $countVolumes.($countVolumes > 1 ? $this->pi_getLL('volumes', '', TRUE) : $this->pi_getLL('volume', '', TRUE))
            )
        );

        // Apply replacements.
        $content = str_replace($replace['key'], $replace['value'], $content);

        return $this->pi_wrapInBaseClass($content);

    }

}
