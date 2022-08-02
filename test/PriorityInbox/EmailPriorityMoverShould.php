<?php

namespace PriorityInbox;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;

class EmailPriorityMoverShould extends TestCase
{
    const INBOX_LABEL_ID = "INBOX";
    const INBOX_LABEL_VALUE = "INBOX";

    const HIDDEN_EMAILS_LABEL_VALUE = "HIDDEN_EMAILS";
    const HIDDEN_EMAILS_LABEL_ID = "HIDDEN_EMAILS_ID";

    private EmailRepository $emailRepository;
    private EmailPriorityMover $emailPriorityMover;

    /**
     * @test
     * @param EmailId $emailId
     * @param Sender $sender
     * @dataProvider provideEmailDetails
     */
    public function move_emails_from_allowed_senders(EmailId $emailId, Sender $sender): void
    {
        $emailFromAllowedSender = new Email($emailId, $sender, new DateTimeImmutable());
        $inboxLabel = new Label(self::INBOX_LABEL_ID, self::INBOX_LABEL_ID);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromAllowedSender]);

        $this
            ->emailPriorityMover
            ->addAllowedSender($sender)
            ->fillInbox()
        ;

        $this->assertContainsEquals($inboxLabel, $emailFromAllowedSender->labels());
    }

    /**
     * @test
     * @param EmailId $emailId
     * @param Sender $sender
     * @dataProvider provideEmailDetails
     */
    public function not_move_emails_not_sent_by_allowed_senders(EmailId $emailId, Sender $sender): void
    {
        $emailFromNotAllowedSender = new Email($emailId, new Sender($sender . "a"), new DateTimeImmutable());
        $inboxLabel = new Label(self::INBOX_LABEL_ID, self::INBOX_LABEL_ID);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromNotAllowedSender]);

        $this
            ->emailPriorityMover
            ->addAllowedSender($sender)
            ->fillInbox()
        ;

        $this->assertNotContainsEquals($inboxLabel, $emailFromNotAllowedSender->labels());
    }

    /**
     * @test
     * @param EmailId $emailId
     * @param Sender $sender
     * @dataProvider provideEmailDetails
     */
    public function remove_hidden_label_from_emails_sent_by_allowed_senders(EmailId $emailId, Sender $sender): void
    {
        $emailFromAllowedSender = new Email($emailId, $sender, new DateTimeImmutable());
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID, self::HIDDEN_EMAILS_LABEL_VALUE);

        $emailFromAllowedSender
            ->addLabel($hiddenLabel);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromAllowedSender])
        ;

        $this
            ->emailPriorityMover
            ->addAllowedSender($sender)
            ->fillInbox()
        ;

        $this->assertNotContainsEquals($hiddenLabel, $emailFromAllowedSender->labels());
    }

    /**
     * @test
     * @param EmailId $emailId
     * @param Sender $sender
     * @dataProvider provideEmailDetails
     */
    public function not_remove_hidden_label_from_emails_not_sent_by_allowed_senders(EmailId $emailId, Sender $sender): void
    {
        $emailFromNotAllowedSender = new Email($emailId, new Sender($sender . "a"), new DateTimeImmutable());
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID, self::HIDDEN_EMAILS_LABEL_VALUE);
        $emailFromNotAllowedSender->addLabel($hiddenLabel);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromNotAllowedSender])
        ;

        $this
            ->emailPriorityMover
            ->addAllowedSender($sender)
            ->fillInbox()
        ;

        $this->assertContainsEquals($hiddenLabel, $emailFromNotAllowedSender->labels());
    }

    /**
     * @test
     */
    public function move_hidden_emails_only(): void
    {
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID, self::HIDDEN_EMAILS_LABEL_VALUE);
        $filterByHiddenLabel = new LabelFilter($hiddenLabel);

        $this
            ->emailRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo([$filterByHiddenLabel]));

        $this
            ->emailPriorityMover
            ->fillInbox();
    }

    /**
     * @test
     * @dataProvider provideEmailDetails
     */
    public function respect_black_list(EmailId $emailId, Sender $sender): void
    {
        $emailFromBlackListedSender = new Email($emailId, $sender, new DateTimeImmutable());
        $inboxLabel = new Label(self::INBOX_LABEL_ID, self::INBOX_LABEL_ID);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromBlackListedSender]);

        $this
            ->emailPriorityMover
            ->addNotAllowedSender($sender)
            ->fillInbox()
        ;

        $this->assertNotContainsEquals($inboxLabel, $emailFromBlackListedSender->labels());
    }

    /**
     * @test
     * @param int $minDelay
     * @return void
     * @throws Exception
     * @dataProvider provideDelays
     */
    public function only_move_emails_sent_after_minimum_delay(int $minDelay): void
    {
        $movedEmail = new Email(new EmailId("1"), new Sender("sender@domain.com"), new DateTimeImmutable("now -" . $minDelay . " hours"));
        $notMovedEmail = new Email(new EmailId("2"), new Sender("sender@domain.com"), new DateTimeImmutable());

        $labelInbox = new Label(self::INBOX_LABEL_ID, self::INBOX_LABEL_VALUE);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$movedEmail, $notMovedEmail]);

        $this
            ->emailPriorityMover
            ->setMinimumDelay($minDelay)
            ->fillInbox();

        $this->assertContainsEquals($labelInbox, $movedEmail->labels());
        $this->assertNotContainsEquals($labelInbox, $notMovedEmail->labels());

    }

    /**
     * @test
     * @dataProvider provideEmailDetails
     */
    public function update_emails_in_repository(EmailId $emailId, Sender $sender): void
    {
        $email = new Email($emailId, $sender, new DateTimeImmutable("now -2 hours"));
        $email
            ->addLabel(new Label(self::HIDDEN_EMAILS_LABEL_ID, self::HIDDEN_EMAILS_LABEL_VALUE))
        ;

        $this->emailRepository
            ->method('fetch')
            ->willReturn([$email])
            ;

        $this
            ->emailRepository
            ->expects($this->once())
            ->method('updateEmail')
            ->with($this->equalTo($email))
            ;

        $this
            ->emailPriorityMover
            ->fillInbox()
        ;
    }
    /**
     * @return array[]
     */
    public function provideEmailDetails(): array
    {
        return [
            [new EmailId("1"), new Sender("mchojrin@gmail.com")],
            [new EmailId("2"), new Sender("maria.pappen@gmail.com")],
        ];
    }

    /**
     * @return int[][]
     */
    public function provideDelays(): array
    {
        return [
            [ 1 ],
            [ 2 ],
            [ 3 ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailRepository = $this->createMock(EmailRepository::class);
        $this->emailPriorityMover = new EmailPriorityMover($this->emailRepository, new Label(self::HIDDEN_EMAILS_LABEL_ID, self::HIDDEN_EMAILS_LABEL_VALUE));
    }
}