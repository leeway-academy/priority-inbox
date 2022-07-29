<?php

namespace PriorityInbox;

class EmailPriorityMover
{
    const INBOX = "INBOX";
    private EmailRepository $emailRepository;
    private array $allowedSenders = [];
    private Label $hiddenLabel;

    public function __construct(EmailRepository $emailRepository, Label $hiddenLabel)
    {
        $this->emailRepository = $emailRepository;
        $this->hiddenLabel = $hiddenLabel;
    }

    public function fillInbox(): void
    {
        foreach ($this->getAllowedSenders() as $allowedSender) {
            $this->moveToInboxIfSentBy($allowedSender);
        }
    }

    private function moveToInboxIfSentBy(EmailAddress $allowedSender): void
    {
        foreach ($this->fetchEmailsToMove() as $email) {
            $this->moveToInbox($email);
        }
    }

    private function getAllowedSenders() : array
    {
        return $this->allowedSenders;
    }

    private function moveToInbox(Email $email) : void
    {
        $email->addLabel(new Label(self::INBOX));
        $this->emailRepository->updateEmail($email);
    }

    public function addAllowedSender(EmailAddress $sender): self
    {
        $this->allowedSenders[] = $sender;

        return $this;
    }

    private function fetchEmailsToMove() : array
    {
        return array_filter(
            $this->fetchEmailsLabeled($this->getHiddenLabel()),
            fn(Email $email) => in_array($email->getSender(), $this->getAllowedSenders())
        );
    }

    private function getHiddenLabel() : Label
    {
        return $this->hiddenLabel;
    }

    private function fetchEmailsLabeled(Label $label): array
    {
        /**
         * @todo this method should use the query methods from the underlying repository
         */
        return $this
            ->emailRepository
            ->addFilter()
            ->fetch()
            ;
    }
}