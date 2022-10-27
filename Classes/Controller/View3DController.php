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
            $model = trim($this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[1]]['files']['DEFAULT']));
            $this->view->assign('3d', $model);

            $modelConverted = trim($this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$this->document->getDoc()->physicalStructure[1]]['files']['CONVERTED']));
            $xml = $this->requestData['id'];

            $settingsParts = explode("/", $model);
            $fileName = end($settingsParts);
            $path = substr($model, 0,  strrpos($model, $fileName));
            $modelSettings = $path . "metadata/" . $fileName . "_viewer";

            if (!empty($modelConverted)) {
                $model = $modelConverted;
            }

            if ($this->settings['useInternalProxy']) {
                $absoluteUri = !empty($this->settings['forceAbsoluteUrl']) ? true : false;
                
                $model = $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setCreateAbsoluteUri($absoluteUri)
                    ->setArguments([
                        'eID' => 'tx_dlf_pageview_proxy',
                        'url' => $model,
                        'uHash' => GeneralUtility::hmac($model, 'PageViewProxy')
                        ])
                    ->build();

                $xml = $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setCreateAbsoluteUri($absoluteUri)
                    ->setArguments([
                        'eID' => 'tx_dlf_pageview_proxy',
                        'url' => $xml,
                        'uHash' => GeneralUtility::hmac($xml, 'PageViewProxy')
                        ])
                    ->build();

                $modelSettings = $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setCreateAbsoluteUri($absoluteUri)
                    ->setArguments([
                        'eID' => 'tx_dlf_pageview_proxy',
                        'url' => $modelSettings,
                        'uHash' => GeneralUtility::hmac($modelSettings, 'PageViewProxy')
                        ])
                    ->build();
            }

            $this->view->assign('model', $model);
            $this->view->assign('xml', $xml);
            $this->view->assign('settings', $modelSettings);
            $this->view->assign('proxy', $this->settings['useInternalProxy']);
        }
    }
}
