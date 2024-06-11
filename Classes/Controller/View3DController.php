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

/**
 * Plugin 'View3D' for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class View3DController extends AbstractController
{
    /**
     * @access public
     *
     * @return void
     */
    public function mainAction(): void
    {
        // Load current document.
        $this->loadDocument();
        if (
            $this->isDocMissingOrEmpty()
            || $this->document->getCurrentDocument()->metadataArray['LOG_0001']['type'][0] != 'object'
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            $model = trim($this->document->getCurrentDocument()->getFileLocation($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[1]]['files']['DEFAULT']));
            $this->view->assign('3d', $model);

            $modelConverted = trim($this->document->getCurrentDocument()->getFileLocation($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[1]]['files']['CONVERTED']));
            $xml = $this->requestData['id'];

            $settingsParts = explode("/", $model);
            $fileName = end($settingsParts);
            $path = substr($model, 0,  strrpos($model, $fileName));
            $modelSettings = $path . "metadata/" . $fileName . "_viewer";

            if (!empty($modelConverted)) {
                $model = $modelConverted;
            }

            if ($this->settings['useInternalProxy']) {
                $this->configureProxyUrl($model);
                $this->configureProxyUrl($xml);
                $this->configureProxyUrl($modelSettings);
            }

            $this->view->assign('model', $model);
            $this->view->assign('xml', $xml);
            $this->view->assign('settings', $modelSettings);
            $this->view->assign('proxy', $this->settings['useInternalProxy']);
        }
    }
}
