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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

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
        /** @var SiteLanguage $siteLanguage */
        $siteLanguage = $this->request->getAttribute('language') ?? $this->request->getAttribute('site')->getDefaultLanguage();

        $typeParam = 'dfgviewer';

        // retrieve validation configuration from plugin.tx_dlf typoscript
        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $typoScript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $settings = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScript['plugin.']['tx_dlf.']['settings.']);

        $disabledValidators = [];
        if (array_key_exists("domDocumentValidation", $settings) && array_key_exists($typeParam, $settings["domDocumentValidation"])) {
            $validationConfiguration = $settings['domDocumentValidation'][$typeParam];
            foreach ($validationConfiguration as $key => $value) {
                if (isset($value['disabled']) && $value['disabled'] === "true") {
                    $disabledValidators[$key]['title'] = $value['title'];
                    $disabledValidators[$key]['shortdescription'] = $value['shortdescription'];
                }
            }
        }

        $this->view->assign("url", $siteLanguage->getBase()->getPath() . '?middleware=dlf/domDocumentValidation');
        $this->view->assign("disabledValidators", $disabledValidators);
        return $this->htmlResponse();
    }
}
