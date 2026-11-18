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

use Kitodo\Dlf\Controller\SearchController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\SystemEnvironmentBuilder;

class SearchControllerTest extends AbstractControllerTestCase
{
    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Controller/pages.csv',
        __DIR__ . '/../../Fixtures/Controller/documents.csv',
        __DIR__ . '/../../Fixtures/Controller/solrcores.csv'
    ];

    private static array $solrFixtures = [
        __DIR__ . '/../../Fixtures/Controller/documents.solr.json'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
        $this->setUpSolr(self::$solrCoreId, self::$storagePid, self::$solrFixtures);
    }

    #[Test]
    public function canMainAction()
    {
        $queryParameters = [
            'tx_dlf' => [
                'id' => 1001
            ],
            'tx_dlf_listview' => [
                'searchParameter' => []
            ]
        ];
        $arguments = [
            'search' => [
                'dateFrom' => '1800'
            ]
        ];
        $settings = [
            'storagePid' => self::$storagePid,
            'solrcore' => self::$solrCoreId,
            'extendedFields' => 'field1,field2,field3',
            'extendedSlotCount' => 1
        ];
        $templateHtml = '<html>
            lastSearch:{lastSearch -> f:format.json() -> f:format.raw()}
            currentDocument:{currentDocument.uid}
            searchFields:<f:for each="{searchFields}" as="field">{field},</f:for>
        </html>';

        $uniqueSessionId = StringUtility::getUniqueId('test');
        $currentTime = time();

        // Main session backend setup
        $userSession = UserSession::createNonFixated($uniqueSessionId);
        $userSessionManagerMock = $this->createMock(UserSessionManager::class);
        $userSessionManagerMock->method('createFromRequestOrAnonymous')->withAnyParameters()->willReturn($userSession);
        $userSessionManagerMock->method('createAnonymousSession')->withAnyParameters()->willReturn($userSession);

        // new session should be written
        $sessionRecord = [
            'ses_id' => 'newSessionId',
            'ses_iplock' => '',
            'ses_userid' => 0,
            'ses_tstamp' => $currentTime,
            'ses_data' => 'a:1:{s:3:"foo";s:3:"bar";}',
            'ses_permanent' => 0,
        ];
        $userSessionToBePersisted = UserSession::createFromRecord($uniqueSessionId, $sessionRecord, true);
        $userSessionToBePersisted->set('foo', 'bar');

        $controller = $this->setUpController(SearchController::class, $settings, $templateHtml);

        $request = $this->setUpRequest('main', $queryParameters, $arguments);
        $feUser = new FrontendUserAuthentication();
        $feUser->initializeUserSessionManager($userSessionManagerMock);
        $request = $request->withAttribute("frontend.user", $feUser);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html>
            lastSearch:{"dateFrom":"1800","dateTo":"*"}
            currentDocument:1001
            searchFields:field1,field2,field3,
        </html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Return different search parameters with default (empty) values, which 
     * all should lead to the same facet array as if only the title is provided.
     *
     * @return array<string, array{0: array<string, string|string[]>}>
     */
    public static function provideSearchParametersForFacetsMenu(): array
    {
        return [
            // only the main search parameter
            'title only' => [
                ['title' => '10 Keyboard pieces'],
            ],
            // the full text switch and empty dates
            'metadata search with empty dates' => [
                ['title' => '10 Keyboard pieces', 'fulltext' => '0', 'dateFrom' => '', 'dateTo' => ''],
            ],
            // empty extended search slots
            'metadata search with empty extended search slots' => [
                [
                    'title' => '10 Keyboard pieces',
                    'extOperator' => ['AND', 'AND'],
                    'extField' => ['title', 'title'],
                    'extQuery' => ['', ''],
                ],
            ],
        ];
    }

    /**
     * Check that facets menu can be built.
     * 
     * @param array<string, string|string[]> $search
     */
    #[DataProvider('provideSearchParametersForFacetsMenu')]
    #[Test]
    public function canMakeFacetsMenuArray(array $search)
    {
        $queryParameters = [
            'tx_dlf' => [
                'id' => 1001
            ],
            'tx_dlf_listview' => [
                'searchParameter' => []
            ]
        ];
        $arguments = [
            'search' => $search,
            'query' => '*'
        ];
        $settings = [
            'storagePid' => self::$storagePid,
            'solrcore' => self::$solrCoreId,
            'facets' => 'type',
            'facetCollections' => '1'
        ];
        $templateHtml = '<html>
            lastSearch:{lastSearch -> f:format.json() -> f:format.raw()}
            currentDocument:{currentDocument.uid}
            facetsMenu:<f:for each="{facetsMenu}" as="menuEntry">
                {menuEntry.field}
                <f:for each="{menuEntry._SUB_MENU}" as="subMenuEntry"> {subMenuEntry.title}: {subMenuEntry.queryColumn.0}</f:for></f:for>
        </html>';

        $uniqueSessionId = StringUtility::getUniqueId('test');
        $currentTime = time();

        // Main session backend setup
        $userSession = UserSession::createNonFixated($uniqueSessionId);
        $userSessionManagerMock = $this->createMock(UserSessionManager::class);
        $userSessionManagerMock->method('createFromRequestOrAnonymous')->withAnyParameters()->willReturn($userSession);
        $userSessionManagerMock->method('createAnonymousSession')->withAnyParameters()->willReturn($userSession);

        // new session should be written
        $sessionRecord = [
            'ses_id' => 'newSessionId',
            'ses_iplock' => '',
            'ses_userid' => 0,
            'ses_tstamp' => $currentTime,
            'ses_data' => 'a:1:{s:3:"foo";s:3:"bar";}',
            'ses_permanent' => 0,
        ];
        $userSessionToBePersisted = UserSession::createFromRecord($uniqueSessionId, $sessionRecord, true);
        $userSessionToBePersisted->set('foo', 'bar');

        $controller = $this->setUpController(SearchController::class, $settings, $templateHtml);

        $request = $this->setUpRequest('main', $queryParameters, $arguments);
        $feUser = new FrontendUserAuthentication();
        $feUser->initializeUserSessionManager($userSessionManagerMock);
        $request = $request->withAttribute("frontend.user", $feUser);

        $response = $controller->processRequest($request);

        $response->getBody()->rewind();
        $actual = $response->getBody()->getContents();
        $expected = '<html>
            lastSearch:' . json_encode($search) . '
            currentDocument:1001
            facetsMenu:
                type
                 other: -type:page manuscript: -type:page
        </html>';
        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function canSearchAction()
    {
        $settings = [
            'storagePid' => self::$storagePid,
        ];
        $controller = $this->setUpController(SearchController::class, $settings, '');
        $request = $this->setUpRequest('search');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $response = $controller->processRequest($request);
        $this->assertEquals(303, $response->getStatusCode());
    }
}
