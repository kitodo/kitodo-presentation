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

namespace Kitodo\Dlf\Controller;

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FeedsController extends AbstractController
{
    /**
     * Initializes the current action
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->request->setFormat('xml');
    }

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        // access to GET parameter tx_dlf_feeds['collection']
        $requestData = $this->request->getArguments();

        // get library information
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_libraries');

        $result = $queryBuilder
            ->select('tx_dlf_libraries.label AS label')
            ->from('tx_dlf_libraries')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_libraries.pid', (int) $this->settings['pages']),
                $queryBuilder->expr()->eq('tx_dlf_libraries.uid', (int) $this->settings['library'])
            )
            ->setMaxResults(1)
            ->execute();

        $feedMeta = [];
        $documents = [];

        if ($resArray = $result->fetch()) {
            $feedMeta['copyright'] = $resArray['label'];
        } else {
            $this->logger->error('Failed to fetch label of selected library with "' . $this->settings['library'] . '"');
        }

        if (
            !$this->settings['excludeOtherCollections']
            || empty($requestData['collection'])
            || GeneralUtility::inList($this->settings['collections'], $requestData['collection'])
        ) {
            $additionalWhere = '';
            // Check for pre-selected collections.
            if (!empty($requestData['collection'])) {
                $additionalWhere = 'tx_dlf_collections.uid=' . intval($requestData['collection']);
            } elseif (!empty($this->settings['collections'])) {
                $additionalWhere = 'tx_dlf_collections.uid IN (' . implode(',', GeneralUtility::intExplode(',', $this->settings['collections'])) . ')';
            }

            // get documents
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            $result = $queryBuilder
                ->select(
                    'tx_dlf_documents.uid AS uid',
                    'tx_dlf_documents.partof AS partof',
                    'tx_dlf_documents.title AS title',
                    'tx_dlf_documents.volume AS volume',
                    'tx_dlf_documents.author AS author',
                    'tx_dlf_documents.record_id AS record_id',
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
                    $queryBuilder->expr()->eq('tx_dlf_documents.pid', $queryBuilder->createNamedParameter((int) $this->settings['pages'])),
                    $queryBuilder->expr()->eq('tx_dlf_documents_collections_mm.ident', $queryBuilder->createNamedParameter('docs_colls')),
                    $queryBuilder->expr()->eq('tx_dlf_collections.pid', $queryBuilder->createNamedParameter((int) $this->settings['pages'])),
                    $additionalWhere
                )
                ->groupBy('tx_dlf_documents.uid')
                ->orderBy('tx_dlf_documents.tstamp', 'DESC')
                ->setMaxResults((int) $this->settings['limit'])
                ->execute();

            $rows = $result->fetchAll();

            foreach ($rows as $resArray) {

                $title = '';
                // Get title of superior document.
                if ((empty($resArray['title']) || !empty($this->settings['prependSuperiorTitle']))
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
                    $title = LocalizationUtility::translate('noTitle', 'dlf');
                }
                // Append volume information.
                if (!empty($resArray['volume'])) {
                    $title .= ', ' . LocalizationUtility::translate('volume', 'dlf') . ' ' . $resArray['volume'];
                }
                // Is this document new or updated?
                if ($resArray['crdate'] == $resArray['tstamp']) {
                    $title = LocalizationUtility::translate('plugins.feeds.new', 'dlf') . ' ' . trim($title);
                } else {
                    $title = LocalizationUtility::translate('plugins.feeds.update', 'dlf') . ' ' . trim($title);
                }

                $resArray['title'] = $title;
                $documents[] = $resArray;
            }

        }

        $this->view->assign('documents', $documents);
        $this->view->assign('feedMeta', $feedMeta);

    }
}
