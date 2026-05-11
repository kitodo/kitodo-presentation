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

namespace Kitodo\Dlf\Tests\Functional\Repository;

use Kitodo\Dlf\Domain\Repository\FormatRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

class FormatRepositoryTest extends FunctionalTestCase
{
    /**
     * @var FormatRepository
     */
    protected FormatRepository $formatRepository;

    /**
     * Sets up the test environment.
     *
     * @access public
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->formatRepository = $this->initializeRepository(
            FormatRepository::class,
            20000
        );

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Repository/metadata.csv');
    }

    /**
     * @test
     * @group find
     */
    public function canFindAll(): void
    {
        $formats = $this->formatRepository->findAll();
        self::assertNotNull($formats);
        self::assertInstanceOf(QueryResult::class, $formats);
        self::assertCount(2, $formats->toArray());

        $format = $formats->getFirst();
        self::assertEquals(5201, $format->getUid());
        self::assertEquals('ALTO', $format->getType());
    }
}
