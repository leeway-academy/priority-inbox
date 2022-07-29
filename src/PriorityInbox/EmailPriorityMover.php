<?php

namespace PriorityInbox;

class EmailPriorityMover
{
    const INBOX = "INBOX";
    private EmailRepository $emailRepository;
    private array $allowedSenders = [];

    public function __construct(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    public function fillInbox(): void
    {
        foreach ($this->getAllowedSenders() as $allowedSender) {
            $this->moveEmailsFromToInbox($allowedSender);
        }
    }

    private function moveEmailsFromToInbox(EmailAddress $allowedSender): void
    {
        foreach ($this->fetchEmailsFrom($allowedSender) as $allowedEmail) {
            $this->moveEmailToInbox($allowedEmail);
        }
    }

    private function getAllowedSenders() : array
    {
        return $this->allowedSenders;
    }

    private function moveEmailToInbox(Email $allowedEmail) : void
    {
        $allowedEmail->addLabel(new Label(self::INBOX));
    }

    private function fetchEmailsFrom(EmailAddress $allowedSender): array
    {
        return $this->emailRepository->fetchFrom($allowedSender);
    }

    public function addAllowedSender(EmailAddress $sender): self
    {
        $this->allowedSenders[] = $sender;

        return $this;
    }
}