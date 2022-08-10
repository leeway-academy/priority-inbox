<?php

namespace PriorityInbox;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @todo Refactor: the tests should be:
 *  - it will respect the whitelist
 *  - it will respect the blacklist
 *  - it will respect the timeframe
 */
class EmailPriorityMoverShould extends TestCase
{
    const INBOX_LABEL_ID = "INBOX";
    const HIDDEN_EMAILS_LABEL_ID = "HIDDEN_EMAILS_ID";

    private EmailRepository $emailRepository;
    private EmailPriorityMover $emailPriorityMover;
    private LoggerInterface $logger;

    /**
     * @test
     * @param EmailId $emailId
     * @param Sender $sender
     * @dataProvider provideEmailDetails
     * @throws Exception
     */
    public function move_emails_from_allowed_senders(EmailId $emailId, Sender $sender): void
    {
        $emailFromAllowedSender = new Email($emailId, $sender, new DateTimeImmutable());
        $inboxLabel = new Label(self::INBOX_LABEL_ID);
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID);

        $emailFromAllowedSender
            ->addLabel($hiddenLabel)
            ;

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

        $labelsAfter = $emailFromAllowedSender->labels();
        $this->assertContainsEquals($inboxLabel, $labelsAfter);
        $this->assertNotContainsEquals($hiddenLabel, $labelsAfter);
    }

    /**
     * @test
     * @param EmailId $emailId
     * @param Sender $sender
     * @dataProvider provideEmailDetails
     * @throws Exception
     */
    public function not_move_emails_not_sent_by_allowed_senders(EmailId $emailId, Sender $sender): void
    {
        $inboxLabel = new Label(self::INBOX_LABEL_ID);
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID);

        $emailFromNotAllowedSender = new Email($emailId, new Sender($sender . "a"), new DateTimeImmutable());
        $emailFromNotAllowedSender->addLabel($hiddenLabel);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromNotAllowedSender]);

        $this
            ->emailPriorityMover
            ->addAllowedSender($sender)
            ->fillInbox();

        $labelsAfter = $emailFromNotAllowedSender->labels();

        $this->assertNotContainsEquals($inboxLabel, $labelsAfter);
        $this->assertContainsEquals($hiddenLabel, $labelsAfter);
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
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID);
        $emailFromNotAllowedSender->addLabel($hiddenLabel);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromNotAllowedSender]);

        $this
            ->emailPriorityMover
            ->addAllowedSender($sender)
            ->fillInbox();

        $this->assertContainsEquals($hiddenLabel, $emailFromNotAllowedSender->labels());
    }

    /**
     * @test
     */
    public function move_hidden_emails_only(): void
    {
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID);
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
     * @throws Exception
     */
    public function respect_black_list(EmailId $emailId, Sender $sender): void
    {
        $inboxLabel = new Label(self::INBOX_LABEL_ID);
        $hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID);

        $emailFromBlackListedSender = new Email($emailId, $sender, new DateTimeImmutable());
        $emailFromBlackListedSender->addLabel($hiddenLabel);

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromBlackListedSender]);

        $this
            ->emailPriorityMover
            ->addNotAllowedSender($sender)
            ->fillInbox();

        $labelsAfter = $emailFromBlackListedSender->labels();

        $this->assertNotContainsEquals($inboxLabel, $labelsAfter);
        $this->assertContainsEquals($hiddenLabel, $labelsAfter);
    }

    /**
     * @test
     * @throws Exception
     */
    public function allow_dry_runs(): void
    {
        $sender = new Sender("Mauro");
        $email = new Email(new EmailId("1"), $sender, new DateTimeImmutable("now - 2 day"));

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$email])
            ;

        $this
            ->emailRepository
            ->expects($this->never())
            ->method('updateEmail')
            ;

        $this
            ->emailPriorityMover
            ->addAllowedSender($sender)
            ->setDryRun(true)
            ;

        $this
            ->emailPriorityMover
            ->fillInbox();
    }
    /**
     * @test
     * @param int $minDelay
     * @return void
     * @throws Exception
     * @dataProvider provideDelays
     */
    public function respect_minimum_delay(int $minDelay): void
    {
        $movedEmail = new Email(new EmailId("1"), new Sender("sender@domain.com"), new DateTimeImmutable("now -" . $minDelay . " hours"));
        $notMovedEmail = new Email(new EmailId("2"), new Sender("sender@domain.com"), new DateTimeImmutable());

        $labelInbox = new Label(self::INBOX_LABEL_ID);

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
     * @throws Exception
     */
    public function update_emails_in_repository(EmailId $emailId, Sender $sender): void
    {
        $email = new Email($emailId, $sender, new DateTimeImmutable("now -2 hours"));
        $email
            ->addLabel(new Label(self::HIDDEN_EMAILS_LABEL_ID));

        $this->emailRepository
            ->method('fetch')
            ->willReturn([$email]);

        $this
            ->emailRepository
            ->expects($this->once())
            ->method('updateEmail')
            ->with($this->equalTo($email));

        $this
            ->emailPriorityMover
            ->fillInbox();
    }

    /**
     * @test
     * @throws Exception
     */
    public function use_logger(): void
    {
        $this->logger
            ->expects($this->atLeast(1))
            ->method('info')
            ;

        $this
            ->emailPriorityMover
            ->fillInbox();
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
            [1],
            [2],
            [3],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailRepository = $this
            ->createMock(EmailRepository::class);
        $this->logger = $this
            ->createMock(LoggerInterface::class);
        $this->emailPriorityMover = new EmailPriorityMover(
            $this->emailRepository,
            new Label(self::HIDDEN_EMAILS_LABEL_ID),
            $this->logger
        );
    }
}