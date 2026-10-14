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

namespace Kitodo\Dlf\Tests\Unit\Controller;

use Kitodo\Dlf\Controller\SearchController;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use ReflectionMethod;
use ReflectionProperty;

class SearchControllerTest extends UnitTestCase
{
    #[Test]
    public function addCollectionsQueryMergesTwoJoinedCollectionsWhenSearching(): void
    {
        $prefix = '{!join from=uid to=uid}';
        $controller = $this->createController(
            [
                1 => $this->createCollection('a', $prefix . '(record_id:856399094)'),
                2 => $this->createCollection('b', $prefix . '(record_id:776857304)'),
            ]
        );

        self::assertSame(
            '{!join from=uid to=uid}((record_id:856399094) OR (record_id:776857304))',
            $this->invoke($controller, 'term')
        );
    }

    #[Test]
    public function addCollectionsQueryStripsJoinWhenBrowsing(): void
    {
        $prefix = '{!join from=uid to=uid}';
        $controller = $this->createController([
            1 => $this->createCollection('a', $prefix . '(record_id:856399094)'),
            2 => $this->createCollection('b', $prefix . '(record_id:776857304)'),
        ]);

        // Plain browsing (no query) drops the join and uses the combined
        // reference query only.
        self::assertSame(
            '(((record_id:856399094)) OR ((record_id:776857304)))',
            $this->invoke($controller, '')
        );
    }

    /**
     * @param array<int, Collection> $collections
     */
    private function createController(array $collections): SearchController
    {
        $controller = $this->getMockBuilder(SearchController::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->createMock(CollectionRepository::class);
        $repository->method('findByUid')->willReturnCallback(
            static fn ($uid) => $collections[(int) $uid] ?? null
        );
        $this->inject($controller, SearchController::class, 'collectionRepository', $repository);
        $this->inject($controller, SearchController::class, 'search', ['collection' => implode(',', array_keys($collections))]);

        return $controller;
    }

    private function createCollection(string $label, string $indexSearch): Collection
    {
        $collection = new Collection();
        $collection->setLabel($label);
        $collection->setIndexSearch($indexSearch);
        return $collection;
    }

    private function invoke(SearchController $controller, string $query): string
    {
        $method = new ReflectionMethod(SearchController::class, 'addCollectionsQuery');
        return $method->invoke($controller, $query);
    }

    private function inject(object $object, string $declaringClass, string $propertyName, mixed $value): void
    {
        $property = new ReflectionProperty($declaringClass, $propertyName);
        $property->setValue($object, $value);
    }
}
