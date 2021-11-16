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
use Kitodo\Dlf\Domain\Repository\LibraryRepository;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FeedsController extends AbstractController
{

    /**
     * @var LibraryRepository
     */
    protected $libraryRepository;

    protected $documentRepository;

    /**
     * @param LibraryRepository $libraryRepository
     */
    public function injectLibraryRepository(LibraryRepository $libraryRepository)
    {
        $this->libraryRepository = $libraryRepository;
    }

    /**
     * @param DocumentRepository $documentRepository
     */
    public function injectDocumentRepository(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

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
        $library = $this->libraryRepository->findByUid($this->settings['library']);

        $feedMeta = [];
        $documents = [];

        if ($library) {
            $feedMeta['copyright'] = $library->getLabel();
        } else {
            $this->logger->error('Failed to fetch label of selected library with "' . $this->settings['library'] . '"');
        }

        if (
            !$this->settings['excludeOtherCollections']
            || empty($requestData['collection'])
            || GeneralUtility::inList($this->settings['collections'], $requestData['collection'])
        ) {

            $result = $this->documentRepository->getDocumentsForFeeds($this->settings, $requestData['collection']);

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
