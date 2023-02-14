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

class PageGridControllerTest extends AbstractControllerTest
{
    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents_local.xml',
        __DIR__ . '/../../Fixtures/Controller/pages.xml',
        __DIR__ . '/../../Fixtures/Controller/solrcores.xml'
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
        $_POST['tx_dlf'] = ['id' => 2001];
        $settings = [];
        $templateHtml = '<html>
            pageGridEntries:<f:count subject="{pageGridEntries}"/>
            pageGridEntries[0]:{pageGridEntries.0.pagination}, {pageGridEntries.0.thumbnail}
            pageGridEntries[1]:{pageGridEntries.1.pagination}, {pageGridEntries.1.thumbnail}
            docUid:{docUid}
        </html>';
        $controller = $this->setUpController(PageGridController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
            pageGridEntries:2
            pageGridEntries[0]: - , http://example.com/mets_audio/jpegs/00000001.tif.thumbnail.jpg
            pageGridEntries[1]:1, http://example.com/mets_audio/jpegs/00000002.tif.thumbnail.jpg
            docUid:2001
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
