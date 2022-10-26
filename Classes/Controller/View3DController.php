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

use Kitodo\Dlf\Common\Doc;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'View3D' for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class View3DController extends AbstractController
{
    /**
     * @return string|void
     */
    public function mainAction()
    {
        $this->cObj = $this->configurationManager->getContentObject();
        // Load current document.
        $this->loadDocument($this->requestData);
        if (
            $this->document === null
            || $this->document->getDoc() === null
            || $this->document->getDoc()->metadataArray['LOG_0001']['type'][0] != 'object'
        ) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            $url = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[1]]['files']['DEFAULT']);
            if ($this->settings['useInternalProxy']) {
                // Configure @action URL for form.
                $uri = $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? true : false)
                    ->setArguments([
                        'eID' => 'tx_dlf_pageview_proxy',
                        'url' => urlencode($url),
                        'uHash' => GeneralUtility::hmac($url, 'PageViewProxy')
                        ])
                    ->build();
                    $url = $uri;
            }

            $this->view->assign('url', $url);
            $this->view->assign('scriptMain', '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/main.js');
            $this->view->assign('scriptToastify', '/typo3conf/ext/dlf/Resources/Public/Javascript/Toastify/toastify.js');
            $this->view->assign('scriptSpinner', '/typo3conf/ext/dlf/Resources/Public/Javascript/3DViewer/spinner/main.js');
        }
    }
}
