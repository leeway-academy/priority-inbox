<?php

namespace PriorityInbox;

class Email
{
    private EmailId $emailId;
    private EmailAddress $senderAddress;
    private array $labels = [];

    public function __construct(EmailId $emailId, EmailAddress $senderAddress)
    {
        $this->emailId = $emailId;
        $this->senderAddress = $senderAddress;
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
}