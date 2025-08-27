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

class StatisticsControllerTest extends AbstractControllerTestCase
{

    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
        __DIR__ . '/../../Fixtures/Controller/solrcores.csv',
        __DIR__ . '/../../Fixtures/Controller/documents.csv'
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
            'collections' => '1',
            'storagePid' => self::$storagePid,
            'description' => 'There are ###TITLES### and ###VOLUMES###.'
        ];
        $templateHtml = '<html>{content}</html>';

        $request = $this->setUpRequest('main');
        $controller = $this->setUpController(StatisticsController::class, $settings, $templateHtml);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html>There are 3 titles and 3 volumes.</html>';
        $this->assertEquals($expected, $actual);
    }
}
