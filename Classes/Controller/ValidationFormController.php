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

/**
 * Plugin 'ValidationForm' for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ValidationFormController extends AbstractController
{

    /**
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        $language = $this->request->getAttribute('language');
        $this->view->assign("url", $language->getBase()->getPath() . '?middleware=dlf/domDocumentValidation');
        return $this->htmlResponse();
    }
}
