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
use TYPO3\CMS\Core\Localization\LanguageService;

class FeedsControllerTest extends AbstractControllerTest
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
        $GLOBALS['LANG'] = LanguageService::create('default');
        $settings = [
            'solrcore' => 4,
            'collections' => '1',
            'limit' => 1
        ];
        $templateHtml = '<html><f:for each="{documents}" as="document" iteration="iterator">
            {document.uid} – {document.title}</f:for>
            feedMeta:<f:count subject="{feedMeta}"/>
        </html>';
        $controller = $this->setUpController(FeedsController::class, $settings, $templateHtml);
        $arguments = [
            'collection' => '1'
        ];
        $request = $this->setUpRequest('main', $arguments);
        $response = $this->getResponse();

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>
            1003 – NEW: 6 Fugues - Go. S. 317
            feedMeta:0
        </html>';
        $this->assertEquals($expected, $actual);
    }
}
