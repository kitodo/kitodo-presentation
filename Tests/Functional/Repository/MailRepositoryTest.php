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

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use Kitodo\Dlf\Domain\Repository\MailRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class MailRepositoryTest extends FunctionalTestCase
{
    /**
     * @var MailRepository
     */
    protected $mailRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->mailRepository = $this->initializeRepository(
            MailRepository::class,
            20000
        );

        $this->importDataSet(__DIR__ . '/../../Fixtures/Repository/mail.xml');
    }

    /**
     * @test
     * @group find
     */
    public function canFindAllWithPid(): void
    {
        $mails = $this->mailRepository->findAllWithPid(30000);
        $this->assertNotNull($mails);
        $this->assertInstanceOf(QueryResult::class, $mails);

        $mailByLabel = [];
        foreach ($mails as $mail) {
            $mailByLabel[$mail->getLabel()] = $mail;
        }

        $this->assertEquals(2, $mails->count());
        $this->assertArrayHasKey('Mail-Label-1', $mailByLabel);
        $this->assertArrayHasKey('Mail-Label-2', $mailByLabel);
    }
}
