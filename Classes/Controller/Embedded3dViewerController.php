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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

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
    /**
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        $siteLanguage = $this->getSiteLanguage();

        if (!empty($this->requestData['model']) || !empty($this->settings['model'])) {
            $this->view->assign('embedded3dViewerUrl', $this->buildEmbedded3dViewerUrl($siteLanguage));
            return $this->htmlResponse();
        }

        // when using the component
        if (!empty($this->settings['document'])) {
            $this->assignModelFromDocument($this->getDocumentByUrl($this->settings['document']), $siteLanguage);
            return $this->htmlResponse();
        }

        $this->loadDocument();

        if (!$this->isDocMissingOrEmpty()) {
            $this->assignModelFromDocument($this->document->getCurrentDocument(), $siteLanguage);
        }

        return $this->htmlResponse();
    }

    /**
     * Get the site language of the current request.
     *
     * @return SiteLanguage
     */
    protected function getSiteLanguage(): SiteLanguage
    {
        return $this->request->getAttribute('language') ?? $this->request->getAttribute('site')->getDefaultLanguage();
    }

    /**
     * Builds the embedded 3D viewer url.
     *
     * @param SiteLanguage $siteLanguage The site language to build the url against
     * @param string $model The model url
     * @param string $mimeType The mime type of the model
     * @return string The embedded 3D viewer url
     */
    protected function buildEmbedded3dViewerUrl(SiteLanguage $siteLanguage, string $model = '', string $mimeType = ''): string
    {
        $viewer = "";
        $embedded3dViewerUrl = $siteLanguage->getBase()->getPath() . '?middleware=dlf/embedded3dviewer';

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
     * @param SiteLanguage $siteLanguage The site language to build the url against
     */
    protected function assignModelFromDocument(AbstractDocument $document, SiteLanguage $siteLanguage): void
    {
        if ($document->getToplevelMetadata()['type'][0] === 'object') {
            $fileId = $document->physicalStructureInfo[$document->physicalStructure[1]]['files']['DEFAULT'];
            $mimeType = trim($document->getFileMimeType($fileId));
            $model = trim($document->getFileLocation($fileId));
            $this->view->assign('embedded3dViewerUrl', $this->buildEmbedded3dViewerUrl($siteLanguage, $model, Helper::getModelFormatOfMimeType($mimeType)));
        }
    }

    /**
     * Get the query part.
     *
     * Gets the query part including the separator, parameter name and value.
     * The value will be overwritten if the request data or settings contain the same name.
     *
     * @param string $name
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
