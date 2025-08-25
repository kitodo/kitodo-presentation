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

use Kitodo\Dlf\Domain\Repository\TokenRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class TokenRepositoryTest extends FunctionalTestCase
{
    /**
     * @var TokenRepository
     */
    protected TokenRepository $tokenRepository;

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

        $this->tokenRepository = $this->initializeRepository(
            TokenRepository::class,
            20000
        );
    }

    /**
     * Cleans up after the test by removing the temporary CSV file.
     *
     * @access public
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        unlink(__DIR__ . '/../../Fixtures/Repository/tokenTemp.csv');
    }

    /**
     * @test
     * @group delete
     */
    public function deleteExpiredTokens(): void
    {
        $inputCsvFile = __DIR__ . '/../../Fixtures/Repository/token.csv';
        $outputCsvFile = __DIR__ . '/../../Fixtures/Repository/tokenTemp.csv';

        $inputCsvData = file_get_contents($inputCsvFile);
        $csvData = str_getcsv($inputCsvData, "\n");

        $expireTime = 3600;
        $i = 1;

        foreach ($csvData as $key => &$row) {
            if ($key > 1) {
                $columns = str_getcsv($row, ",");
                if ($i % 2 == 0) {
                    $columns[3] = time() - $expireTime - rand(10, 3600);
                } else {
                    $columns[3] = time() - $expireTime + rand(10, 3600);
                }
                $row = implode(",", $columns);
                $i++;
            }
        }

        $outputCsvData = implode("\n", $csvData);
        file_put_contents($outputCsvFile, $outputCsvData);

        $this->importCSVDataSet($outputCsvFile);
        $this->tokenRepository->deleteExpiredTokens($expireTime);

        $this->persistenceManager->persistAll();

        $tokens = $this->tokenRepository->findAll();

        self::assertEquals(2, $tokens->count());

        $tokenUids = [];
        foreach ($tokens as $token) {
            $tokenUids[$token->getUid()] = $token;
        }

        self::assertArrayHasKey('101', $tokenUids);
        self::assertArrayHasKey('103', $tokenUids);
    }
}
