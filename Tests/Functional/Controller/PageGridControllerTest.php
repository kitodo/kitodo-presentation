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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
     */
    #[Test]
    public function canMainAction()
    {
        $settings = [
            'storagePid' => self::$storagePid
        ];

        $templateHtml = '<html>
            pageGridEntries:<f:count subject="{paginator.paginatedItems}"/>
            pageGridEntries[0]:{paginator.paginatedItems.0.pagination}, {paginator.paginatedItems.0.thumbnail}
            pageGridEntries[1]:{paginator.paginatedItems.1.pagination}, {paginator.paginatedItems.1.thumbnail}
            docUid:{docUid}
        </html>';
        $controller = $this->setUpController(PageGridController::class, $settings, $templateHtml);
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

    /**
     * Renders the real PageGrid template (not a reduced inline template) and asserts that the
     * error-handling markup introduced for failing thumbnails is present: a hidden fallback
     * placeholder image and, for each page that has a thumbnail, an <img onerror> handler plus a
     * hidden localized error message.
     */
    #[Test]
    public function rendersErrorHandlingMarkupForThumbnails()
    {
        $settings = [
            'storagePid' => self::$storagePid,
            'action' => 'main'
        ];

        $templatePath = ExtensionManagementUtility::extPath('dlf') . 'Resources/Private/Templates/PageGrid/Main.html';
        $view = $this->setUpTemplateView($templatePath);
        $controller = $this->setUpController(PageGridController::class, $settings, '', $view);
        $request = $this->setUpTemplateRequest('main', ['tx_dlf' => ['id' => 2001]]);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();

        // A single hidden fallback placeholder image is emitted once for the whole grid.
        $this->assertMatchesRegularExpression(
            '/<img[^>]*id="tx-dlf-pagegrid-fallback"[^>]*PageGridPlaceholder\.jpg/',
            $actual
        );
        // Every page that has a thumbnail renders an onerror handler that points at the fallback.
        $this->assertStringContainsString(
            "onerror=\"this.onerror=null;var f=document.getElementById('tx-dlf-pagegrid-fallback');",
            $actual
        );
        // ... and a hidden localized error message element.
        $this->assertStringContainsString(
            'id="tx-dlf-pagegrid-error-1" class="tx-dlf-pagegrid-error"',
            $actual
        );
        $this->assertStringContainsString(
            'The page image could not be loaded because the image server is unavailable.',
            $actual
        );
    }
}
