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

use Kitodo\Dlf\Domain\Model\PageSelectForm;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Controller class for the plugin 'Navigation'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class NavigationController extends AbstractController
{
    /**
     * Method to get the page select values and use them with cHash
     *
     * @access public
     *
     * @param PageSelectForm|NULL $pageSelectForm
     *
     * @return void
     */
    public function pageSelectAction(PageSelectForm $pageSelectForm = NULL): void
    {
        if ($pageSelectForm) {
            $uri = $this->uriBuilder->reset()
                ->setArguments(
                    [
                        'tx_dlf' => [
                            'id' => $pageSelectForm->getId(),
                            'page' => $pageSelectForm->getPage(),
                            'double' => $pageSelectForm->getDouble()
                        ]
                    ]
                )
                ->uriFor('main');
            $this->redirectToUri($uri);
        }
    }

    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return void
     */
    public function mainAction(): void
    {
        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return;
        }

        // Set default values if not set.
        if ($this->document->getCurrentDocument()->numPages > 0) {
            $this->setPage();
        } else {
            $this->requestData['page'] = 0;
            $this->requestData['double'] = 0;
            // reassign requestData to viewData after assigning default values
            $this->viewData['requestData'] = $this->requestData;
        }

        // Steps for X pages backward / forward. Double page view uses double steps.
        $pageSteps = $this->settings['pageStep'] * ($this->requestData['double'] + 1);

        $this->view->assign('pageSteps', $pageSteps);
        $this->view->assign('numPages', $this->document->getCurrentDocument()->numPages);
        $this->view->assign('viewData', $this->viewData);

        $searchSessionParameters = $GLOBALS['TSFE']->fe_user->getKey('ses', 'search');
        if ($searchSessionParameters) {
            $lastSearchArguments = [
                'tx_dlf_listview' => [
                    'searchParameter' => $searchSessionParameters
                ]
            ];

            // save last search parameter to generate a link back to the search list
            $this->view->assign('lastSearchParams', $lastSearchArguments);
        }

        $pageOptions = [];
        for ($i = 1; $i <= $this->document->getCurrentDocument()->numPages; $i++) {
            $orderLabel = $this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[$i]]['orderlabel'];
            $pageOptions[$i] = '[' . $i . ']' . ($orderLabel ? ' - ' . htmlspecialchars($orderLabel) : '');
        }
        $this->view->assign('pageOptions', $pageOptions);

        // prepare feature array for fluid
        $features = [];
        foreach (explode(',', $this->settings['features']) as $feature) {
            $features[$feature] = true;
        }
        $this->view->assign('features', $features);
    }
}
