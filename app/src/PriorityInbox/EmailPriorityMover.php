<?php

namespace PriorityInbox;

use Exception;
use Psr\Log\LoggerInterface;

class EmailPriorityMover
{
    public const INBOX = "INBOX";
    private Label $hiddenLabel;
    private EmailRepository $emailRepository;
    /**
     * @var array <SenderPattern>
     */
    private array $allowedSenderPatterns = [];
    /**
     * @var array <SenderPattern>
     */
    private array $notAllowedSenderPatterns = [];
    private int $minimumDelay = 0;
    private bool $dryRun = false;
    private LoggerInterface $logger;

    /**
     * @param EmailRepository $emailRepository
     * @param Label $hiddenLabel
     * @param LoggerInterface $logger
     */
    public function __construct(EmailRepository $emailRepository, Label $hiddenLabel, LoggerInterface $logger)
    {
        $this->emailRepository = $emailRepository;
        $this->hiddenLabel = $hiddenLabel;
        $this->logger = $logger;
    }

    /**
     * @param SenderPattern $pattern
     * @return $this
     */
    public function addAllowedSenderPattern(SenderPattern $pattern): self
    {
        $this->allowedSenderPatterns[] = $pattern;

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function fillInbox(): void
    {
        $this
            ->getLogger()
            ->info('Fetching emails')
        ;

        $hiddenEmails = $this->fetchHiddenEmails();

        $this
            ->getLogger()
            ->info("Found ".count($hiddenEmails)." emails to process");
        foreach ($hiddenEmails as $hiddenEmail) {
            $this
                ->getLogger()
                ->debug("Found message coming from ".$hiddenEmail->sender()." sent ".$hiddenEmail->hoursSinceItWasSent()." hours ago");
            $this->moveToInboxIfMovable($hiddenEmail);
        }
    }

    /**
     * @param int $hours
     * @return $this
     */
    public function setMinimumDelay(int $hours): self
    {
        $this->minimumDelay = $hours;

        return $this;
    }

    /**
     * @param Email $email
     * @return void
     * @throws Exception
     */
    private function moveToInbox(Email $email): void
    {
        $this
            ->getLogger()
            ->info("Moving email from ".$email->sender()." sent ".$email->hoursSinceItWasSent()." hours ago to Inbox")
        ;
        $this
            ->getLogger()
            ->debug("Adding label INBOX");
        $email->addLabel(new Label(self::INBOX));
        $this
            ->getLogger()
            ->debug("Removing label ".$this->getHiddenLabel());
        $email->removeLabel($this->getHiddenLabel());
        $this
            ->getLogger()
            ->debug("Updating email in repository");
        $this->updateEmail($email);
    }

    /**
     * @return array<Email>
     */
    private function fetchHiddenEmails(): array
    {
        $this
            ->getLogger()
            ->debug("Fetching hidden emails");

        return $this
            ->fetchEmailsLabeled(
                $this->getHiddenLabel()
            );
    }

    /**
     * @return Label
     */
    private function getHiddenLabel(): Label
    {
        return $this->hiddenLabel;
    }

    /**
     * @param Label $label
     * @return array<Email>
     */
    private function fetchEmailsLabeled(Label $label): array
    {
        $this
            ->getLogger()
            ->debug("Fetching emails labeled ".$label->id())
            ;
            
        return $this
            ->getEmailRepository()
            ->fetch([new LabelFilter($label)]);
    }

    /**
     * @param Email $hiddenEmail
     * @return void
     * @throws Exception
     */
    private function moveToInboxIfMovable(Email $hiddenEmail): void
    {
        if ($this->shouldBeMoved($hiddenEmail)) {
            $this->moveToInbox($hiddenEmail);
        }
    }

    /**
     * @param Email $hiddenEmail
     * @return bool
     * @throws Exception
     */
    private function shouldBeMoved(Email $hiddenEmail): bool
    {
        return $this->wasSentByAllowedSender($hiddenEmail)
            && $this->wasSentWithinAcceptableTimeFrame($hiddenEmail)
            && !$this->wasSentByNotAllowedSender($hiddenEmail);
    }

    /**
     * @param Email $hiddenEmail
     * @return bool
     */
    private function wasSentByAllowedSender(Email $hiddenEmail): bool
    {
        return empty($this->allowedSenderPatterns) || $this->senderBelongsToWhiteList($hiddenEmail->sender());
    }

    /**
     * @param Email $hiddenEmail
     * @return bool
     */
    private function wasSentByNotAllowedSender(Email $hiddenEmail): bool
    {
        return !empty($this->notAllowedSenderPatterns) && $this->senderBelongsToBlackList($hiddenEmail->sender());
    }

    /**
     * @param Email $hiddenEmail
     * @return bool
     * @throws Exception
     */
    private function wasSentWithinAcceptableTimeFrame(Email $hiddenEmail): bool
    {
        return $hiddenEmail->hoursSinceItWasSent() >= $this->minimumDelay;
    }

    /**
     * @param SenderPattern $pattern
     * @return $this
     */
    public function addNotAllowedSenderPattern(SenderPattern $pattern): self
    {
        $this->notAllowedSenderPatterns[] = $pattern;

        return $this;
    }

    /**
     * @param Email $email
     * @return void
     */
    private function updateEmail(Email $email): void
    {
        if (!$this->dryRun) {
            $this->getEmailRepository()
                ->updateEmail($email);
        }
    }

    /**
     * @param bool $newValue
     * @return $this
     */
    public function setDryRun(bool $newValue): self
    {
        $this->dryRun = $newValue;

        return $this;
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param Sender $sender
     * @return bool
     */
    private function senderBelongsToWhiteList(Sender $sender): bool
    {
        return $this->senderBelongsToList($sender, $this->allowedSenderPatterns);
    }

    /**
     * @param Sender $sender
     * @return bool
     */
    private function senderBelongsToBlackList(Sender $sender): bool
    {
        return $this->senderBelongsToList($sender, $this->notAllowedSenderPatterns);
    }

    /**
     * @return EmailRepository
     */
    private function getEmailRepository(): EmailRepository
    {
        return $this
            ->emailRepository;
    }

    /**
     * @param Sender $sender
     * @param array $list
     * @return bool
     */
    private function senderBelongsToList(Sender $sender, array $list): bool
    {
        $match = array_filter($list, fn(SenderPattern $pattern) => $pattern->matches($sender));

        return !empty($match);
    }
}
