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

use Kitodo\Dlf\Controller\AudioPlayerController;
use TYPO3\CMS\Core\Http\Response;

class AudioPlayerControllerTest extends AbstractControllerTest
{
    static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents_local.csv',
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
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
        $_POST['tx_dlf'] = [
            'id'=> 2001,
        ];
        $templateHtml = 'This template should be returned.';
        $controller = $this->setUpController(AudioPlayerController::class, [], $templateHtml);
        $request = $this->setUpRequest('main');

        if (explode('.', TYPO3_version)[0] === '10') {
            $response = $this->objectManager->get(Response::class);
            $controller->processRequest($request, $response);
            $actual = $response->getContent();
        } else {
            $response = $controller->processRequest($request);
            $actual = $response->getBody()->getContents();
        }
        $expected = 'This template should be returned.';
        $this->assertEquals($expected, $actual);
    }
}