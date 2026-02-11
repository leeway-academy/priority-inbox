<?php

namespace PriorityInbox;

use Psr\Log\LoggerInterface;

interface EmailRepository
{
    /**
     * @param array $filters
     * @return array<Email>
     */
    public function fetch(array $filters): array;

    /**
     * @param Email $email
     * @return void
     */
    public function updateEmail(Email $email): void;

    public function setLogger(LoggerInterface $logger): self;
}
