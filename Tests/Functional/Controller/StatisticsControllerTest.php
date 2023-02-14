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

use Kitodo\Dlf\Controller\StatisticsController;
use TYPO3\CMS\Core\Localization\LanguageService;

class StatisticsControllerTest extends AbstractControllerTest {

    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/pages.xml',
        __DIR__ . '/../../Fixtures/Controller/solrcores.xml',
        __DIR__ . '/../../Fixtures/Controller/documents.xml'
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
            'storagePid' => '0',
            'description' => 'There are ###TITLES### and ###VOLUMES###.'
        ];
        $templateHtml = '<html>{content}</html>';

        $request = $this->setUpRequest('main');
        $response = $this->getResponse();
        $controller = $this->setUpController(StatisticsController::class, $settings, $templateHtml);

        $controller->processRequest($request, $response);
        $actual = $response->getContent();
        $expected = '<html>There are 3 titles and 3 volumes.</html>';
        $this->assertEquals($expected, $actual);
    }
}
