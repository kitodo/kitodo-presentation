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
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BootstrapServiceTest extends FunctionalTestCase
{
    // Required by the site set of the demo sites (kitodo/presentation-demo)
    protected array $coreExtensionsToLoad = [
        'felogin',
        'fluid_styled_content',
    ];

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

    #[Test]
    public function canBootstrapShowcaseScenario(): void
    {
        $bootstrapService = $this->get(BootstrapService::class);

        // start bootstrapping of showcase scenario
        $pageUids = $bootstrapService->bootstrap('showcase', rtrim($this->baseUrl, '/'));

        // check that the RSS feed and OAI-PMH pages were created with their backend layouts
        foreach (['feeds' => 'pagets__Feeds', 'oai' => 'pagets__OaiPmh'] as $key => $backendLayout) {
            self::assertArrayHasKey($key, $pageUids, 'Page "' . $key . '" was not created.');
            $page = $this->getConnectionPool()->getConnectionForTable('pages')
                ->select(['backend_layout'], 'pages', ['uid' => $pageUids[$key]])
                ->fetchAssociative();
            self::assertSame($backendLayout, $page['backend_layout'] ?? null);
        }

        // check that the demo frontend user was created (records with "{record:<key>}" reference) with a hashed password
        $group = $this->getConnectionPool()->getConnectionForTable('fe_groups')
            ->select(['uid'], 'fe_groups', ['pid' => $pageUids['users'], 'title' => 'Demo users', 'deleted' => 0])
            ->fetchAssociative();
        self::assertIsArray($group, 'Demo user group was not created.');
        $user = $this->getConnectionPool()->getConnectionForTable('fe_users')
            ->select(['password', 'usergroup'], 'fe_users', ['pid' => $pageUids['users'], 'username' => 'demo', 'deleted' => 0])
            ->fetchAssociative();
        self::assertIsArray($user, 'Demo user was not created.');
        self::assertSame((string) $group['uid'], (string) $user['usergroup']);
        self::assertNotSame('Pass123!', $user['password'], 'Password of the demo user was not hashed.');

        // check that the login plugin uses the folder of the demo user
        $flexForm = $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->select(['pi_flexform'], 'tt_content', ['CType' => 'felogin_login', 'pid' => $pageUids['login'], 'deleted' => 0])
            ->fetchOne();
        $settings = GeneralUtility::xml2array((string) $flexForm)['data']['sDEF']['lDEF'] ?? [];
        self::assertSame((string) $pageUids['users'], $settings['settings.pages']['vDEF'] ?? null);

        // check that the site settings of the scenario were written with resolved placeholders
        $siteSettings = Yaml::parseFile(Environment::getConfigPath() . '/sites/kitodo-demo-showcase/settings.yaml');
        self::assertSame($pageUids['login'], $siteSettings['demo']['loginPid'] ?? null);
        self::assertSame($pageUids['storage'], $siteSettings['plugin']['tx_dlf']['persistence']['storagePid'] ?? null);

        // check that the title record of the multi-volume work was added by the ingest of its volumes
        $documents = $this->getConnectionPool()->getConnectionForTable('tx_dlf_documents')
            ->select(['uid', 'record_id', 'partof'], 'tx_dlf_documents', ['pid' => $pageUids['storage'], 'deleted' => 0])
            ->fetchAllAssociative();
        $documentsByRecordId = array_column($documents, null, 'record_id');
        self::assertArrayHasKey('kitodo-demo-encyclopedia', $documentsByRecordId, 'Title record of the multi-volume work was not added.');
        foreach (['kitodo-demo-encyclopedia-volume1', 'kitodo-demo-encyclopedia-volume2'] as $volume) {
            self::assertSame(
                (int) $documentsByRecordId['kitodo-demo-encyclopedia']['uid'],
                (int) ($documentsByRecordId[$volume]['partof'] ?? 0),
                'Volume "' . $volume . '" is not part of the multi-volume work.'
            );
        }

        // check that the RSS feed and OAI-PMH plugins use the library of the documents (placeholder "{library}")
        $libraryUid = (int) $this->getConnectionPool()->getConnectionForTable('tx_dlf_libraries')
            ->select(['uid'], 'tx_dlf_libraries', ['pid' => $pageUids['storage'], 'deleted' => 0])
            ->fetchOne();
        self::assertGreaterThan(0, $libraryUid);
        foreach (['dlf_feeds' => 'feeds', 'dlf_oaipmh' => 'oai'] as $cType => $key) {
            $flexForm = $this->getConnectionPool()->getConnectionForTable('tt_content')
                ->select(['pi_flexform'], 'tt_content', ['CType' => $cType, 'pid' => $pageUids[$key], 'deleted' => 0])
                ->fetchOne();
            $settings = GeneralUtility::xml2array((string) $flexForm)['data']['sDEF']['lDEF'] ?? [];
            self::assertSame((string) $libraryUid, $settings['settings.library']['vDEF'] ?? null, 'Library of "' . $cType . '" was not set.');
        }
    }
}
