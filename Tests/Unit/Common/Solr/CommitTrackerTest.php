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

use Kitodo\Dlf\Common\Solr\CommitTracker;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CommitTrackerTest extends UnitTestCase
{
    /**
     * Register the given number of documents and return for each whether it has to be added with a commit.
     *
     * @param CommitTracker $commitTracker
     * @param int $count
     *
     * @return bool[]
     */
    private function addDocuments(CommitTracker $commitTracker, int $count): array
    {
        $commits = [];
        for ($i = 0; $i < $count; $i++) {
            $commits[] = $commitTracker->addDocument();
        }
        return $commits;
    }

    #[Test]
    public function commitsEveryDocumentByDefault(): void
    {
        $commitTracker = new CommitTracker(1, 1);

        self::assertSame([true, true, true], $this->addDocuments($commitTracker, 3));
        self::assertSame(0, $commitTracker->getUncommittedDocuments());
    }

    #[Test]
    public function commitsEveryNthDocument(): void
    {
        $commitTracker = new CommitTracker(1, 3);

        self::assertSame([false, false, true, false, false, true, false], $this->addDocuments($commitTracker, 7));
        self::assertSame(1, $commitTracker->getUncommittedDocuments());
    }

    #[Test]
    public function commitRemainingDoesNothingWithoutUncommittedDocuments(): void
    {
        // The Solr core with UID 1 does not exist in unit tests, so committing would fail if it was attempted.
        $commitTracker = new CommitTracker(1, 2);
        $this->addDocuments($commitTracker, 4);

        self::assertTrue($commitTracker->commitRemaining());
    }

    #[Test]
    public function rejectsNonPositiveNumberOfDocumentsPerCommit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CommitTracker(1, 0);
    }
}
