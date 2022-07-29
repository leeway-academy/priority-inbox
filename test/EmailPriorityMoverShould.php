<?php

use PHPUnit\Framework\TestCase;
use PriorityInbox\Email;
use PriorityInbox\EmailAddress;
use PriorityInbox\EmailId;
use PriorityInbox\EmailPriorityMover;
use PriorityInbox\EmailRepository;

class EmailPriorityMoverShould extends TestCase
{
    const INBOX = "INBOX";

    /**
     * @test
     * @param $emailId
     * @param string $senderAddress
     * @dataProvider provideEmailDetails
     */
    public function move_emails_from_whitelisted_senders_to_inbox(EmailId $emailId, EmailAddress $senderAddress): void
    {
        $emailFromWhiteListedSender = new Email($emailId, $senderAddress);

        $emailRepository = $this->createMock(EmailRepository::class);

        $emailRepository
            ->method('fetchFrom')
            ->willReturn([$emailFromWhiteListedSender]);

        $emailRepository->expects($this->once())
            ->method('updateEmail')
            ->with($this->equalTo($emailFromWhiteListedSender));

        $emailPriorityMover = new EmailPriorityMover($emailRepository);
        $emailPriorityMover->fillInbox();

        $this->assertContains(self::INBOX, $emailFromWhiteListedSender->getLabels());
    }

    public function provideEmailDetails(): array
    {
        return [
            [new EmailId("1"), new EmailAddress("mchojrin@gmail.com")],
            [new EmailId("2"), new EmailAddress("maria.pappen@gmail.com")],
        ];
    }
}
