<?php

namespace PriorityInbox;

use DateTimeImmutable;
use Exception;

class Email
{
    private EmailId $emailId;
    private EmailAddress $sender;
    private array $labels = [];
    private DateTimeImmutable $sentAt;

    public function __construct(EmailId $emailId, EmailAddress $sender, DateTimeImmutable $sentAt)
    {
        $this->emailId = $emailId;
        $this->sender = $sender;
        $this->sentAt = $sentAt;
    }

    /**
     * @return array
     */
    public function labels(): array
    {
        return $this->labels;
    }

    /**
     * @param Label $newLabel
     * @return $this
     */
    public function addLabel(Label $newLabel): self
    {
        $this->labels[] = $newLabel;

        return $this;
    }

    /**
     * @return EmailAddress
     */
    public function sender() : EmailAddress
    {
        return $this->sender;
    }

    /**
     * @return EmailId
     */
    public function id(): EmailId
    {
        return $this->emailId;
    }

    /**
     * @param Label $toRemove
     * @return void
     */
    public function removeLabel(Label $toRemove): void
    {
        foreach ($this->labels as $k => $label) {
            if ($label == $toRemove) {
                unset($this->labels[$k]);

                break;
            }
        }
    }

    /**
     * @return int
     * @throws Exception
     */
    public function hoursSinceItWasSent() : int
    {
        return (new DateTimeImmutable('now', $this->sentAt->getTimeZone()))->diff($this->sentAt, true)->h;
    }
}