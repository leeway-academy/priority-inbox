<?php

use PriorityInbox\Email;
use PriorityInbox\EmailRepository;

class EmailRepositoryStub implements EmailRepository
{
    /**
     * @var array<Email>
     */
    private array $emails;

    /**
     * @param Email $email
     * @return $this
     */
    public function addEmail(Email $email) : self
    {
        $this->emails[$email->id()->value()] = $email;

        return $this;
    }

    public function setEmails(array $emails): self
    {
        $this->emails = $emails;

        return $this;
    }
    /**
     * @inheritDoc
     */
    public function fetch(array $filters): array
    {
        return $this->emails;
    }

    /**
     * @inheritDoc
     */
    public function updateEmail(Email $email): void
    {
        // TODO: Implement updateEmail() method.
    }

    /**
     * @param string $emailId
     * @return Email
     */
    public function getEmail(string $emailId): Email
    {
        return $this->emails[$emailId];
    }
}