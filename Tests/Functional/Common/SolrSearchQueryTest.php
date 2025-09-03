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

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\Solr\Solr;
use Kitodo\Dlf\Common\Solr\SolrSearch;
use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Domain\Repository\SolrCoreRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class SolrSearchQueryTest extends FunctionalTestCase
{
    private static array $databaseFixtures = [
        __DIR__ . '/../../Fixtures/Common/documents_1.csv',
        __DIR__ . '/../../Fixtures/Common/pages.csv',
        __DIR__ . '/../../Fixtures/Common/solrcores.csv'
    ];

    private static array $solrFixtures = [
        __DIR__ . '/../../Fixtures/Common/documents_1.solr.json'
    ];

    /**
     * Sets up the test environment.
     *
     * This method is called before each test method is executed.
     * It imports the necessary CSV datasets and sets up the Solr core for the tests.
     *
     * @access public
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpData(self::$databaseFixtures);
        $this->setUpSolr(4, 20000, self::$solrFixtures);
    }

    /**
     * @test
     */
    public function canExecute()
    {
        $documentRepository = $this->initializeRepository(DocumentRepository::class, 0);
        $settings = ['solrcore' => 4, 'storagePid' => 20000];

        // FIXME: test would fail because it is not possible to set $this->settings['storagePid'] for the
        // documentRepository used in DocumentRepository.php:502
        // as a workaround, call $documentRepository->findSolrWithoutCollection to register settings
        $documentRepository->findSolrWithoutCollection($settings, []);

        $params = ['query' => '10 Keyboard pieces'];
        $search = new SolrSearch($documentRepository, [], $settings, $params);
        $search->prepare();
        $solrSearchQuery = $search->getQuery();
        $result = $solrSearchQuery->execute();

        self::assertCount(1, $result);
        self::assertEquals(1, $solrSearchQuery->getLimit());
    }
}
