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

namespace Kitodo\Dlf\Tests\Functional\Bootstrap;

use Kitodo\Dlf\Bootstrap\BootstrapService;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BootstrapServiceTest extends FunctionalTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        // create an authenticated admin user for backend requests
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Bootstrap/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect($backendUser));
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    #[Test]
    public function canBootstrapMinimalScenario(): void
    {
        $bootstrapService = $this->get(BootstrapService::class);

        // start bootstrapping of minimal scenario
        $pageUids = $bootstrapService->bootstrap('minimal', rtrim($this->baseUrl, '/'));

        // read all pages from database
        $pages = $this->getConnectionPool()->getConnectionForTable('pages')
            ->select(['uid', 'pid', 'doktype', 'is_siteroot', 'backend_layout'], 'pages', ['deleted' => 0])
            ->fetchAllAssociativeIndexed();

        // check that pages were created by bootstrapping
        self::assertEqualsCanonicalizing(['root', 'storage', 'results', 'viewer'], array_keys($pageUids));
        foreach ($pageUids as $key => $uid) {
            self::assertArrayHasKey($uid, $pages, 'Page "' . $key . '" was not created.');
        }

        // check that root page was set up with pid and settings
        $root = $pages[$pageUids['root']];
        self::assertSame(0, (int) $root['pid']);
        self::assertSame(1, (int) $root['is_siteroot']);
        self::assertSame('pagets__Home', $root['backend_layout']);

        // check that child pages are child of root page
        foreach (['storage', 'results', 'viewer'] as $key) {
            self::assertSame($pageUids['root'], (int) $pages[$pageUids[$key]]['pid'], 'Page "' . $key . '" is not a child of the root page.');
        }
    }
}
