<?php

namespace PriorityInbox;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EmailPriorityMoverShould extends TestCase
{
    public const INBOX_LABEL_ID = "INBOX";
    public const HIDDEN_EMAILS_LABEL_ID = "HIDDEN_EMAILS_ID";

    private EmailRepository $emailRepository;
    private EmailPriorityMover $emailPriorityMover;
    private LoggerInterface $logger;
    private $inboxLabel;
    private $hiddenLabel;

    /**
     * @test
     * @param EmailId $emailId
     * @param Sender $sender
     * @dataProvider provideEmailDetails
     * @throws Exception
     */
    public function respect_white_list(EmailId $emailId, Sender $sender): void
    {
        $emailFromAllowedSender = $this->createMock(Email::class);

        $emailFromAllowedSender
            ->method('sender')
            ->willReturn($sender)
        ;

        $emailFromAllowedSender
            ->expects($this->once())
            ->method('addLabel')
            ->with($this->equalTo($this->inboxLabel));

        $emailFromAllowedSender
            ->expects($this->once())
            ->method('removeLabel')
            ->with($this->equalTo($this->hiddenLabel));

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromAllowedSender]);

        $this
            ->emailPriorityMover
            ->addAllowedSenderPattern(new SenderPattern($sender->value()))
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

        $emailFromBlackListedSender = $this->createMock(Email::class);

        $emailFromBlackListedSender
            ->method('sender')
            ->willReturn($sender)
        ;

        $emailFromBlackListedSender
            ->expects($this->never())
            ->method('addLabel')
            ->with($this->equalTo($inboxLabel))
        ;

        $emailFromBlackListedSender
            ->expects($this->never())
            ->method('removeLabel')
            ->with($this->equalTo($hiddenLabel))
        ;

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$emailFromBlackListedSender]);

        $this
            ->emailPriorityMover
            ->addNotAllowedSenderPattern(new SenderPattern($sender->value()))
            ->fillInbox()
        ;
    }

    /**
     * @test
     * @throws Exception
     */
    public function move_every_email_if_no_blacklist_nor_whitelist_exist(): void
    {
        $firstEmail = $this->createMock(Email::class);
        $firstEmail
            ->expects($this->once())
            ->method('addLabel')
            ->with($this->equalTo($this->inboxLabel))
        ;
        $firstEmail
            ->expects($this->once())
            ->method('removeLabel')
            ->with($this->equalTo($this->hiddenLabel))
        ;

        $secondEmail = $this->createMock(Email::class);
        $secondEmail
            ->expects($this->once())
            ->method('addLabel')
            ->with($this->equalTo($this->inboxLabel))
        ;
        $secondEmail
            ->expects($this->once())
            ->method('removeLabel')
            ->with($this->equalTo($this->hiddenLabel))
        ;

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$firstEmail, $secondEmail]);

        $this
            ->emailPriorityMover
            ->fillInbox();
    }

    /**
     * @test
     * @throws Exception
     */
    public function move_hidden_emails_only(): void
    {
        $filterByHiddenLabel = new LabelFilter($this->hiddenLabel);

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
     * @throws Exception
     */
    public function allow_dry_runs(): void
    {
        $sender = new Sender("Mauro");
        $email = new Email(new EmailId("1"), $sender, new DateTimeImmutable("now - 2 day"));

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$email]);

        $this
            ->emailRepository
            ->expects($this->never())
            ->method('updateEmail')
        ;

        $this
            ->emailPriorityMover
            ->setDryRun(true);

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

        $this
            ->emailRepository
            ->method('fetch')
            ->willReturn([$movedEmail, $notMovedEmail]);

        $this
            ->emailPriorityMover
            ->setMinimumDelay($minDelay)
            ->fillInbox();

        $this->assertContainsEquals($this->inboxLabel, $movedEmail->labels());
        $this->assertNotContainsEquals($this->inboxLabel, $notMovedEmail->labels());
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
            ->method('info');

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
        $this->inboxLabel = new Label(self::INBOX_LABEL_ID);
        $this->hiddenLabel = new Label(self::HIDDEN_EMAILS_LABEL_ID);
    }
}
