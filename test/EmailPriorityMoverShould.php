<?php

use PHPUnit\Framework\TestCase;
use PriorityInbox\Email;
use PriorityInbox\EmailAddress;
use PriorityInbox\EmailId;
use PriorityInbox\EmailPriorityMover;
use PriorityInbox\EmailRepository;
use PriorityInbox\Label;

class EmailPriorityMoverShould extends TestCase
{
    const INBOX = "INBOX";

    /**
     * @test
     * @param $emailId
     * @param string $sender
     * @dataProvider provideEmailDetails
     */
    public function move_emails_from_whitelisted_senders_to_inbox(EmailId $emailId, EmailAddress $sender): void
    {
        $emailFromWhiteListedSender = new Email($emailId, $sender);
        $emailRepository = $this->createMock(EmailRepository::class);
        $emailRepository
            ->method('fetchFrom')
            ->willReturn([$emailFromWhiteListedSender]);
        $emailRepository->expects($this->once())
            ->method('updateEmail')
            ->with($this->equalTo($emailFromWhiteListedSender));

        $emailPriorityMover = new EmailPriorityMover($emailRepository);
        $emailPriorityMover->addAllowedSender($sender);

        $emailPriorityMover->fillInbox();

        $this->assertContains(self::INBOX, array_map(fn (Label $label) => $label->value(), $emailFromWhiteListedSender->getLabels()));
    }

    public function provideEmailDetails(): array
    {
        return [
            [new EmailId("1"), new EmailAddress("mchojrin@gmail.com")],
            [new EmailId("2"), new EmailAddress("maria.pappen@gmail.com")],
        ];
    }
}
