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

use Kitodo\Dlf\Controller\BasketController;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class BasketControllerTest extends AbstractControllerTestCase
{
    private const SESSION_ID = 'basket-test-session';

    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.csv',
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
        __DIR__ . '/../../Fixtures/Controller/solrcores.csv'
    ];

    private static array $settings = [
        'storagePid' => 2,
        'pdfgenerate' => 'https://pdf.example.org/generate/',
        'pdfparams' => '##docId##,##startpage##,##endpage##,##startx##,##starty##,##endx##,##endy##,##rotation##',
        'pdfparamseparator' => '*',
        'targetBasket' => 1,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
    }

    #[Test]
    public function canAddEntries(): void
    {
        // single page from the page view
        $response = $this->processBasketRequest('add', [
            'addToBasket' => '1', 'id' => '1001', 'startpage' => '5', 'endpage' => '5',
            'startX' => '', 'startY' => '', 'endX' => '', 'endY' => '', 'rotation' => ''
        ]);
        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals(['1001_5_5'], array_keys($this->getStoredEntries()));

        // identical entry is only added once
        $this->processBasketRequest('add', [
            'addToBasket' => '1', 'id' => '1001', 'startpage' => '5', 'endpage' => '5'
        ]);
        $this->assertEquals(['1001_5_5'], array_keys($this->getStoredEntries()));

        // cutout of a page
        $this->processBasketRequest('add', [
            'addToBasket' => '1', 'id' => '1001', 'startpage' => '7', 'endpage' => '7',
            'startX' => '100', 'startY' => '200', 'endX' => '400', 'endY' => '500', 'rotation' => '0'
        ]);
        // whole document from the list view
        $this->processBasketRequest('add', [
            'addToBasket' => 'list', 'id' => '1001', 'startpage' => '1'
        ]);
        // structure element from the table of contents (LOG_0005 covers pages 3-7)
        $this->processBasketRequest('add', [
            'addToBasket' => 'toc', 'id' => '1001', 'startpage' => '3', 'logId' => 'LOG_0005'
        ]);

        $entries = $this->getStoredEntries();
        $this->assertEquals(['1001_5_5', '1001_7_7_100_400', '1001_1_76', '1001_3_7'], array_keys($entries));
        $this->assertEquals(
            ['id' => '1001', 'startpage' => 7, 'endpage' => 7, 'startX' => 100, 'startY' => 200, 'endX' => 400, 'endY' => 500, 'rotation' => 0],
            $entries['1001_7_7_100_400']
        );
        $this->assertEquals(76, $entries['1001_1_76']['endpage']);
        $this->assertEquals(7, $entries['1001_3_7']['endpage']);
    }

    #[Test]
    public function canIgnoreAddingMissingDocument(): void
    {
        $this->processBasketRequest('add', ['addToBasket' => '1', 'id' => '999999', 'startpage' => '1']);
        $this->assertEquals([], $this->getStoredEntries());
    }

    #[Test]
    public function canListEntries(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Controller/basket.csv');

        $templateHtml = '<html>{countDocs}
<f:for each="{entries}" as="entry">{entry.key}: {entry.BASKETDATA.title} ({entry.BASKETDATA.info}) {entry.BASKETDATA.downloadUrl}
</f:for></html>';
        $response = $this->processBasketRequest('main', [], $templateHtml);

        $response->getBody()->rewind();
        $expected = '<html>3
1001_1_76: 10 Keyboard pieces - Go. S. 658 (Page 1-76) https://pdf.example.org/generate/oai:de:slub-dresden:db:id-476251419,1,76,,,,,
1001_5_5: 10 Keyboard pieces - Go. S. 658 (Page 5) https://pdf.example.org/generate/oai:de:slub-dresden:db:id-476251419,5,,,,,
1001_7_7_100_400: 10 Keyboard pieces - Go. S. 658 (Cutout Page 7) https://pdf.example.org/generate/oai:de:slub-dresden:db:id-476251419,7,100,200,400,500,0
</html>';
        $this->assertEquals($expected, $response->getBody()->getContents());
    }

    #[Test]
    public function canListEmptyBasket(): void
    {
        $response = $this->processBasketRequest('main', [], '<html>{countDocs}<f:for each="{entries}" as="entry">{entry.key}</f:for></html>');

        $response->getBody()->rewind();
        $this->assertEquals('<html>0</html>', $response->getBody()->getContents());
    }

    #[Test]
    public function canRemoveEntries(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Controller/basket.csv');

        // unchecked entries only contain an empty value as id
        $response = $this->processBasketRequest('basket', [
            'basket_action' => 'remove',
            'selected' => [
                '1001_1_76' => ['id' => '1001', 'startpage' => '1', 'endpage' => '76', 'startX' => '', 'endX' => ''],
                '1001_5_5' => ['id' => '', 'startpage' => '5', 'endpage' => '5', 'startX' => '', 'endX' => ''],
                '1001_7_7_100_400' => ['id' => '1001', 'startpage' => '7', 'endpage' => '7', 'startX' => '100', 'endX' => '400'],
            ],
        ]);
        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals(['1001_5_5'], array_keys($this->getStoredEntries()));

        // removing the last entry leaves an empty basket
        $this->processBasketRequest('basket', [
            'basket_action' => 'remove',
            'selected' => [
                '1001_5_5' => ['id' => '1001', 'startpage' => '5', 'endpage' => '5', 'startX' => '', 'endX' => ''],
            ],
        ]);
        $this->assertEquals([], $this->getStoredEntries());
        $this->assertSame('', $this->getStoredDocIds());
    }

    #[Test]
    public function canIgnoreRemovingFromMissingBasket(): void
    {
        $response = $this->processBasketRequest('basket', [
            'basket_action' => 'remove',
            'selected' => ['1001_5_5' => ['id' => '1001', 'startpage' => '5', 'endpage' => '5']],
        ]);
        $this->assertEquals(303, $response->getStatusCode());
        $this->assertFalse($this->getStoredDocIds());
    }

    /**
     * Process a request of an anonymous frontend user and persist the changes
     */
    private function processBasketRequest(string $action, array $arguments, string $templateHtml = '<html></html>'): ResponseInterface
    {
        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $frontendUser->method('getSession')->willReturn(UserSession::createNonFixated(self::SESSION_ID));
        $frontendUser->method('getUserId')->willReturn(null);

        $controller = $this->setUpController(BasketController::class, self::$settings, $templateHtml);
        $request = $this->setUpTemplateRequest($action, [], $arguments)
            ->withAttribute('frontend.user', $frontendUser);

        $response = $controller->processRequest($request);

        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        return $response;
    }

    /**
     * @return string|false the stored basket entries (JSON) or false if there is no basket
     */
    private function getStoredDocIds(): string|false
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tx_dlf_basket')
            ->select(['doc_ids'], 'tx_dlf_basket', ['session_id' => self::SESSION_ID, 'pid' => self::$storagePid, 'deleted' => 0])
            ->fetchOne();
    }

    private function getStoredEntries(): array
    {
        return json_decode((string) $this->getStoredDocIds(), true) ?? [];
    }
}
