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

use Kitodo\Dlf\Controller\CollectionController;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;

class CollectionControllerTest extends AbstractControllerTestCase
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
     */
    public function canListAction()
    {
        $settings = [
            'solrcore' => self::$solrCoreId,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $templateHtml = '<html><f:for each="{collections}" as="item">{item.collection.indexName}</f:for></html>';
        $controller = $this->setUpController(CollectionController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('list', [ 'tx_dlf' => ['id' => 1] ]);

        $response = $controller->processRequest($request);
        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html>test-collection</html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canListActionForwardToShow()
    {
        $settings = [
            'solrcore' => self::$solrCoreId,
            'collections' => '1',
            'randomize' => ''
        ];
        $controller = $this->setUpController(CollectionController::class, $settings);
        $request = $this->setUpRequest('list', ['tx_dlf' => ['id' => 1] ]);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $response = $controller->processRequest($request);
        $this->assertEquals(303, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function canShowAction()
    {
        $settings = [
            'solrcore' => self::$solrCoreId,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => '',
            'storagePid' => self::$storagePid
        ];
        $templateHtml = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"><f:for each="{documents.solrResults.documents}" as="page" iteration="docIterator">{page.title},</f:for></html>';

        $controller = $this->setUpController(CollectionController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('show', [], ['collection' => '1' ]);

        $response = $controller->processRequest($request);
        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">10 Keyboard pieces - Go. S. 658,Beigefügte Quellenbeschreibung,</html>';
        $this->assertEquals($expected, $actual);

    }

    /**
     * @test
     */
    public function canShowSortedAction()
    {
        $settings = [
            'solrcore' => self::$solrCoreId,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $controller = $this->setUpController(CollectionController::class, $settings);
        $request = $this->setUpRequest('showSorted');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $response = $controller->processRequest($request);
        $this->assertEquals(303, $response->getStatusCode());
    }
}
