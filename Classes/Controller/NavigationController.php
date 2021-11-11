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

class NavigationController extends AbstractController
{
    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');

        if (empty($requestData['page'])) {
            $requestData['page'] = 1;
        }
        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            // Set default values if not set.
            if ($this->doc->numPages > 0) {
                if (!empty($requestData['logicalPage'])) {
                    $requestData['page'] = $this->doc->getPhysicalPage($requestData['logicalPage']);
                    // The logical page parameter should not appear
                    unset($requestData['logicalPage']);
                }
                // Set default values if not set.
                // $requestData['page'] may be integer or string (physical structure @ID)
                if (
                    (int) $requestData['page'] > 0
                    || empty($requestData['page'])
                ) {
                    $requestData['page'] = MathUtility::forceIntegerInRange((int) $requestData['page'], 1, $this->doc->numPages, 1);
                } else {
                    $requestData['page'] = array_search($requestData['page'], $this->doc->physicalStructure);
                }
                $requestData['double'] = MathUtility::forceIntegerInRange($requestData['double'], 0, 1, 0);
            } else {
                $requestData['page'] = 0;
                $requestData['double'] = 0;
            }
        }

        // Steps for X pages backward / forward. Double page view uses double steps.
        $pageSteps = $this->settings['pageStep'] * ($requestData['double'] + 1);

        $this->view->assign('page', $requestData['page']);
        $this->view->assign('pageSteps', $pageSteps);
        $this->view->assign('double', $requestData['double']);
        $this->view->assign('numPages', $this->doc->numPages);

        $pageOptions = [];
        for ($i = 1; $i <= $this->doc->numPages; $i++) {
            $pageOptions[$i] = '[' . $i . ']' . ($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$i]]['orderlabel'] ? ' - ' . htmlspecialchars($this->doc->physicalStructureInfo[$this->doc->physicalStructure[$i]]['orderlabel']) : '');
        }
        $this->view->assign('uniqueId', uniqid(Helper::getUnqualifiedClassName(get_class($this)) . '-'));
        $this->view->assign('pageOptions', $pageOptions);

        // prepare feature array for fluid
        $features = [];
        foreach (explode(',', $this->settings['features']) as $feature) {
            $features[$feature] = true;
        }
        $this->view->assign('features', $features);
    }
}
