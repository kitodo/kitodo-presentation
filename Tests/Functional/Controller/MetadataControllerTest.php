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

use Kitodo\Dlf\Controller\MetadataController;

class MetadataControllerTest extends AbstractControllerTest
{
    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.xml',
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
        $settings = [
            'solrcore' => 4
        ];
        $templateHtml = '<html>
            mets_label:<f:for each="{documentMetadataSections}" as="section"><f:for each="{section.mets_label}" as="entry">{entry}</f:for></f:for>
        </html>';
        $_POST['tx_dlf'] = ['id' => 1001];

        $controller = $this->setUpController(MetadataController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main');
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
            mets_label:10 Keyboard pieces - Go. S. 658
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
