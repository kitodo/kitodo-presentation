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

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Feeds' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Feeds extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Feeds.php';

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return void
     */
    public function main($content, $conf)
    {
        $this->init($conf);
        // Don't cache the output.
        $this->setCache(false);
        // Create XML document.
        $rss = new \DOMDocument('1.0', 'utf-8');
        // Add mandatory root element.
        $root = $rss->createElement('rss');
        $root->setAttribute('version', '2.0');
        // Add channel element.
        $channel = $rss->createElement('channel');
        $channel->appendChild($rss->createElement('title', htmlspecialchars($this->conf['title'], ENT_NOQUOTES, 'UTF-8')));
        $channel->appendChild($rss->createElement('link', htmlspecialchars(GeneralUtility::locationHeaderUrl($this->pi_linkTP_keepPIvars_url()), ENT_NOQUOTES, 'UTF-8')));
        if (!empty($this->conf['description'])) {
            $channel->appendChild($rss->createElement('description', htmlspecialchars($this->conf['description'], ENT_QUOTES, 'UTF-8')));
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_structures');

        $result = $queryBuilder
            ->select('tx_dlf_libraries.label AS label')
            ->from('tx_dlf_libraries')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_libraries.pid', intval($this->conf['pages'])),
                $queryBuilder->expr()->eq('tx_dlf_libraries.uid', intval($this->conf['library'])),
                Helper::whereExpression('tx_dlf_libraries')
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $result->fetchAll();

        if (count($allResults) === 1) {
            $resArray = $allResults[0];
            $channel->appendChild($rss->createElement('copyright', htmlspecialchars($resArray['label'], ENT_NOQUOTES, 'UTF-8')));
        }
        $channel->appendChild($rss->createElement('pubDate', date('r', $GLOBALS['EXEC_TIME'])));
        $channel->appendChild($rss->createElement('generator', htmlspecialchars($this->conf['useragent'], ENT_NOQUOTES, 'UTF-8')));
        // Add item elements.
        if (
            !$this->conf['excludeOther']
            || empty($this->piVars['collection'])
            || GeneralUtility::inList($this->conf['collections'], $this->piVars['collection'])
        ) {
            $additionalWhere = '';
            // Check for pre-selected collections.
            if (!empty($this->piVars['collection'])) {
                $additionalWhere = 'tx_dlf_collections.uid=' . intval($this->piVars['collection']);
            } elseif (!empty($this->conf['collections'])) {
                $additionalWhere = 'tx_dlf_collections.uid IN (' . implode(',', GeneralUtility::intExplode(',', $this->conf['collections'])) . ')';
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $result = $queryBuilder
                ->select(
                    'tx_dlf_documents.uid AS uid',
                    'tx_dlf_documents.partof AS partof',
                    'tx_dlf_documents.title AS title',
                    'tx_dlf_documents.volume AS volume',
                    'tx_dlf_documents.author AS author',
                    'tx_dlf_documents.record_id AS guid',
                    'tx_dlf_documents.tstamp AS tstamp',
                    'tx_dlf_documents.crdate AS crdate'
                )
                ->from('tx_dlf_documents')
                ->join(
                    'tx_dlf_documents',
                    'tx_dlf_relations',
                    'tx_dlf_documents_collections_mm',
                    $queryBuilder->expr()->eq('tx_dlf_documents.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_local'))
                )
                ->join(
                    'tx_dlf_documents_collections_mm',
                    'tx_dlf_collections',
                    'tx_dlf_collections',
                    $queryBuilder->expr()->eq('tx_dlf_collections.uid', $queryBuilder->quoteIdentifier('tx_dlf_documents_collections_mm.uid_foreign'))
                )
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', $queryBuilder->createNamedParameter((int) $this->conf['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_documents_collections_mm.ident', $queryBuilder->createNamedParameter('docs_colls')),
                    $queryBuilder->expr()->eq('tx_dlf_collections.pid', $queryBuilder->createNamedParameter((int) $this->conf['pages'])),
                    $additionalWhere,
                    Helper::whereExpression('tx_dlf_documents'),
                    Helper::whereExpression('tx_dlf_collections')
                )
                ->groupBy('tx_dlf_documents.uid')
                ->orderBy('tx_dlf_documents.tstamp', 'DESC')
                ->setMaxResults((int) $this->conf['limit'])
                ->execute();
            $rows = $result->fetchAll();

            if (count($rows) > 0) {
                // Add each record as item element.
                foreach ($rows as $resArray) {
                    $item = $rss->createElement('item');
                    $title = '';
                    // Get title of superior document.
                    if ((empty($resArray['title']) || !empty($this->conf['prependSuperiorTitle']))
                        && !empty($resArray['partof'])
                    ) {
                        $superiorTitle = Document::getTitle($resArray['partof'], true);
                        if (!empty($superiorTitle)) {
                            $title .= '[' . $superiorTitle . ']';
                        }
                    }
                    // Get title of document.
                    if (!empty($resArray['title'])) {
                        $title .= ' ' . $resArray['title'];
                    }
                    // Set default title if empty.
                    if (empty($title)) {
                        $title = $this->pi_getLL('noTitle');
                    }
                    // Append volume information.
                    if (!empty($resArray['volume'])) {
                        $title .= ', ' . $this->pi_getLL('volume') . ' ' . $resArray['volume'];
                    }
                    // Is this document new or updated?
                    if ($resArray['crdate'] == $resArray['tstamp']) {
                        $title = $this->pi_getLL('new') . ' ' . trim($title);
                    } else {
                        $title = $this->pi_getLL('update') . ' ' . trim($title);
                    }
                    $item->appendChild($rss->createElement('title', htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8')));
                    // Add link.
                    $linkConf = [
                        'parameter' => $this->conf['targetPid'],
                        'forceAbsoluteUrl' => 1,
                        'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                        'additionalParams' => GeneralUtility::implodeArrayForUrl($this->prefixId, ['id' => $resArray['uid']], '', true, false)
                    ];
                    $item->appendChild($rss->createElement('link', htmlspecialchars($this->cObj->typoLink_URL($linkConf), ENT_NOQUOTES, 'UTF-8')));
                    // Add author if applicable.
                    if (!empty($resArray['author'])) {
                        $item->appendChild($rss->createElement('author', htmlspecialchars($resArray['author'], ENT_NOQUOTES, 'UTF-8')));
                    }
                    // Add online publication date.
                    $item->appendChild($rss->createElement('pubDate', date('r', $resArray['crdate'])));
                    // Add internal record identifier.
                    $item->appendChild($rss->createElement('guid', htmlspecialchars($resArray['guid'], ENT_NOQUOTES, 'UTF-8')));
                    $channel->appendChild($item);
                }
            }
        }
        $root->appendChild($channel);
        // Build XML output.
        $rss->appendChild($root);
        $content = $rss->saveXML();
        // Clean output buffer.
        ob_end_clean();
        // Send headers.
        header('HTTP/1.1 200 OK');
        header('Cache-Control: no-cache');
        header('Content-Length: ' . strlen($content));
        header('Content-Type: application/rss+xml; charset=utf-8');
        header('Date: ' . date('r', $GLOBALS['EXEC_TIME']));
        header('Expires: ' . date('r', $GLOBALS['EXEC_TIME']));
        echo $content;
        exit;
    }
}
