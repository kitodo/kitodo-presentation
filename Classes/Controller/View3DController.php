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
            $this->view->assign('url', '');
            $this->view->assign('script.main', '');
            $this->view->assign('script.toastify', '');
            $this->view->assign('script.spinner', '');
        }
    }
}
