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

    /**
     * @test
     */
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
            'solrcore' => self::$solrCoreId,
            'storagePid' => self::$storagePid,
            'extendedFields' => 'field1,field2,field3',
            'extendedSlotCount' => 1
        ];
        $templateHtml = '<html>
            lastSearch:<f:for each="{lastSearch}" as="searchEntry" key="key">{key}:{searchEntry},</f:for>
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
            lastSearch:dateFrom:1800,dateTo:NOW,
            currentDocument:1001
            searchFields:field1,field2,field3,
        </html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canMakeFacetsMenuArray()
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
                'title' => '10 Keyboard pieces'
            ],
            'query' => '*'
        ];
        $settings = [
            'solrcore' => self::$solrCoreId,
            'storagePid' => self::$storagePid,
            'facets' => 'type',
            'facetCollections' => '1'
        ];
        $templateHtml = '<html>
            lastSearch:<f:for each="{lastSearch}" as="searchEntry" key="key">{key}:{searchEntry},</f:for>
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
            lastSearch:title:10 Keyboard pieces,
            currentDocument:1001
            facetsMenu:
                type
                 other: -type:page manuscript: -type:page
        </html>';
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canSearchAction()
    {
        $controller = $this->setUpController(SearchController::class, [], '');
        $request = $this->setUpRequest('search');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $response = $controller->processRequest($request);
        $this->assertEquals(303, $response->getStatusCode());
    }
}
