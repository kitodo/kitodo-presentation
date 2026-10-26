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

use Kitodo\Dlf\Validation\Configuration\YamlConfigurationProvider;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Validation' for the 'dlf' extension
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ValidationController extends AbstractController
{
    /**
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        $validationConfiguration = GeneralUtility::makeInstance(YamlConfigurationProvider::class)->getConfiguration($this->settings['type']);

        /** @var SiteLanguage $siteLanguage */
        $siteLanguage = $this->request->getAttribute('language') ?? $this->request->getAttribute('site')->getDefaultLanguage();

        $disabledValidators = [];
        foreach ($validationConfiguration as $key => $value) {
            if (isset($value['disabled']) && $value['disabled'] === "true") {
                $disabledValidators[$key]['title'] = $value['title'];
                $disabledValidators[$key]['shortDescription'] = $value['shortDescription'];
            }
        }

        $this->view->assign("url", $siteLanguage->getBase()->getPath() . '?middleware=dlf/dom-document-validation');
        $this->view->assign("disabledValidators", $disabledValidators);
        return $this->htmlResponse();
    }
}
