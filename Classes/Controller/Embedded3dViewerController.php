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
use Kitodo\Dlf\Common\Helper;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\PathUtility;

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
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        if (!empty($this->requestData['model']) || !empty($this->settings['model'])) {
            $this->view->assign('embedded3dViewerUrl', $this->buildEmbedded3dViewerUrl());
            return $this->htmlResponse();
        }

        // when using the component
        if (!empty($this->settings['document'])) {
            $this->assignModelFromDocument($this->getDocumentByUrl($this->settings['document']));
            return $this->htmlResponse();
        }

        $this->loadDocument();

        if (!$this->isDocMissingOrEmpty()) {
            $this->assignModelFromDocument($this->document->getCurrentDocument());
        }

        return $this->htmlResponse();
    }

    /**
     * Builds the embedded 3D viewer url.
     *
     * @param string $model The model url
     * @param string $mimeType The mime type of the model
     * @return string The embedded 3D viewer url
     */
    protected function buildEmbedded3dViewerUrl(string $model = '', string $mimeType = ''): string
    {
        $viewer = "";
        $embedded3dViewerUrl = self::MIDDLEWARE_DLF_EMBEDDED_3D_VIEWER_PREFIX;

        $embedded3dViewerUrl .= $this->getQueryPart('model', $model);

        $modelFormat = $this->getModelFormat($mimeType, $model);
        if (!empty($modelFormat)) {
            $embedded3dViewerUrl .= '&' . http_build_query(['modelFormat' => $modelFormat]);
        }

        $embedded3dViewerUrl .= $this->getQueryPart('viewer', $viewer);

        if (!empty($this->requestData['viewerParam'])) {
            $embedded3dViewerUrl .= '&' . http_build_query(['viewerParam' => $this->requestData['viewerParam']]);
        }

        if (!empty($this->settings['queryString'])) {
            $embedded3dViewerUrl .= '&' . $this->settings['queryString'];
        }

        return $embedded3dViewerUrl;
    }

    /**
     * Assign the model from document to view.
     *
     * @param AbstractDocument $document The document containing the model
     */
    protected function assignModelFromDocument(AbstractDocument $document): void
    {
        if ($document->getToplevelMetadata()['type'][0] === 'object') {
            $fileId = $document->physicalStructureInfo[$document->physicalStructure[1]]['files']['DEFAULT'];
            $mimeType = trim($document->getFileMimeType($fileId));
            $model = trim($document->getFileLocation($fileId));
            $this->view->assign('embedded3dViewerUrl', $this->buildEmbedded3dViewerUrl($model,Helper::getModelFormatOfMimeType($mimeType)));
        }
    }

    /**
     * Get the query part.
     *
     * Gets the query part including the separator, parameter name and value.
     * The value will be overwritten if the request data or settings contain the same name.
     *
     * @param mixed $name
     * @param string $value
     * @return string The query part with separator, parameter name and value
     */
    protected function getQueryPart(string $name, string $value): string
    {
        if (!empty($this->requestData[$name])) {
            $value = $this->requestData[$name];
        } elseif (!empty($this->settings[$name])) {
            $value = $this->settings[$name];
        }

        if (!empty($name)) {
            return '&' . http_build_query([$name => $value]);
        }
        return '';
    }
}
