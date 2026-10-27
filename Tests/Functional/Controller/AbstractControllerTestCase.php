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

namespace Kitodo\Dlf\Tests\Functional\Controller;

use Kitodo\Dlf\Controller\AbstractController;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\FluidViewFactory;

abstract class AbstractControllerTestCase extends FunctionalTestCase
{
    protected static $solrCoreId = 1;
    protected static $storagePid = 2;

    protected function setUpRequest($actionName, array $params = [], array $arguments = []): Request
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        return (new Request($serverRequest))
            ->withControllerActionName($actionName)
            ->withQueryParams($params)
            ->withArguments($arguments);
    }

    /**
     * Create a request that allows Fluid template files to use view helpers that need
     * a request context, such as f:translate (extension name + locale) and
     * f:link.page (application type, graceful degradation to a plain link in BE context).
     */
    protected function setUpTemplateRequest($actionName, array $params = [], array $arguments = []): Request
    {
        $request = $this->setUpRequest($actionName, $params, $arguments);
        return $request
            ->withControllerExtensionName('Dlf')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('language', new SiteLanguage(0, 'en', new Uri('http://test.local/'), ['typo3Language' => 'default']));
    }

    /**
     * Create a view that renders an actual template file (instead of an inline template string),
     * with the partials of the dlf extension available.
     */
    protected function setUpTemplateView(string $templatePathAndFilename, Request $request): ViewInterface
    {
        return $this->get(FluidViewFactory::class)->create(new ViewFactoryData(
            templatePathAndFilename: $templatePathAndFilename,
            partialRootPaths: [ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Partials/'],
            request: $request,
        ));
    }

    protected function setUpController($class, $settings, $templateHtml = '', ?ViewInterface $view = null): AbstractController
    {
        $documentRepository = $this->initializeRepository(DocumentRepository::class, self::$storagePid);

        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        if ($view === null) {
            $view = $this->get(FluidViewFactory::class)->create(new ViewFactoryData());
            $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($templateHtml);
        }

        /** @var AbstractController $controller */
        $controller = $this->get($class);
        
        $viewFactoryMock = $this->createMock(ViewFactoryInterface::class);
        $viewFactoryMock
            ->expects(self::once())
            ->method('create')
            ->with(self::isInstanceOf(ViewFactoryData::class))
            ->willReturn($view);

        $controller->injectViewFactory($viewFactoryMock);
        $controller->injectDocumentRepository($documentRepository);
        $controller->setSettingsForTest($settings);
        return $controller;
    }
}
