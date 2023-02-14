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

use Kitodo\Dlf\Controller\ListViewController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class ListViewControllerTest extends AbstractControllerTest
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
     * @group action
     */
    public function canMainAction(): void
    {
        $arguments = [
            'searchParameter' => [
                'query' => '10 Keyboard pieces',
            ]
        ];
        $settings = [
            'solrcore' => 4,
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $templateHtml = '<html xmlns:v="http://typo3.org/ns/FluidTYPO3/Vhs/ViewHelpers">
                <f:spaceless>
                uniqueId-length: <v:count.bytes>{viewData.uniqueId}</v:count.bytes>
                page: {viewData.requestData.page}
                double: {viewData.requestData.double}
                widgetPage: {widgetPage.currentPage}
                lastSearch.query: {lastSearch.query}
                numResults: {numResults}
                </f:spaceless>
            </html>';
        $request = $this->setUpRequest('main', $arguments);
        $controller = $this->setUpController(ListViewController::class, $settings, $templateHtml);
        $response =  $this->getResponse();
        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

        $controller->processRequest($request, $response);
        $actual =  $response->getContent();
        $expected = '<html xmlns:v="http://typo3.org/ns/FluidTYPO3/Vhs/ViewHelpers">
                uniqueId-length: 13
                page: 1
                double: 0
                widgetPage: 1
                lastSearch.query: 10 Keyboard pieces
                numResults: 1
            </html>';
        $this->assertEquals($expected, $actual);
    }
}
