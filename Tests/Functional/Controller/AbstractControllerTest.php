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

use Kitodo\Dlf\Common\Solr;
use Kitodo\Dlf\Controller\AbstractController;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Fluid\View\StandaloneView;


abstract class AbstractControllerTest extends FunctionalTestCase
{

    protected function setUpData($databaseFixtures): void
    {
        foreach ($databaseFixtures as $filePath) {
            $this->importDataSet($filePath);
        }
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $this->initializeRepository(DocumentRepository::class, 0);
    }

    protected function setUpSolr($uid, $storagePid, $solrFixtures)
    {
        $this->solrCoreRepository = $this->initializeRepository(SolrCoreRepository::class, $storagePid);

        // Setup Solr only once for all tests in this suite
        static $solr = null;

        if ($solr === null) {
            $coreName = Solr::createCore();
            $solr = Solr::getInstance($coreName);
            foreach ($solrFixtures as $filePath) {
                $this->importSolrDocuments($solr, $filePath);
            }
        }

        $coreModel = $this->solrCoreRepository->findByUid($uid);
        $coreModel->setIndexName($solr->core);
        $this->solrCoreRepository->update($coreModel);
        $this->persistenceManager->persistAll();
    }

    protected function setUpRequest($actionName, $arguments = []): Request
    {
        $request = new Request();
        $request->setControllerActionName($actionName);
        $request->setArguments($arguments);
        return $request;
    }

    protected function setUpController($class, $settings, $templateHtml = ''): AbstractController
    {
        $view = new StandaloneView();
        $view->setTemplateSource($templateHtml);

        $controller = $this->get($class);
        $viewResolverMock = $this->getMockBuilder( GenericViewResolver::class)
            ->disableOriginalConstructor()->getMock();
        $viewResolverMock->expects(self::once())->method('resolve')->willReturn($view);
        $controller->injectViewResolver($viewResolverMock);
        $controller->setSettingsForTest($settings);
        return $controller;
    }

    protected function getResponse(): Response
    {
        return $this->objectManager->get(Response::class);
    }

}
