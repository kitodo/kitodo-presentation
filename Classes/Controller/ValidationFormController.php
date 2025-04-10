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

use Kitodo\Dlf\Middleware\DOMDocumentValidation;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        /** @var SiteLanguage $language */
        $language = $GLOBALS['TYPO3_REQUEST']->getAttribute('language');

        // Get slug of the language
        $languageSlug = $language->getBase()->getPath();

        $this->view->assign("url", $languageSlug . '?middleware=dlf/domDocumentValidation');
        return $this->htmlResponse();
    }
}
