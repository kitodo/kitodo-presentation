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

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Domain\Repository\LibraryRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller class for the plugin 'Feeds'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class FeedsController extends AbstractController
{

    /**
     * @access protected
     * @var LibraryRepository
     */
    protected LibraryRepository $libraryRepository;

    /**
     * @param LibraryRepository $libraryRepository
     */
    public function injectLibraryRepository(LibraryRepository $libraryRepository): void
    {
        $this->libraryRepository = $libraryRepository;
    }

    /**
     * Initializes the current action
     *
     * @access public
     *
     * @return void
     */
    public function initializeAction(): void
    {
        $this->request = $this->request->withFormat("xml");
    }

    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        // access to GET parameter tx_dlf_feeds['collection']
        $requestData = $this->request->getArguments();

        // get library information
        /** @var \Kitodo\Dlf\Domain\Model\Library|null $library */
        $library = $this->libraryRepository->findByUid($this->settings['library']);

        $feedMeta = [];
        $documents = [];

        if ($library) {
            $feedMeta['copyright'] = $library->getLabel();
        } else {
            $this->logger->error('Failed to fetch label of selected library with "' . $this->settings['library'] . '"');
        }

        if (
            !($this->settings['excludeOtherCollections'] ?? false)
            || empty($requestData['collection'])
            || GeneralUtility::inList($this->settings['collections'], $requestData['collection'])
        ) {

            $documents = $this->documentRepository->findAllByCollectionsLimited(GeneralUtility::intExplode(',', $requestData['collection'] ?? "", true), $this->settings['limit']);

            foreach ($documents as $document) {

                $title = '';
                // Get title of superior document.
                if ((empty($document->getTitle()) || !empty($this->settings['prependSuperiorTitle']))
                    && !empty($document->getPartof())
                ) {
                    $superiorTitle = AbstractDocument::getTitle($document->getPartof(), true);
                    if (!empty($superiorTitle)) {
                        $title .= '[' . $superiorTitle . ']';
                    }
                }
                // Get title of document.
                if (!empty($document->getTitle())) {
                    $title .= ' ' . $document->getTitle();
                }
                // Set default title if empty.
                if (empty($title)) {
                    $title = LocalizationUtility::translate('noTitle', 'dlf') ? : '';
                }
                // Append volume information.
                if (!empty($document->getVolume())) {
                    $title .= ', ' . LocalizationUtility::translate('volume', 'dlf') . ' ' . $document->getVolume();
                }
                // Is this document new or updated?
                if ($document->getCrdate() == $document->getTstamp()) {
                    $title = LocalizationUtility::translate('plugins.feeds.new', 'dlf') . ' ' . trim($title);
                } else {
                    $title = LocalizationUtility::translate('plugins.feeds.update', 'dlf') . ' ' . trim($title);
                }

                $document->setTitle($title);
            }
        }

        $this->view->assign('documents', $documents);
        $this->view->assign('feedMeta', $feedMeta);

        return $this->htmlResponse();
    }
}
