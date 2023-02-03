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

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Controller class for the plugin 'Navigation'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class NavigationController extends AbstractController
{
    /**
     * Method to get the page select values and use them with chash
     * @param \Kitodo\Dlf\Domain\Model\PageSelectForm|NULL $pageSelectForm
     * @return void
     */
    public function pageSelectAction(\Kitodo\Dlf\Domain\Model\PageSelectForm $pageSelectForm = NULL) {
        if ($pageSelectForm) {
            $uriBuilder = $this->getControllerContext()->getUriBuilder();
            $uri = $uriBuilder->reset()
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
     * @return void
     */
    public function mainAction()
    {
        // Load current document.
        $this->loadDocument($this->requestData);
        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            // Set default values if not set.
            if ($this->document->getDoc()->numPages > 0) {
                if (!empty($this->requestData['logicalPage'])) {
                    $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                    // The logical page parameter should not appear
                    unset($this->requestData['logicalPage']);
                }
                // Set default values if not set.
                // $this->requestData['page'] may be integer or string (physical structure @ID)
                if (
                    (int) $this->requestData['page'] > 0
                    || empty($this->requestData['page'])
                ) {
                    $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
                } else {
                    $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
                }
                $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
            } else {
                $this->requestData['page'] = 0;
                $this->requestData['double'] = 0;
            }
        }

        // Steps for X pages backward / forward. Double page view uses double steps.
        $pageSteps = $this->settings['pageStep'] * ($this->requestData['double'] + 1);

        $this->view->assign('pageSteps', $pageSteps);
        $this->view->assign('numPages', $this->document->getDoc()->numPages);
        $this->view->assign('viewData', $this->viewData);

        if ($GLOBALS['TSFE']->fe_user->getKey('ses', 'search')) {
            $lastSearchArguments = [];
            $searchSessionParameters = $GLOBALS['TSFE']->fe_user->getKey('ses', 'search');
            $widgetPage = $GLOBALS['TSFE']->fe_user->getKey('ses', 'widgetPage');

            if ($searchSessionParameters) {
                $lastSearchArguments = [
                    'tx_dlf_listview' => [
                        'searchParameter' => $searchSessionParameters
                    ]
                ];
            }
            if ($widgetPage) {
                $lastSearchArguments['tx_dlf_listview']['@widget_0'] = $widgetPage;
            }

            // save last search parameter to generate a link back to the search list
            $this->view->assign('lastSearchParams', $lastSearchArguments);
        }

        $pageOptions = [];
        for ($i = 1; $i <= $this->document->getDoc()->numPages; $i++) {
            $pageOptions[$i] = '[' . $i . ']' . ($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$i]]['orderlabel'] ? ' - ' . htmlspecialchars($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[$i]]['orderlabel']) : '');
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
