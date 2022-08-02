<?php

namespace PriorityInbox\Providers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PriorityInbox\Email;
use PriorityInbox\EmailAddress;
use PriorityInbox\EmailId;
use PriorityInbox\LabelFilter;
use PriorityInbox\Label;

class GmailRepositoryShould extends TestCase
{
    const A_LABEL = "A label";
    const LABEL_ID = "abcd";
    private GmailRepository $gmailRepository;
    private GmailService $gmailService;

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
        $emailId = new EmailId("1");
        $sender = new EmailAddress("asasas");

        $this->gmailService
            ->expects($this->once())
            ->method('modifyMessage')
            ->with($this->equalTo($emailId))
        ;

        $email = new Email($emailId, $sender, new DateTimeImmutable());

        $this
            ->gmailRepository
            ->updateEmail($email);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->gmailService = $this->createMock(GmailService::class);
        $this->gmailRepository = new GmailRepository($this->gmailService);
    }
}
