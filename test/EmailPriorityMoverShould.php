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
     * @param string $senderAddress
     * @param $emailId
     * @dataProvider provideEmailDetails
     */
    public function move_emails_from_whitelisted_senders_to_inbox(string $senderAddress, $emailId): void
    {
        $emailFromWhiteListedSender = new Email(new EmailId($emailId), new EmailAddress($senderAddress));

        $emailRepository = $this->createMock(EmailRepository::class);

        $emailRepository
            ->method('fetchFrom')
            ->willReturn([ $emailFromWhiteListedSender ]);

        $emailRepository->expects($this->once())
            ->method('updateEmail')
            ->with($this->equalTo($emailFromWhiteListedSender))
            ;

        $emailPriorityMover = new EmailPriorityMover($emailRepository);
        $emailPriorityMover->fillInbox();

        $this->assertContains(self::INBOX, $emailFromWhiteListedSender->getLabels());
    }

    public function provideEmailDetails(): array
    {
        return [
            [ "1", "mchojrin@gmail.com" ],
        ];
    }
}
