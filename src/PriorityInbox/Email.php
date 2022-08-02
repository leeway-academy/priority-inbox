<?php

namespace PriorityInbox;

use DateTimeImmutable;
use Exception;

class Email
{
    private EmailId $emailId;
    private Sender $sender;
    private DateTimeImmutable $sentAt;
    /**
     * @var array<Label>
     */
    private array $labels = [];
    /**
     * @var array<Label>
     */
    private array $addedLabels = [];
    /**
     * @var array<Label>
     */
    private array $removedLabels = [];

    /**
     * @param EmailId $emailId
     * @param Sender $sender
     * @param DateTimeImmutable $sentAt
     * @param array $labels
     */
    public function __construct(EmailId $emailId, Sender $sender, DateTimeImmutable $sentAt, array $labels = [])
    {
        $this->emailId = $emailId;
        $this->sender = $sender;
        $this->sentAt = $sentAt;
        $this->labels = $labels;
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
        $this->labels[] = $this->addedLabels[] = $newLabel;

        return $this;
    }

    /**
     * @return Sender
     */
    public function sender() : Sender
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
                $this->removedLabels[] = $toRemove;

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

    /**
     * @return array<Label>
     */
    public function addedLabels(): array
    {
        return $this->addedLabels;
    }

    /**
     * @return array<Label>
     */
    public function removedLabels(): array
    {
        return $this->removedLabels;
    }

    /**
     * @return DateTimeImmutable
     */
    public function sentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }
}