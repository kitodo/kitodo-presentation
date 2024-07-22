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
 * Plugin 'Embedded3dViewer' for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Embedded3dViewerController extends AbstractController
{

    const MIDDLEWARE_DLF_EMBEDDED_3D_VIEWER_PREFIX = '/?middleware=dlf/embedded3dviewer';

    /**
     * @access public
     *
     * @return void
     */
    public function mainAction(): void
    {
        if (!empty($this->requestData['model'])) {
            $this->view->assign('embedded3dViewerUrl', $this->buildEmbedded3dViewerUrl($this->requestData['model']));
            return;
        }

        // Load current document.
        $this->loadDocument();
        if (
            !($this->isDocMissingOrEmpty()
                || $this->document->getCurrentDocument()->metadataArray['LOG_0001']['type'][0] != 'object')
        ) {
            $model = trim($this->document->getCurrentDocument()->getFileLocation($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[1]]['files']['DEFAULT']));
            $this->view->assign('embedded3dViewerUrl', $this->buildEmbedded3dViewerUrl($model));
        }
    }

    /**
     * Builds the embedded 3D viewer url.
     *
     * @param string $model The model url
     * @return string The embedded 3D viewer url
     */
    public function buildEmbedded3dViewerUrl(string $model): string
    {
        $embedded3dViewerUrl = self::MIDDLEWARE_DLF_EMBEDDED_3D_VIEWER_PREFIX . '&model=' . $model;

        if (!empty($this->requestData['viewer'])) {
            $embedded3dViewerUrl .= '&viewer=' . $this->requestData['viewer'];
        }
        return $embedded3dViewerUrl;
    }

}
