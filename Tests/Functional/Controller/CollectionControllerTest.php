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
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class CollectionControllerTest extends AbstractControllerTest {

    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
        __DIR__ . '/../../Fixtures/Controller/solrcores.csv'
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
    public function canListAction()
    {
        $settings = [
            'solrcore' => $this->currentCoreName,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $templateHtml = '<html><f:for each="{collections}" as="item">{item.collection.indexName}</f:for></html>';
        $subject = $this->setUpController(CollectionController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('list', ['id' => 1]);

        $response = $subject->processRequest($request);

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
            'solrcore' => $this->currentCoreName,
            'collections' => '1',
            'randomize' => ''
        ];
        $subject = $this->setUpController(CollectionController::class, $settings);
        $request = $this->setUpRequest('list', ['id' => 1]);

        $this->expectException(StopActionException::class);
        $response = $subject->processRequest($request);
    }

    /**
     * @test
     */
    public function canShowAction()
    {
        $settings = [
            'solrcore' => $this->currentCoreName,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => '',
            'storagePid' => 0
        ];
        $templateHtml = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"><f:for each="{documents.solrResults.documents}" as="page" iteration="docIterator">{page.title},</f:for></html>';

        $subject = $this->setUpController(CollectionController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('show', ['collection' => '1']);

        $response = $subject->processRequest($request);
        $actual = $response->getBody()->getContents();
        $expected = '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">10 Keyboard pieces - Go. S. 658,</html>';
        $this->assertEquals($expected, $actual);

    }

    /**
     * @test
     */
    public function canShowSortedAction()
    {
        $settings = [
            'solrcore' => $this->currentCoreName,
            'collections' => '1',
            'dont_show_single' => 'some_value',
            'randomize' => ''
        ];
        $subject = $this->setUpController(CollectionController::class, $settings);
        $request = $this->setUpRequest('showSorted');

        $this->expectException(StopActionException::class);
        $response = $subject->processRequest($request);
    }
}
