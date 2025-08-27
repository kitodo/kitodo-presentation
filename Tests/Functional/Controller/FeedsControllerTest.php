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

use Kitodo\Dlf\Controller\FeedsController;

class FeedsControllerTest extends AbstractControllerTestCase
{
    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.csv',
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
        __DIR__ . '/../../Fixtures/Controller/solrcores.csv'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
    }

    /**
     * @test
     */
    public function canMainAction()
    {
        $settings = [
            'storagePid' => self::$storagePid,
            'solrcore' => self::$solrCoreId,
            'collections' => '1',
            'library' => 0,
            'limit' => 1
        ];
        $templateHtml = '<html><f:for each="{documents}" as="document" iteration="iterator">
            {document.uid} – {document.title}</f:for>
            feedMeta:<f:count subject="{feedMeta}"/>
        </html>';
        $controller = $this->setUpController(FeedsController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main', [], [ 'collection' => '1' ]);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html>
            1003 – NEW: 6 Fugues - Go. S. 317
            feedMeta:0
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
