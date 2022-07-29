<?php

namespace PriorityInbox;

class Email
{

    const INBOX = "INBOX";
    private EmailId $emailId;
    private EmailAddress $senderAddress;

    public function __construct(EmailId $emailId, EmailAddress $senderAddress)
    {
        $this->emailId = $emailId;
        $this->senderAddress = $senderAddress;
    }

    public function getLabels(): array
    {
        return [self::INBOX];
    }
}