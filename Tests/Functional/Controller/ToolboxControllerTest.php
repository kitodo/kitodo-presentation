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

use Kitodo\Dlf\Controller\ToolboxController;

class ToolboxControllerTest extends AbstractControllerTest
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
    public function canFulltextdownloadtool()
    {
        $_POST['tx_dlf'] = [
            'id' => 2002,
            'page' => '2'
        ];
        $settings = [
            'tools' => 'tx_dlf_fulltextdownloadtool'
        ];
        $templateHtml = '<html>fulltextDownload:{fulltextDownload}</html>';
        $controller = $this->setUpController(ToolboxController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>fulltextDownload:1</html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canFulltexttool()
    {
        $_POST['tx_dlf'] = [
            'id' => 2002,
            'page' => '2'
        ];
        $settings = [
            'tools' => 'tx_dlf_fulltexttool',
            'activateFullTextInitially' => 1
        ];
        $templateHtml = '<html>fulltext:{fulltext},activateFullTextInitially:{activateFullTextInitially}</html>';
        $controller = $this->setUpController(ToolboxController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>fulltext:1,activateFullTextInitially:1</html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canImagedownloadtool()
    {
        $_POST['tx_dlf'] = [
            'id' => 2002,
            'double' => 1,
            'page' => 1
        ];
        $settings = [
            'tools' => 'tx_dlf_imagedownloadtool',
            'fileGrpsImageDownload' => 'MAX'
        ];
        $templateHtml = '<html>imageDownload:<f:for each="{imageDownload}" as="image">
            {image.url}{image.mimetypeLabel}</f:for>
        </html>';
        $controller = $this->setUpController(ToolboxController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>imageDownload:
            http://web:8001/Tests/Fixtures/Controller/mets_local/jpegs/00000001.tif.large.jpg (JPG)
            http://web:8001/Tests/Fixtures/Controller/mets_local/jpegs/00000002.tif.large.jpg (JPG)
        </html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canImagemanipulationtool()
    {
        $_POST['tx_dlf'] = [
            'id' => 2002,
            'page' => '2'
        ];
        $settings = [
            'tools' => 'tx_dlf_imagemanipulationtool',
            'parentContainer' => '.parent-container'
        ];
        $templateHtml = '<html>imageManipulation:{imageManipulation},parentContainer:{parentContainer}</html>';
        $controller = $this->setUpController(ToolboxController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>imageManipulation:1,parentContainer:.parent-container</html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canMainAction()
    {
        $_POST['tx_dlf'] = [
            'id' => 1001,
            'double' => 1
        ];
        $settings = [
            'solrcore' => 4,
            'library' => 1,
            'tools' => 'tx_dlf_annotationtool',
            'limit' => 1
        ];
        $templateHtml = '<html>double:{double}</html>';
        $controller = $this->setUpController(ToolboxController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>double:1</html>';
        $this->assertEquals($expected, $actual);

    }

    /**
     * @test
     */
    public function canPdfdownloadtool()
    {
        $_POST['tx_dlf'] = [
            'id' => 2002,
            'page' => 1,
            'double' => 1
        ];
        $settings = [
            'tools' => 'tx_dlf_pdfdownloadtool'
        ];
        $templateHtml = '<html>pageLinks:<f:for each="{pageLinks}" as="link">
            {link}</f:for>
            workLink:{workLink}
        </html>';
        $controller = $this->setUpController(ToolboxController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>pageLinks:
            http://web:8001/Tests/Fixtures/Controller/mets_local/jpegs/00000001.tif.pdf
            http://web:8001/Tests/Fixtures/Controller/mets_local/jpegs/00000002.tif.pdf
            workLink:http://web:8001/Tests/Fixtures/Controller/mets_local/jpegs/full.pdf
        </html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canSearchindocumenttool()
    {
        $_POST['tx_dlf'] = [
            'id' => 2002,
            'page' => 1
        ];
        $settings = [
            'solrcore' => 4,
            'tools' => 'tx_dlf_searchindocumenttool',
            'queryInputName' => 'queryInputName',
            'startInputName' => 'startInputName',
            'idInputName' => 'idInputName',
            'pageInputName' => 'pageInputName',
            'highlightWordInputName' => 'highlightWordInputName',
            'encryptedInputName' => 'encryptedInputName',
            'documentIdUrlSchema' => 'https://host.de/items/*id*/record',
        ];
        $templateHtml = '<html>
            LABEL_QUERY_URL:{searchInDocument.LABEL_QUERY_URL}
            LABEL_START:{searchInDocument.LABEL_START}
            LABEL_ID:{searchInDocument.LABEL_ID}
            LABEL_PAGE_URL:{searchInDocument.LABEL_PAGE_URL}
            LABEL_HIGHLIGHT_WORD:{searchInDocument.LABEL_HIGHLIGHT_WORD}
            LABEL_ENCRYPTED:{searchInDocument.LABEL_ENCRYPTED}
            CURRENT_DOCUMENT:{searchInDocument.CURRENT_DOCUMENT}
        </html>';
        $controller = $this->setUpController(ToolboxController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
            LABEL_QUERY_URL:queryInputName
            LABEL_START:startInputName
            LABEL_ID:idInputName
            LABEL_PAGE_URL:pageInputName
            LABEL_HIGHLIGHT_WORD:highlightWordInputName
            LABEL_ENCRYPTED:encryptedInputName
            CURRENT_DOCUMENT:2002
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
