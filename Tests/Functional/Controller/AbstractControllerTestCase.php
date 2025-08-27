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
use ReflectionClass;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\StandaloneView;

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

    protected function setUpController($class, $settings, $templateHtml = ''): AbstractController
    {
        $documentRepository = $this->initializeRepository(DocumentRepository::class, self::$storagePid);

        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $view = new StandaloneView();
        $view->setTemplateSource($templateHtml);

        if ((new Typo3Version())->getMajorVersion() == 13) {
            // ViewResolverInterface was changed in Typo3 v13
            // which makes it necessary to wrap the view in a FluidViewAdapater
            $view = new FluidViewAdapter($view);
        }

        $controller = $this->get($class);

        // the protected property "defaultViewObjectName" is used to hide deprecated Typo3 v12 behaviour in v13
        // without settings this property (or starting in Typo3 v14), there seems to be no way any more to
        // provide a fluid template other than by providing file paths, see:
        // https://github.com/TYPO3/typo3/blob/7c1c619d28d997d32a33446d066e25904c2c893c/typo3/sysext/extbase/Classes/Mvc/Controller/ActionController.php#L533-L560
        $reflectionClass = new ReflectionClass($controller);
        $reflectionMethod = $reflectionClass->getProperty('defaultViewObjectName');
        $reflectionMethod->setValue($controller, "something");

        /** @var AbstractController $controller */
        $viewResolverMock = $this->getMockBuilder(GenericViewResolver::class)
            ->disableOriginalConstructor()->getMock();
        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);

        $controller->injectDocumentRepository($documentRepository);
        $controller->injectViewResolver($viewResolverMock);
        $controller->setSettingsForTest($settings);
        return $controller;
    }
}
