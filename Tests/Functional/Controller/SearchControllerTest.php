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

use Kitodo\Dlf\Controller\SearchController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class SearchControllerTest extends AbstractControllerTest
{
    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/pages.xml',
        __DIR__ . '/../../Fixtures/Controller/documents.xml',
        __DIR__ . '/../../Fixtures/Controller/solrcores.xml'
    ];

    static array $solrFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.solr.json'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
        $this->setUpSolr(4, 0, self::$solrFixtures);
    }

    /**
     * @test
     */
    public function canMainAction()
    {
        $_POST['tx_dlf'] = [
            'id' => 1001
        ];
        $_POST['tx_dlf_listview'] = [
            'searchParameter' => []
        ];
        $arguments = [
            'searchParameter' => [
                'dateFrom' => '1800'
            ],
            '@widget_0' => [
                'currentPage' => 3
            ]
        ];
        $settings = [
            'solrcore' => 4,
            'extendedFields' => 'field1,field2,field3',
            'extendedSlotCount' => 1
        ];
        $templateHtml = '<html>
            widgetPage.currentPage:{widgetPage.currentPage}
            lastSearch:<f:for each="{lastSearch}" as="searchEntry" key="key">{key}:{searchEntry},</f:for>
            currentDocument:{currentDocument.uid}
            searchFields:<f:for each="{searchFields}" as="field">{field},</f:for>
        </html>';
        $controller = $this->setUpController(SearchController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main', $arguments);
        $response = $this->getResponse();
        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
            widgetPage.currentPage:3
            lastSearch:dateFrom:1800,dateTo:NOW,
            currentDocument:1001
            searchFields:field1,field2,field3,
        </html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canMakeFacetsMenuArray()
    {
        $_POST['tx_dlf'] = [
            'id' => 1001
        ];
        $_POST['tx_dlf_listview'] = [
            'searchParameter' => []
        ];
        $arguments = [
            'searchParameter' => [
                'title' => '10 Keyboard pieces'
            ],
            'query' => '*',
            '@widget_0' => [
                'currentPage' => 3
            ]
        ];
        $settings = [
            'solrcore' => 4,
            'facets' => 'type',
            'facetCollections' => '1'
        ];
        $templateHtml = '<html>
            widgetPage.currentPage:{widgetPage.currentPage}
            lastSearch:<f:for each="{lastSearch}" as="searchEntry" key="key">{key}:{searchEntry},</f:for>
            currentDocument:{currentDocument.uid}
            facetsMenu:<f:for each="{facetsMenu}" as="menuEntry">
                {menuEntry.field}
                <f:for each="{menuEntry._SUB_MENU}" as="subMenuEntry"> {subMenuEntry.title}: {subMenuEntry.queryColumn.0}</f:for></f:for>
        </html>';
        $controller = $this->setUpController(SearchController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main', $arguments);
        $response = $this->getResponse();
        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
            widgetPage.currentPage:3
            lastSearch:title:10 Keyboard pieces,
            currentDocument:1001
            facetsMenu:
                type
                 other: type_faceting:(&quot;other&quot;) manuscript: type_faceting:(&quot;manuscript&quot;)
        </html>';
        $this->assertEquals($expected, $actual);

    }

    /**
     * @test
     */
    public function canSearchAction()
    {
        $controller = $this->setUpController(SearchController::class, [], '');
        $request = $this->setUpRequest('search', []);
        $response = $this->getResponse();

        $this->expectException(StopActionException::class);
        $controller->processRequest($request, $response);
    }
}
