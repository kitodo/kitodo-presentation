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

use Kitodo\Dlf\Controller\BasketController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class BasketControllerTest extends AbstractControllerTest
{

    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/pages.xml',
        __DIR__ . '/../../Fixtures/Controller/solrcores.xml'
    ];

    static array $solrFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.solr.json'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
        $this->setUpSolr(4, 2, self::$solrFixtures);
    }

    /**
     * @test
     */
    public function canAddAction()
    {
        $controller = $this->setUpController(BasketController::class, []);
        $request = $this->setUpRequest('add');
        $response = $this->getResponse();
        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();
        $GLOBALS['TSFE']->fe_user->id = 1;

        $this->expectException(StopActionException::class);
        $controller->processRequest($request, $response);
    }

    /**
     * @test
     */
    public function canBasketAction()
    {
        $controller = $this->setUpController(BasketController::class, []);
        $request = $this->setUpRequest('add');
        $response = $this->getResponse();
        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();
        $GLOBALS['TSFE']->fe_user->id = 1;
        $this->expectException(StopActionException::class);
        $controller->processRequest($request, $response);
    }

    /**
     * @test
     */
    public function canMainAction()
    {
        $templateHtml = '<html>
            count:{countDocs}
            entries:<f:count subject="{entries}"/>
        </html>';
        $controller = $this->setUpController(BasketController::class, [], $templateHtml);
        $request = $this->setUpRequest('main', []);
        $response = $this->getResponse();
        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();
        $GLOBALS['TSFE']->fe_user->id = 1;

        $controller->processRequest($request, $response);
        $actual =  $response->getContent();
        $expected = '<html>
            count:0
            entries:0
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
