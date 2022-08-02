<?php

namespace PriorityInbox\Providers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PriorityInbox\Email;
use PriorityInbox\EmailAddress;
use PriorityInbox\EmailId;
use PriorityInbox\EmailUpdate;
use PriorityInbox\Label;
use PriorityInbox\LabelFilter;

class GmailRepositoryShould extends TestCase
{
    const A_LABEL = "A label";
    const LABEL_ID = "abcd";
    const EMAIL_ID = "1";
    const SENDER_ADDRESS = "asasas";
    const LABEL_ADD_THIS = "ADD THIS";
    const LABEL_REMOVE_THIS = "REMOVE THIS";
    private GmailRepository $gmailRepository;
    private GmailDAO $gmailService;

    /**
     * @test
     */
    public function fetch_emails_from_gmail_using_filters(): void
    {
        $label = new Label(self::LABEL_ID, self::A_LABEL);
        $labelFilter = new LabelFilter($label);

        $this
            ->gmailService
            ->expects($this->once())
            ->method('getFilteredMessageList')
            ->with($this->equalTo([$labelFilter]))
            ;

        $this
            ->gmailRepository
            ->fetch([$labelFilter])
            ;
    }

    /**
     * @return void
     * @test
     */
    public function update_emails_in_gmail(): void
    {
        $emailId = new EmailId(self::EMAIL_ID);
        $sender = new EmailAddress(self::SENDER_ADDRESS);
        $sentAt = new DateTimeImmutable();


        $labelToAdd = new Label(self::LABEL_ADD_THIS, self::LABEL_ADD_THIS);
        $labelToRemove = new Label(self::LABEL_REMOVE_THIS, self::LABEL_REMOVE_THIS);

        $email = new Email($emailId, $sender, $sentAt, [$labelToRemove]);

        $emailUpdate = new EmailUpdate();
        $emailUpdate
            ->addLabel($labelToAdd)
            ->removeLabel($labelToRemove)
            ;

        $this->gmailService
            ->expects($this->once())
            ->method('modifyMessage')
            ->with(
                $this->equalTo($emailId),
                $this->equalTo($emailUpdate)
            )
        ;

        $email
            ->addLabel($labelToAdd)
            ->removeLabel($labelToRemove)
            ;

        $this
            ->gmailRepository
            ->updateEmail($email);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->gmailService = $this->createMock(GmailDAO::class);
        $this->gmailRepository = new GmailRepository($this->gmailService);
    }
}
