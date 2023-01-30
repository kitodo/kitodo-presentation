<?php

namespace Kitodo\Dlf\Tests\Functional\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use Kitodo\Dlf\Domain\Repository\MailRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class MailRepsitoryTest extends FunctionalTestCase
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
