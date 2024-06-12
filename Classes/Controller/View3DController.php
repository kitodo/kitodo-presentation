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

    const MIDDLEWARE_DLF_EMBEDDED_3D_VIEWER_PREFIX = '/?middleware=dlf/embedded3DViewer';

    /**
     * @access public
     *
     * @return void
     */
    public function mainAction(): void
    {

        if (!empty($this->requestData['model'])) {
            $this->view->assign('is3DViewer', $this->is3DViewer($this->requestData['model']));
            $embedded3DViewerUrl = $this->buildEmbedded3DViewerUrl($this->requestData['model']);
            if (!empty($this->requestData['viewer'])) {
                $embedded3DViewerUrl .= '&viewer=' . $this->requestData['viewer'];
            }
            $this->view->assign('embedded3DViewerUrl', $embedded3DViewerUrl);
            return;
        }

        // Load current document.
        $this->loadDocument();
        if (
            !($this->isDocMissingOrEmpty()
                || $this->document->getCurrentDocument()->metadataArray['LOG_0001']['type'][0] != 'object')
        ) {
            $model = trim($this->document->getCurrentDocument()->getFileLocation($this->document->getCurrentDocument()->physicalStructureInfo[$this->document->getCurrentDocument()->physicalStructure[1]]['files']['DEFAULT']));
            $this->view->assign('is3DViewer', $this->is3DViewer($model));
            $this->view->assign('embedded3DViewerUrl', $this->buildEmbedded3DViewerUrl($model));
        }
    }

    /**
     * Checks if the 3D viewer can be rendered.
     *
     * @return bool True if the 3D viewer can be rendered
     */
    private function is3DViewer($model): bool
    {
        return !empty($model);
    }

    /**
     * Builds the embedded 3D viewer url.
     *
     * @param string $model The model url
     * @return string The embedded 3D viewer url
     */
    public function buildEmbedded3DViewerUrl(string $model): string
    {
        return self::MIDDLEWARE_DLF_EMBEDDED_3D_VIEWER_PREFIX . '&model=' . $model;
    }


}
