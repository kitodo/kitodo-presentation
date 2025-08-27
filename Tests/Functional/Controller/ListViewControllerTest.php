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

class ListViewControllerTest extends AbstractControllerTestCase
{
    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
        __DIR__ . '/../../Fixtures/Controller/solrcores.csv'
    ];

    private static array $solrFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.solr.json'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
        $this->setUpSolr(self::$solrCoreId, self::$storagePid, self::$solrFixtures);
    }

    /**
     * @test
     * @group action
     */
    public function canMainAction(): void
    {
        $arguments = [
            'search' => [
                'query' => '10 Keyboard pieces',
            ]
        ];
        $settings = [
            'solrcore' => self::$solrCoreId,
            'storagePid' => self::$storagePid,
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $templateHtml = '<html xmlns:v="http://typo3.org/ns/FluidTYPO3/Vhs/ViewHelpers">
                <f:spaceless>
                uniqueId-length: <v:count.bytes>{viewData.uniqueId}</v:count.bytes>
                page: {page}
                double: {viewData.requestData.double}
                lastSearch.query: {lastSearch.query}
                numResults: {numResults}
                </f:spaceless>
            </html>';
        $request = $this->setUpRequest('main', [], $arguments);
        $controller = $this->setUpController(ListViewController::class, $settings, $templateHtml);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html xmlns:v="http://typo3.org/ns/FluidTYPO3/Vhs/ViewHelpers">
                uniqueId-length: 13
                page: 1
                double: 0
                lastSearch.query: 10 Keyboard pieces
                numResults: 1
            </html>';
        $this->assertEquals($expected, $actual);
    }
}
