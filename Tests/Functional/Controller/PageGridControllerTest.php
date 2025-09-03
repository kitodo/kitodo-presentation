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

use Kitodo\Dlf\Controller\PageGridController;

class PageGridControllerTest extends AbstractControllerTestCase
{
    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents_local.csv',
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
        __DIR__ . '/../../Fixtures/Controller/solrcores.csv'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
    }

    /**
     * This test hard-codes the URL that is used to load the METS of document 2001 (see documents_local.csv).
     * It will fail unless the docker test environment is used with the proxy hosted at "web:8001".
     *
     * @test
     */
    public function canMainAction()
    {
        $templateHtml = '<html>
            pageGridEntries:<f:count subject="{paginator.paginatedItems}"/>
            pageGridEntries[0]:{paginator.paginatedItems.0.pagination}, {paginator.paginatedItems.0.thumbnail}
            pageGridEntries[1]:{paginator.paginatedItems.1.pagination}, {paginator.paginatedItems.1.thumbnail}
            docUid:{docUid}
        </html>';
        $controller = $this->setUpController(PageGridController::class, [], $templateHtml);
        $request = $this->setUpRequest('main', ['tx_dlf' => [ 'id' => 2001 ] ]);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html>
            pageGridEntries:2
            pageGridEntries[0]: - , http://example.com/mets_audio/jpegs/00000001.tif.thumbnail.jpg
            pageGridEntries[1]:1, http://example.com/mets_audio/jpegs/00000002.tif.thumbnail.jpg
            docUid:2001
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
