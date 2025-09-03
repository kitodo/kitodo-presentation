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

class MetadataControllerTest extends AbstractControllerTestCase
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
            'solrcore' => self::$solrCoreId,
            'storagePid' => self::$storagePid,
            'separator' => '#'
        ];
        $templateHtml = '<html>
            mets_label:<f:for each="{documentMetadataSections}" as="section"><f:for each="{section.mets_label}" as="entry">{entry}</f:for></f:for>
        </html>';

        $controller = $this->setUpController(MetadataController::class, $settings, $templateHtml);
        $request = $this->setUpRequest('main', ['tx_dlf' => ['id' => 1001] ]);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html>
            mets_label:10 Keyboard pieces - Go. S. 658
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
