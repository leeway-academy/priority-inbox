<?php

use PHPUnit\Framework\TestCase;
use PriorityInbox\Email;
use PriorityInbox\EmailAddress;
use PriorityInbox\EmailId;
use PriorityInbox\EmailPriorityMover;
use PriorityInbox\EmailRepository;
use PriorityInbox\Label;
use PriorityInbox\LabelFilter;

class EmailPriorityMoverShould extends TestCase
{
    const INBOX = "INBOX";
    const HIDDEN_EMAILS = "HIDDEN_EMAILS";
    private EmailRepository $emailRepository;
    private EmailPriorityMover $emailPriorityMover;

    /**
     * @test
     * @param EmailId $emailId
     * @param EmailAddress $sender
     * @dataProvider provideEmailDetails
     */
    public function label_emails_from_whitelisted_senders_with_inbox(EmailId $emailId, EmailAddress $sender): void
    {
        $emailFromWhiteListedSender = new Email($emailId, $sender);
        $this->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromWhiteListedSender]);

        $this->emailRepository->expects($this->once())
            ->method('updateEmail')
            ->with($this->equalTo($emailFromWhiteListedSender));

        $this->emailPriorityMover->addAllowedSender($sender);

        $this->emailPriorityMover->fillInbox();

        $this->assertContains(self::INBOX, array_map(fn (Label $label) => $label->value(), $emailFromWhiteListedSender->getLabels()));
    }

    /**
     * @test
     * @param EmailId $emailId
     * @param EmailAddress $sender
     * @dataProvider provideEmailDetails
     */
    public function remove_hidden_label_from_emails_sent_by_whitelisted_senders(EmailId $emailId, EmailAddress $sender): void
    {
        $emailFromWhiteListedSender = new Email($emailId, $sender);
        $this->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromWhiteListedSender]);
        $this->emailRepository->expects($this->once())
            ->method('updateEmail')
            ->with($this->equalTo($emailFromWhiteListedSender));

        $this->emailPriorityMover->addAllowedSender($sender);

        $this->emailPriorityMover->fillInbox();

        $this->assertNotContains(self::HIDDEN_EMAILS, array_map(fn (Label $label) => $label->value(), $emailFromWhiteListedSender->getLabels()));
    }

    /**
     * @test
     */
    public function start_with_hidden_emails_only(): void
    {
        $this
            ->emailRepository
            ->expects($this->once())
            ->method('addFilter')
            ->with($this->equalTo(new LabelFilter(new Label(self::HIDDEN_EMAILS))))
            ;

        $this->emailPriorityMover->fillInbox();
    }

    public function provideEmailDetails(): array
    {
        return [
            [new EmailId("1"), new EmailAddress("mchojrin@gmail.com")],
            [new EmailId("2"), new EmailAddress("maria.pappen@gmail.com")],
        ];
    }

    protected function setUp() : void
    {
        parent::setUp();
        $this->emailRepository = $this->createMock(EmailRepository::class);
        $this->emailPriorityMover = new EmailPriorityMover($this->emailRepository, new Label(self::HIDDEN_EMAILS));
    }
}
