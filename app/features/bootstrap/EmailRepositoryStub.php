<?php

use PriorityInbox\Email;
use PriorityInbox\EmailRepository;
use Psr\Log\LoggerInterface;

class EmailRepositoryStub implements EmailRepository
{
    /**
     * @var array<Email>
     */
    private array $emails;

    private LoggerInterface $logger;

    /**
     * @param Email $email
     * @return $this
     */
    public function addEmail(Email $email): self
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
        $filteredEmails = [];

        foreach ($this->emails as $email) {
            foreach ($filters as $filter) {
                if (!$filter->isValid($email)) {

                    break;
                }
            }

            $filteredEmails[] = $email;
        }

        return $filteredEmails;
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

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}