<?php

namespace PriorityInbox;

class Email
{
    private EmailId $emailId;
    private EmailAddress $sender;
    private array $labels = [];

    public function __construct(EmailId $emailId, EmailAddress $sender)
    {
        $this->emailId = $emailId;
        $this->sender = $sender;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function addLabel(Label $newLabel): self
    {
        $this->labels[] = $newLabel;
        return $this;
    }

    public function getSender() : EmailAddress
    {
        return $this->sender;
    }
}