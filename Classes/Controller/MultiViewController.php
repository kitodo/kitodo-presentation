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

use Psr\Http\Message\ResponseInterface;
use Ubl\Iiif\Presentation\Common\Model\Resources\CanvasInterface;
use Ubl\Iiif\Presentation\Common\Model\Resources\ManifestInterface;
use Ubl\Iiif\Presentation\Common\Vocabulary\Motivation;

/**
 * Controller class for the plugin 'Multi View'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class MultiViewController extends AbstractController
{

    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        // Load current document.
        $this->loadDocument();

        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return $this->htmlResponse();
        }

        $this->setPage();

        $page = $this->requestData['page'] ?? 0;

        $this->view->assign('viewData', $this->viewData);
        $this->view->assign('forceAbsoluteUrl', $this->extConf['general']['forceAbsoluteUrl'] ?? 0);
        $this->view->assign('docId', $this->requestData['id']);
        $this->view->assign('page', $page);

        $this->view->assign('multiview', 1);
        $this->view->assign('multiViewDocuments', $this->multiViewDocuments);

        return $this->htmlResponse();
    }
}
