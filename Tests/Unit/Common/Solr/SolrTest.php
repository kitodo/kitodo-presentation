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

namespace Kitodo\Dlf\Tests\Unit\Common\Solr;

use Kitodo\Dlf\Common\Solr\Solr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SolrTest extends UnitTestCase
{
    #[DataProvider('provideCombineJoinedCollections')]
    #[Test]
    public function combineJoinedCollectionsBuildsSingleJoinedClause(string $expected, array $joinedQueries): void
    {
        self::assertSame($expected, Solr::combineJoinedCollections($joinedQueries));
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function provideCombineJoinedCollections(): array
    {
        $joinA = '{!join from=uid to=uid}(record_id:856399094)';
        $joinB = '{!join from=uid to=uid}(record_id:776857304)';

        return [
            'empty' => [
                '',
                [],
            ],
            'single join stays unchanged' => [
                $joinA,
                [$joinA],
            ],
            'two joins with same prefix are merged' => [
                '{!join from=uid to=uid}((record_id:856399094) OR (record_id:776857304))',
                [$joinA, $joinB],
            ],
            'joins with different prefix merge into one join (superset)' => [
                '{!join from=uid to=uid}((record_id:856399094) OR (record_id:776857304))',
                [$joinA, '{!join from=partof to=uid}(record_id:776857304)'],
            ],
        ];
    }

    #[Test]
    public function splitJoinLocalParameters(): void
    {
        self::assertSame(
            ['{!join from=uid to=uid}', '(record_id:123)'],
            Solr::splitJoinLocalParameters('{!join from=uid to=uid}(record_id:123)')
        );
        self::assertSame(
            ['', 'record_id:123'],
            Solr::splitJoinLocalParameters('record_id:123')
        );
        self::assertSame(
            ['{!join from=uid to=uid}', '(record_id:a) OR (type:issue)'],
            Solr::splitJoinLocalParameters('{!join from=uid to=uid} (record_id:a) OR (type:issue)')
        );
    }
}
