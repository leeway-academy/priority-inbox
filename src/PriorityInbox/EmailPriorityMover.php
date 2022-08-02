<?php

namespace PriorityInbox;

use Exception;

class EmailPriorityMover
{
    const INBOX = "INBOX";
    private Label $hiddenLabel;
    private EmailRepository $emailRepository;
    /**
     * @var array <Sender>
     */
    private array $allowedSenders = [];
    /**
     * @var array <Sender>
     */
    private array $notAllowedSenders = [];
    private int $minimumDelay = 0;

    /**
     * @param EmailRepository $emailRepository
     * @param Label $hiddenLabel
     */
    public function __construct(EmailRepository $emailRepository, Label $hiddenLabel)
    {
        $this->emailRepository = $emailRepository;
        $this->hiddenLabel = $hiddenLabel;
    }

    /**
     * @param Sender $sender
     * @return $this
     */
    public function addAllowedSender(Sender $sender): self
    {
        $this->allowedSenders[] = $sender;

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function fillInbox(): void
    {
        foreach($this->fetchHiddenEmails() as $hiddenEmail) {
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
     */
    private function moveToInbox(Email $email) : void
    {
        $email->addLabel(new Label(self::INBOX));
        $email->removeLabel($this->getHiddenLabel());

        $this
            ->emailRepository
            ->updateEmail($email);
    }

    /**
     * @return array<Email>
     */
    private function fetchHiddenEmails() : array
    {
        return $this
            ->fetchEmailsLabeled(
                $this->getHiddenLabel()
            );
    }

    /**
     * @return Label
     */
    private function getHiddenLabel() : Label
    {
        return $this->hiddenLabel;
    }

    /**
     * @param Label $label
     * @return array<Email>
     */
    private function fetchEmailsLabeled(Label $label): array
    {
        return $this
            ->emailRepository
            ->fetch([new LabelFilter($label)])
            ;
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
        return !$this->allowedSenders || in_array($hiddenEmail->sender(), $this->allowedSenders);
    }

    /**
     * @param Email $hiddenEmail
     * @return bool
     */
    private function wasSentByNotAllowedSender(Email $hiddenEmail): bool
    {
        return !empty($this->notAllowedSenders) && in_array($hiddenEmail->sender(), $this->notAllowedSenders);
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
     * @param Sender $sender
     * @return $this
     */
    public function addNotAllowedSender(Sender $sender): self
    {
        $this->notAllowedSenders[] = $sender;

        return $this;
    }
}